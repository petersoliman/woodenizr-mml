<?php

namespace App\HomeBundle\Utils;

/**
 * example
 *  $MailChimp = new MailChimp('xxxxxxx-us12');
 *  $listId = "66xxx82a";
 *  $MailChimp->call("lists/$listId/members", [
 *  'email_address' => $email,
 *  'status' => "subscribed",
 *  ]);
 */
class MailChimp
{

    private string $api_key;
    private string $api_endpoint = 'https://<dc>.api.mailchimp.com/3.0';
    private bool $verify_ssl = false;

    /**
     * Create a new instance
     * @param string $api_key Your MailChimp API key
     */
    public function __construct(string $api_key)
    {
        $this->api_key = $api_key;
        list(, $datacenter) = explode('-', $this->api_key);
        $this->api_endpoint = str_replace('<dc>', $datacenter, $this->api_endpoint);
    }

    /**
     * Call an API method. Every request needs the API key, so that is added automatically -- you don't need to pass it in.
     * @param string $method The API method to call, e.g. 'lists/list'
     * @param array $args An array of arguments to pass to the method. Will be json-encoded for you.
     * @return array          Associative array of json decoded API response.
     */
    public function call(string $method, array $args = [], int $timeout = 10)
    {
        return $this->makeRequest($method, $args, $timeout);
    }

    /**
     * Performs the underlying HTTP request. Not very exciting
     * @param string $method The API method to be called
     * @param array $args Assoc array of parameters to be passed
     * @return array          Assoc array of decoded result
     */
    private function makeRequest(string $method, array $args = [], int $timeout = 10)
    {
        $url = $this->api_endpoint.'/'.$method;
        $json_data = json_encode($args);

        if (function_exists('curl_init') && function_exists('curl_setopt')) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: apikey '.$this->api_key,
            ]);
            curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
            curl_setopt($ch, CURLOPT_ENCODING, '');
            $result = curl_exec($ch);
            curl_close($ch);
        } else {
            $result = file_get_contents($url, null, stream_context_create(array(
                'http' => [
                    'protocol_version' => 1.1,
                    'user_agent' => 'PHP-MCAPI/2.0',
                    'method' => 'POST',
                    'header' => "Content-type: application/json\r\n".
                        "Connection: close\r\n".
                        "Authorization: apikey".$this->api_key."\r\n".
                        "Content-length: ".strlen($json_data)."\r\n",
                    'content' => $json_data,
                ],
            )));
        }

        return $result ? json_decode($result, true) : false;
    }

}