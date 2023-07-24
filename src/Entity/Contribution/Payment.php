<?php

namespace App\Entity\Contribution;

use App\Entity\Adherent;
use App\Entity\EntityIdentityTrait;
use App\Entity\EntityTimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="contribution_payment")
 */
class Payment
{
    use EntityIdentityTrait;
    use EntityTimestampableTrait;

    /**
     * @ORM\Column(length=50)
     */
    public ?string $ohmeId = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    public ?\DateTime $date = null;

    /**
     * @ORM\Column(length=50)
     */
    public ?string $method = null;

    /**
     * @ORM\Column(length=50, nullable=true)
     */
    public ?string $status = null;

    /**
     * @ORM\Column(type="integer")
     */
    public ?int $amount = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Adherent", inversedBy="payments")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    public ?Adherent $adherent = null;

    public function __construct(UuidInterface $uuid = null)
    {
        $this->uuid = $uuid ?? Uuid::uuid4();
    }

    public static function fromArray(Adherent $adherent, array $data): self
    {
        $payment = new self();

        $payment->adherent = $adherent;
        $payment->ohmeId = $data['id'];
        $payment->date = $data['date'] ? \DateTime::createFromFormat('Y-m-d\TH:i:sP', $data['date']) : null;
        $payment->method = $data['payment_method_name'];
        $payment->status = $data['payment_status'];
        $payment->amount = round($data['amount']);

        return $payment;
    }
}