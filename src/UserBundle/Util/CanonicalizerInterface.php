<?php

namespace App\UserBundle\Util;

interface CanonicalizerInterface
{
    /**
     * @param string $string
     *
     * @return string
     */
    public function canonicalize($string): ?string;
}
