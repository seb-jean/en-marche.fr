<?php

namespace App\Entity\Mooc;

use App\Entity\EntityTimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'mooc_attachment_link')]
#[ORM\Entity]
class AttachmentLink
{
    use EntityTimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     */
    #[ORM\Column]
    private $title;

    /**
     * @Assert\Url
     */
    #[ORM\Column]
    private $link;

    public function __construct(?string $title = null, ?string $link = null)
    {
        $this->title = $title;
        $this->link = $link;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }
}
