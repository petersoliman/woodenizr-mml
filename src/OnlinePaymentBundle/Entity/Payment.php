<?php

namespace App\OnlinePaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\OnlinePaymentBundle\Model\PaymentModel;
use App\OnlinePaymentBundle\Model\PaymentTrait;
use PN\ServiceBundle\Interfaces\UUIDInterface;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("payment")
 * @ORM\Entity(repositoryClass="App\OnlinePaymentBundle\Repository\PaymentRepository")
 */
class Payment extends PaymentModel implements UUIDInterface
{
    use PaymentTrait;

    /**
     * @ORM\PrePersist
     */
    public function updatedTimestamps()
    {
        if ($this->getCreated() == null) {
            $this->setCreated(new \DateTime());
        }
    }


}
