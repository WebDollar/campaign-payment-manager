<?php

namespace App\Contracts\Entity;

use Doctrine\Common\Collections\Collection;
use Xterr\Symfony\Contracts\Entity\IIdentifiable;
use Xterr\Symfony\Contracts\Entity\ITimestampable;

/**
 * Interface IAddress
 * @package App\Contracts\Entity
 */
interface IAddress extends IIdentifiable, ITimestampable
{
    /**
     * @return string|null
     */
    public function getAddress(): ?string;

    /**
     * @param string $address
     */
    public function setAddress(string $address): void;

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
