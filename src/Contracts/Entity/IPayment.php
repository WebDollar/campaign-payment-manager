<?php

namespace App\Contracts\Entity;

use Xterr\Symfony\Contracts\Entity\IIdentifiable;
use Xterr\Symfony\Contracts\Entity\ITimestampable;

/**
 * Interface IPayment
 * @package App\Contracts\Entity
 */
interface IPayment extends IIdentifiable, ITimestampable
{
    public const STATE_NEW          = 0;
    public const STATE_READY_TO_PAY = 1;
    public const STATE_PENDING      = 2;
    public const STATE_COMPLETED    = 3;
    public const STATE_FAILED       = 4;

    /**
     * @return ICampaign|null
     */
    public function getCampaign(): ?ICampaign;

    /**
     * @param ICampaign|null $campaign
     */
    public function setCampaign(?ICampaign $campaign): void;

    /**
     * @return IAddress|null
     */
    public function getAddress(): ?IAddress;

    /**
     * @param IAddress|null $address
     */
    public function setAddress(?IAddress $address): void;

    /**
     * @return int|null
     */
    public function getAmount(): ?int;

    /**
     * @param int $amount
     */
    public function setAmount(int $amount): void;

    /**
     * @return int|null
     */
    public function getState(): ?int;

    /**
     * @param int $state
     */
    public function setState(int $state): void;

    /**
     * @return string|null
     */
    public function getFailReason(): ?string;

    /**
     * @param string|null $failReason
     */
    public function setFailReason(?string $failReason): void;

    /**
     * @return IPaymentTransaction|null
     */
    public function getPaymentTransaction():? IPaymentTransaction;

    /**
     * @param IPaymentTransaction|null $paymentTransaction
     */
    public function setPaymentTransaction(?IPaymentTransaction $paymentTransaction): void;
}
