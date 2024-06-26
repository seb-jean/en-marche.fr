<?php

namespace App\Entity\Team;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Api\Filter\ScopeVisibilityFilter;
use App\Entity\Adherent;
use App\Entity\EntityAdherentBlameableInterface;
use App\Entity\EntityAdherentBlameableTrait;
use App\Entity\EntityAdministratorBlameableInterface;
use App\Entity\EntityAdministratorBlameableTrait;
use App\Entity\EntityIdentityTrait;
use App\Entity\EntityScopeVisibilityTrait;
use App\Entity\EntityScopeVisibilityWithZoneInterface;
use App\Entity\EntityTimestampableTrait;
use App\Entity\Geo\Zone;
use App\Repository\Team\TeamRepository;
use App\Validator\Scope\ScopeVisibility;
use App\Validator\UniqueInCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     attributes={
 *         "order": {"createdAt": "DESC"},
 *         "normalization_context": {
 *             "groups": {"team_read"}
 *         },
 *         "denormalization_context": {
 *             "groups": {"team_write"}
 *         },
 *         "security": "is_granted('IS_FEATURE_GRANTED', 'team')"
 *     },
 *     collectionOperations={
 *         "get": {
 *             "path": "/v3/teams",
 *             "normalization_context": {
 *                 "groups": {"team_list_read"}
 *             },
 *             "maximum_items_per_page": 1000
 *         },
 *         "post": {
 *             "path": "/v3/teams",
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "path": "/v3/teams/{uuid}",
 *             "requirements": {"uuid": "%pattern_uuid%"},
 *             "security": "is_granted('IS_FEATURE_GRANTED', 'team') and is_granted('SCOPE_CAN_MANAGE', object)"
 *         },
 *         "put": {
 *             "path": "/v3/teams/{uuid}",
 *             "requirements": {"uuid": "%pattern_uuid%"},
 *             "security": "is_granted('IS_FEATURE_GRANTED', 'team') and is_granted('SCOPE_CAN_MANAGE', object)"
 *         }
 *     }
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={
 *     "name": "partial",
 *     "visibility": "exact",
 * })
 *
 * @ApiFilter(ScopeVisibilityFilter::class)
 *
 * @UniqueEntity(
 *     fields={"name", "zone"},
 *     ignoreNull=false,
 *     message="team.name.already_exists",
 *     errorPath="name"
 * )
 *
 * @ScopeVisibility
 */
#[ORM\Table]
#[ORM\UniqueConstraint(columns: ['name', 'zone_id'])]
#[ORM\Entity(repositoryClass: TeamRepository::class)]
class Team implements EntityAdherentBlameableInterface, EntityAdministratorBlameableInterface, EntityScopeVisibilityWithZoneInterface
{
    use EntityIdentityTrait;
    use EntityTimestampableTrait;
    use EntityAdministratorBlameableTrait;
    use EntityAdherentBlameableTrait;
    use EntityScopeVisibilityTrait;

    /**
     * @Assert\NotBlank(message="team.name.not_blank")
     * @Assert\Length(
     *     min=2,
     *     max=255,
     *     minMessage="team.name.min_length",
     *     maxMessage="team.name.max_length"
     * )
     */
    #[Groups(['team_read', 'team_list_read', 'team_write', 'phoning_campaign_read', 'phoning_campaign_list'])]
    #[ORM\Column]
    private ?string $name;

    /**
     * @var Member[]|Collection
     *
     * @Assert\Valid
     * @UniqueInCollection(propertyPath="adherent", message="team.members.adherent_already_in_collection")
     */
    #[ORM\OneToMany(mappedBy: 'team', targetEntity: Member::class, cascade: ['all'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $members;

    #[Groups(['team_list_read'])]
    public ?bool $isDeletable = null;

    public function __construct(?UuidInterface $uuid = null, ?string $name = null, array $members = [], ?Zone $zone = null)
    {
        $this->uuid = $uuid ?? Uuid::uuid4();
        $this->name = $name;

        $this->members = new ArrayCollection();
        foreach ($members as $member) {
            $this->addMember($member);
        }

        $this->setZone($zone);
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Member[]|Collection
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(Member $member): void
    {
        if (!$this->members->contains($member)) {
            $member->setTeam($this);
            $this->members->add($member);
        }
    }

    public function removeMember(Member $member): void
    {
        $this->members->removeElement($member);
    }

    #[Groups(['team_list_read', 'phoning_campaign_read', 'phoning_campaign_list'])]
    #[SerializedName('members_count')]
    public function getMembersCount(): int
    {
        return $this->members->count();
    }

    #[Groups(['team_read', 'team_list_read'])]
    public function getCreator(): string
    {
        return null !== $this->createdByAdherent ? $this->createdByAdherent->getFullName() : 'Admin';
    }

    public function __clone()
    {
        $this->members = new ArrayCollection($this->members->toArray());
    }

    public function hasAdherent(Adherent $adherent): bool
    {
        foreach ($this->members as $member) {
            if ($member->getAdherent() === $adherent) {
                return true;
            }
        }

        return false;
    }

    public function getMember(Adherent $adherent): ?Member
    {
        foreach ($this->members as $member) {
            if ($member->getAdherent() === $adherent) {
                return $member;
            }
        }

        return null;
    }

    public function reorderMembersCollection(): void
    {
        $this->members = new ArrayCollection(array_values($this->members->matching(Criteria::create()->orderBy(['createdAt' => 'DESC']))->toArray()));
    }
}
