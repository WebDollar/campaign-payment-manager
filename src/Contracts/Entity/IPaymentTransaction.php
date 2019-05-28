<?php

namespace App\Contracts\Entity;

use Doctrine\Common\Collections\Collection;
use Xterr\Symfony\Contracts\Entity\IIdentifiable;
use Xterr\Symfony\Contracts\Entity\ITimestampable;

/**
 * Interface PaymentTransaction
 * @package App\Contracts\Entity
 */
interface IPaymentTransaction extends IIdentifiable, ITimestampable
{
    public const STATE_PENDING   = 0;
    public const STATE_CONFIRMED = 1;
    public const STATE_FAILED    = 2;

    /**
     * @return string|null
     */
    public function getHash(): ?string;

    /**
     * @param string $hash
     */
    public function setHash(string $hash): void;

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
    public function getFeeAmount(): ?int;

    /**
     * @param int $feeAmount
     */
    public function setFeeAmount(int $feeAmount): void;

    /**
     * @return int|null
     */
    public function getConfirmations(): ?int;

    /**
     * @param int $confirmations
     */
    public function setConfirmations(int $confirmations): void;

    /**
     * @return int|null
     */
    public function getState(): ?int;

    /**
     * @param int $state
     */
    public function setState(int $state): void;

    /**
     * @return Collection|IPayment[]
     */
    public function getPayments(): Collection;

    /**
     * @param IPayment $payment
     */
    public function addPayment(IPayment $payment): void;

    /**
     * @param IPayment $payment
     */
    public function removePayment(IPayment $payment): void;
}
