<?php

namespace App\UserBundle\Util;

use App\UserBundle\Model\UserInterface;

class CanonicalFieldsUpdater
{
    private CanonicalizerInterface $canonicalizer;

    public function __construct(CanonicalizerInterface $canonicalizer)
    {
        $this->canonicalizer = $canonicalizer;
    }

    public function updateCanonicalFields(UserInterface $user)
    {
        $user->setEmailCanonical($this->canonicalizeEmail($user->getEmail()));
    }

    /**
     * Canonicalizes an email.
     *
     * @param string|null $email
     *
     * @return string|null
     */
    public function canonicalizeEmail(string $email): ?string
    {
        return $this->canonicalizer->canonicalize($email);
    }

}
