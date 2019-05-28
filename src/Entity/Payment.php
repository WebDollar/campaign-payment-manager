<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Contracts\Entity\IAddress;
use App\Contracts\Entity\ICampaign;
use App\Contracts\Entity\IPayment;
use App\Contracts\Entity\IPaymentTransaction;
use Doctrine\ORM\Mapping as ORM;
use Xterr\Symfony\Contracts\Traits\TimestampableEntityTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"payment:read"}},
 *     denormalizationContext={"groups"={"payment:write"}}
 * )
 * @ORM\Entity(repositoryClass="App\Repository\PaymentRepository")
 */
class Payment implements IPayment
{
    use TimestampableEntityTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Campaign", inversedBy="payments", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotBlank
     * @Groups({"payment:read", "payment:write"})
     */
    private $campaign;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Address", inversedBy="payments", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotBlank
     * @Groups({"payment:read", "payment:write"})
     */
    private $address;

    /**
     * @ORM\Column(type="bigint", options={"unsigned":true})
     * @Assert\NotBlank
     * @Assert\GreaterThan(0)
     * @Groups({"payment:read", "payment:write"})
     */
    private $amount;

    /**
     * @ORM\Column(type="smallint", options={"unsigned":true})
     * @Groups({"payment:read"})
     */
    private $state = self::STATE_NEW;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"payment:read"})
     */
    private $failReason;

    /**
     * @var \DateTimeInterface
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     * @Groups({"payment:read"})
     */
    protected $createdAt;

    /**
     * @var \DateTimeInterface
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     * @Groups({"payment:read"})
     */
    protected $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\PaymentTransaction", inversedBy="payments")
     * @Groups({"payment:read"})
     */
    private $paymentTransaction;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCampaign(): ?ICampaign
    {
        return $this->campaign;
    }

    public function setCampaign(?ICampaign $campaign): void
    {
        $this->campaign = $campaign;
    }

    public function getAddress(): ?IAddress
    {
        return $this->address;
    }

    public function setAddress(?IAddress $address): void
    {
        $this->address = $address;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getState(): ?int
    {
        return $this->state;
    }

    public function setState(int $state): void
    {
        $this->state = $state;
    }

    public function getFailReason(): ?string
    {
        return $this->failReason;
    }

    public function setFailReason(?string $failReason): void
    {
        $this->failReason = $failReason;
    }

    public function getPaymentTransaction():? IPaymentTransaction
    {
        return $this->paymentTransaction;
    }

    public function setPaymentTransaction(?IPaymentTransaction $paymentTransaction): void
    {
        $this->paymentTransaction = $paymentTransaction;
    }
}
