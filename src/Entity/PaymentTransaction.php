<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Contracts\Entity\IPayment;
use App\Contracts\Entity\IPaymentTransaction;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Xterr\Symfony\Contracts\Traits\TimestampableEntityTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"paymentTransaction:read"}},
 *     denormalizationContext={"groups"={"paymentTransaction:write"}}
 * )
 * @ORM\Entity(repositoryClass="App\Repository\PaymentTransactionRepository")
 * @ApiFilter(SearchFilter::class, properties={"hash": "exact"})
 * @ApiFilter(DateFilter::class, properties={"createdAt", "updatedAt"})
 * @ApiFilter(OrderFilter::class, properties={"updatedAt": "DESC"})
 */
class PaymentTransaction implements IPaymentTransaction
{
    use TimestampableEntityTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Groups({"paymentTransaction:read"})
     */
    private $hash;

    /**
     * @Assert\GreaterThan(0)
     * @ORM\Column(type="bigint", options={"unsigned":true})
     * @Groups({"paymentTransaction:read"})
     */
    private $amount;

    /**
     * @ORM\Column(type="bigint", options={"unsigned":true})
     * @Groups({"paymentTransaction:read"})
     */
    private $feeAmount;

    /**
     * @ORM\Column(type="integer", options={"unsigned":true})
     * @Groups({"paymentTransaction:read"})
     */
    private $confirmations = 0;

    /**
     * @ORM\Column(type="smallint", options={"unsigned":true})
     * @Groups({"paymentTransaction:read"})
     */
    private $state = self::STATE_PENDING;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Payment", mappedBy="paymentTransaction")
     * @Groups({"paymentTransaction:read"})
     */
    private $payments;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getFeeAmount(): ?int
    {
        return $this->feeAmount;
    }

    public function setFeeAmount(int $feeAmount): void
    {
        $this->feeAmount = $feeAmount;
    }

    public function getConfirmations(): ?int
    {
        return $this->confirmations;
    }

    public function setConfirmations(int $confirmations): void
    {
        $this->confirmations = $confirmations;
    }

    public function getState(): ?int
    {
        return $this->state;
    }

    public function setState(int $state): void
    {
        $this->state = $state;
    }

    /**
     * @return Collection|IPayment[]
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(IPayment $payment): void
    {
        if (!$this->payments->contains($payment)) {
            $this->payments[] = $payment;
            $payment->setPaymentTransaction($this);
        }
    }

    public function removePayment(IPayment $payment): void
    {
        if ($this->payments->contains($payment)) {
            $this->payments->removeElement($payment);
            // set the owning side to null (unless already changed)
            if ($payment->getPaymentTransaction() === $this) {
                $payment->setPaymentTransaction(null);
            }
        }
    }
}
