<?php

namespace App\BaseBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RequestBlockerService
{
    private $params;
    private $rules = [
        #You can add multiple Rules in a array like this one here
        #Notice that large "sec definitions" (like 60*60*60) will blow up your client File
        [
            //if >5 requests in 5 Seconds then Block client 15 Seconds
            'requests' => 5, //5 requests
            'sek' => 5, //5 requests in 5 Seconds
            'blockTime' => 15 // Block client 15 Seconds
        ],
        [
            //if >10 requests in 30 Seconds then Block client 20 Seconds
            'requests' => 10, //10 requests
            'sek' => 30, //10 requests in 30 Seconds
            'blockTime' => 20 // Block client 20 Seconds
        ],
        [
            //if >200 requests in 1 Hour then Block client 10 Minutes
            'requests' => 200, //200 requests
            'sek' => 60 * 60, //200 requests in 1 Hour
            'blockTime' => 60 * 10 // Block client 10 Minutes
        ],
    ];

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->params =$parameterBag;
    }

    public function verify($dir = null, $rules = null)
    {
        $dir = $this->getDir($dir);
        if (is_array($rules)) {
            $this->setRules($rules);
        }

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $time = time();
        $blockIt = [];
        $user = [];

        #Set Unique Name for each Client-File
        $user[] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'IP_unknown';
        $user[] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $user[] = strtolower(gethostbyaddr($user[0]));

        # Notice that I use files because bots do not accept Sessions
        $botFile = $dir.substr($user[0], 0, 8).'_'.substr(md5(join('', $user)), 0, 5).'.txt';

        if (file_exists($botFile)) {
            $file = file_get_contents($botFile);
            $client = json_decode($file, true);

        } else {
            $client = [];
            $client['time'][$time] = 0;
            $client['ip'][$time] = $user[0];
        }

        # Set/Unset Blocktime for blocked Clients
        if (isset($client['block'])) {
            foreach ($client['block'] as $ruleNr => $timestampPast) {
                $elapsed = $time - $timestampPast;
                if (($elapsed) > $this->rules[$ruleNr]['blockTime']) {
                    unset($client['block'][$ruleNr]);
                    continue;
                }
                $blockIt[] = 'Block active for Rule: '.$ruleNr.' - unlock in '.($elapsed - $this->rules[$ruleNr]['blockTime']).' Sec.';
            }
            if (!empty($blockIt)) {
                return $blockIt;
            }
        }

        # log/count each access
        if (!isset($client['time'][$time])) {
            $client['time'][$time] = 1;
            $client['ip'][$time] = $user[0];
        } else {
            $client['time'][$time]++;

        }

        #check the Rules for Client
        $min = [0];
        foreach ($this->rules as $ruleNr => $v) {
            $i = 0;
            $tr = false;
            $sum[$ruleNr] = 0;
            $requests = $v['requests'];
            $sek = $v['sek'];
            foreach ($client['time'] as $timestampPast => $count) {
                if (($time - $timestampPast) < $sek) {
                    $sum[$ruleNr] += $count;
                    if ($tr == false) {
                        #register non-use Timestamps for File
                        $min[] = $i;
                        unset($min[0]);
                        $tr = true;
                    }
                }
                $i++;
            }

            if ($sum[$ruleNr] > $requests) {
                $blockIt[] = 'Limit : '.$ruleNr.'='.$requests.' requests in '.$sek.' seconds!';
                $client['block'][$ruleNr] = $time;
            }
        }
        $min = min($min) - 1;
        #drop non-use Timestamps in File
        foreach ($client['time'] as $k => $v) {
            if (!($min <= $i)) {
                unset($client['time'][$k]);
            }
        }

        //Save into file
        file_put_contents($botFile, json_encode($client));
        return $blockIt;

    }

    private function getDir($dir = null)
    {
        $finalDir = $this->params->get('request_blocker_path');

        if ($dir != null) {
            $finalDir .= rtrim($dir, "/")."/";
        }

        return $finalDir;
    }

    private function setRules(array $rules): void
    {
        $this->rules = $rules;
    }
}