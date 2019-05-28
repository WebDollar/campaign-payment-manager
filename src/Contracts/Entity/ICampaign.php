<?php

namespace App\Contracts\Entity;

use Doctrine\Common\Collections\Collection;
use Xterr\Symfony\Contracts\Entity\IIdentifiable;
use Xterr\Symfony\Contracts\Entity\ITimestampable;

/**
 * Interface ICampaign
 * @package App\Contracts\Entity
 */
interface ICampaign extends IIdentifiable, ITimestampable
{
    public const STATE_NEW       = 0;
    public const STATE_PAYING    = 1;
    public const STATE_COMPLETED = 2;

    /**
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @param string $name
     */
    public function setName(string $name): void;

    /**
     * @return string|null
     */
    public function getSlug(): ?string;

    /**
     * @param string $slug
     */
    public function setSlug(string $slug): void;

    /**
     * @return string|null
     */
    public function getPaymentAddress(): ?string;

    /**
     * @param string $address
     */
    public function setPaymentAddress(string $address): void;

    /**
     * @return string|null
     */
    public function getPaymentAddressPassword(): ?string;

    /**
     * @param string|null $paymentAddressPassword
     */
    public function setPaymentAddressPassword(?string $paymentAddressPassword): void;

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
