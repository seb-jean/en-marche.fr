<?php

namespace App\Entity\ProgrammaticFoundation;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @UniqueEntity("label")
 */
#[ORM\Table(name: 'programmatic_foundation_tag')]
#[ORM\Entity]
class Tag
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="100")
     */
    #[Groups(['approach_list_read'])]
    #[ORM\Column(length: 100, unique: true)]
    private $label;

    public function __construct(string $label = '')
    {
        $this->label = $label;
    }

    public function __toString(): string
    {
        return $this->label;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }
}
