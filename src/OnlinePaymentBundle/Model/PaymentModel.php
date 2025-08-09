<?php

namespace App\OnlinePaymentBundle\Model;

use App\OnlinePaymentBundle\Entity\PaymentMethod;
use App\OnlinePaymentBundle\Enum\PaymentMethodEnum;
use App\OnlinePaymentBundle\Enum\PaymentTypesEnum;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass()
 */
class PaymentModel
{
    /**
     * @ORM\ManyToOne(targetEntity="App\OnlinePaymentBundle\Entity\PaymentMethod", cascade={"persist"})
     */
    protected ?PaymentMethod $paymentMethod = null;

    /**
     * @ORM\Column(name="uuid", type="string", length=50, unique=true)
     */
    protected ?string $uuid = null;

    /**
     * @ORM\Column(name="type", type="string", enumType=PaymentTypesEnum::class, length=255, nullable=true)
     */
    protected ?PaymentTypesEnum $type = null;

    /**
     * Order Id, subscription Id, etc ...
     * @ORM\Column(name="object_id", type="integer", nullable=true)
     */
    protected ?int $objectId = null;

    /**
     * @ORM\Column(name="user_data", type="json")
     */
    protected array $userData = [];

    /**
     * Ex. Subscription,Add Credit Card, etc ..
     * @ORM\Column(name="info", type="text", nullable=true)
     */
    protected ?string $info = null;

    /**
     * @ORM\Column(name="amount", type="float", nullable=true)
     */
    protected ?float $amount = null;

    /**
     * @ORM\Column(name="amount_cents", type="float", nullable=true)
     */
    protected ?float $amountCents = null;

    /**
     * @ORM\Column(name="txn_message", type="text", nullable=true)
     */
    protected ?string $txnMessage = "No Response";

    /**
     * @ORM\Column(name="txn_response_code", nullable=true, type="string", length=255)
     */
    protected ?string $txnResponseCode = null;

    /**
     * @ORM\Column(name="transaction_no", nullable=true, type="string", length=40)
     */
    protected ?string $transactionNo = null;

    /**
     * @ORM\Column(name="sent_data", type="json")
     */
    protected array $sentData = [];

    /**
     * @ORM\Column(name="received_data", type="json", nullable=true)
     */
    protected array $receivedData = [];

    /**
     * @ORM\Column(name="credit_card_received_data", type="json",nullable=true)
     */
    protected array $creditCardReceivedData = [];

    /**
     * @ORM\Column(name="gateway", type="string", length=255 ,nullable=true)
     */
    protected ?string $gateway = null;

    /**
     * @ORM\Column(name="is_automated", type="boolean")
     */
    protected bool $automated = false;

    /**
     * @ORM\Column(name="is_success", type="boolean")
     */
    protected bool $success = false;

    /**
     * @ORM\Column(name="is_refunded", type="boolean")
     */
    protected bool $refunded = false;

    /**
     * @ORM\Column(type="datetime")
     */
    protected ?\DateTimeInterface $created = null;


    public function convertObjectToArray($data)
    {
        if (is_array($data) || is_object($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = (is_array($data) || is_object($data)) ? $this->convertObjectToArray($value) : $value;
            }

            return $result;
        }

        return $data;
    }

    public function setAmount(?float $amount): self
    {
        $this->amount = $amount;
        if ($amount > 0) {
            $this->setAmountCents(floor($amount * 100));
        }

        return $this;
    }

    public function getTypeName(): ?string
    {
        if ($this->getType() instanceof PaymentMethodEnum) {
            return $this->getType()->name();
        }

        return null;
    }

    public function getType(): ?PaymentTypesEnum
    {
        return $this->type;
    }

    public function setType(?PaymentTypesEnum $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getObjectId(): ?int
    {
        return $this->objectId;
    }

    public function setObjectId(?int $objectId): self
    {
        $this->objectId = $objectId;

        return $this;
    }

    public function getUserData(): array
    {
        return $this->userData;
    }

    public function setUserData(array $userData): self
    {
        $this->userData = $userData;

        return $this;
    }

    public function getInfo(): ?string
    {
        return $this->info;
    }

    public function setInfo(?string $info): self
    {
        $this->info = $info;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }


    public function getAmountCents(): ?float
    {
        return $this->amountCents;
    }

    public function setAmountCents(?float $amountCents): self
    {
        $this->amountCents = $amountCents;

        return $this;
    }

    public function getTxnMessage(): ?string
    {
        return $this->txnMessage;
    }

    public function setTxnMessage(?string $txnMessage): self
    {
        $this->txnMessage = $txnMessage;

        return $this;
    }

    public function getTxnResponseCode(): ?string
    {
        return $this->txnResponseCode;
    }

    public function setTxnResponseCode(?string $txnResponseCode): self
    {
        $this->txnResponseCode = $txnResponseCode;

        return $this;
    }

    public function getTransactionNo(): ?string
    {
        return $this->transactionNo;
    }

    public function setTransactionNo(?string $transactionNo): self
    {
        $this->transactionNo = $transactionNo;

        return $this;
    }

    public function getSentData(): array
    {
        return $this->sentData;
    }

    public function setSentData(array $sentData): self
    {
        $this->sentData = $sentData;

        return $this;
    }

    public function getReceivedData(): array
    {
        return $this->receivedData;
    }

    public function setReceivedData(?array $receivedData): self
    {
        $this->receivedData = $receivedData;

        return $this;
    }

    public function getCreditCardReceivedData(): array
    {
        return $this->creditCardReceivedData;
    }

    public function setCreditCardReceivedData(?array $creditCardReceivedData): self
    {
        $this->creditCardReceivedData = $creditCardReceivedData;

        return $this;
    }

    public function getGateway(): ?string
    {
        return $this->gateway;
    }

    public function setGateway(?string $gateway): self
    {
        $this->gateway = $gateway;

        return $this;
    }

    public function isAutomated(): ?bool
    {
        return $this->automated;
    }

    public function setIsAutomated(bool $automated): self
    {
        $this->automated = $automated;

        return $this;
    }

    public function isSuccess(): ?bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): self
    {
        $this->success = $success;

        return $this;
    }

    public function isRefunded(): ?bool
    {
        return $this->refunded;
    }

    public function setRefunded(bool $refunded): self
    {
        $this->refunded = $refunded;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?PaymentMethod $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }


}