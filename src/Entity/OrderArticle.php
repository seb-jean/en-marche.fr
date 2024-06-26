<?php

namespace App\Entity;

use App\Repository\OrderArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'order_articles')]
#[ORM\Entity(repositoryClass: OrderArticleRepository::class)]
class OrderArticle implements EntityContentInterface, EntitySoftDeletedInterface
{
    use EntityTimestampableTrait;
    use EntitySoftDeletableTrait;
    use EntityContentTrait;

    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var int
     *
     * @Assert\NotBlank
     */
    #[ORM\Column(type: 'smallint')]
    private $position;

    /**
     * @var OrderSection[]|Collection
     *
     * @Assert\Count(min=1)
     */
    #[ORM\JoinTable(name: 'order_section_order_article')]
    #[ORM\JoinColumn(name: 'order_article_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'order_section_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\ManyToMany(targetEntity: OrderSection::class, inversedBy: 'articles')]
    private $sections;

    /**
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    private $published = false;

    /**
     * @var Media|null
     */
    #[ORM\ManyToOne(targetEntity: Media::class)]
    private $media;

    /**
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    private $displayMedia = true;

    public function __construct()
    {
        $this->sections = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->title ?: '';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition($position): void
    {
        $this->position = $position;
    }

    public function addSection(OrderSection $section): void
    {
        $this->sections[] = $section;
    }

    public function removeSection(OrderSection $section): void
    {
        $this->sections->removeElement($section);
    }

    /**
     * @return OrderSection[]|Collection
     */
    public function getSections(): iterable
    {
        return $this->sections;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): void
    {
        $this->published = $published;
    }

    public function getMedia(): ?Media
    {
        return $this->media;
    }

    public function setMedia(?Media $media = null): void
    {
        $this->media = $media;
    }

    public function displayMedia(): bool
    {
        return $this->displayMedia;
    }

    public function setDisplayMedia(bool $displayMedia): void
    {
        $this->displayMedia = $displayMedia;
    }
}
