<?php

namespace App\Entity\Pap;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Api\Filter\ScopeVisibilityFilter;
use App\Entity\EntityAdministratorTrait;
use App\Entity\EntityIdentityTrait;
use App\Entity\EntityScopeVisibilityInterface;
use App\Entity\EntityScopeVisibilityTrait;
use App\Entity\EntityTimestampableTrait;
use App\Entity\Geo\Zone;
use App\Entity\IndexableEntityInterface;
use App\Entity\Jecoute\Survey;
use App\Validator\Scope\ScopeVisibility;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Pap\CampaignRepository")
 * @ORM\Table(name="pap_campaign")
 *
 * @ORM\EntityListeners({"App\EntityListener\AlgoliaIndexListener"})
 *
 * @ApiResource(
 *     shortName="PapCampaign",
 *     attributes={
 *         "order": {"createdAt": "DESC"},
 *         "pagination_client_enabled": true,
 *         "access_control": "is_granted('IS_FEATURE_GRANTED', 'pap') or (is_granted('ROLE_OAUTH_SCOPE_JEMARCHE_APP') and is_granted('ROLE_PAP_USER'))",
 *         "normalization_context": {
 *             "iri": true,
 *             "groups": {"pap_campaign_read"},
 *         },
 *         "denormalization_context": {
 *             "groups": {"pap_campaign_write"}
 *         },
 *     },
 *     itemOperations={
 *         "get": {
 *             "method": "GET",
 *             "path": "/v3/pap_campaigns/{id}",
 *             "requirements": {"id": "%pattern_uuid%"},
 *         },
 *         "put": {
 *             "path": "/v3/pap_campaigns/{id}",
 *             "requirements": {"id": "%pattern_uuid%"},
 *             "access_control": "is_granted('IS_FEATURE_GRANTED', 'pap') and is_granted('SCOPE_CAN_MANAGE', object)",
 *             "normalization_context": {"groups": {"pap_campaign_read_after_write"}},
 *         },
 *         "get_questioners_with_scores": {
 *             "method": "GET",
 *             "path": "/v3/pap_campaigns/{uuid}/questioners",
 *             "requirements": {"uuid": "%pattern_uuid%"},
 *             "access_control": "is_granted('IS_FEATURE_GRANTED', 'pap')",
 *             "controller": "App\Controller\Api\Pap\GetPapCampaignQuestionersStatsController",
 *             "defaults": {"_api_receive": false},
 *         }
 *     },
 *     collectionOperations={
 *         "get": {
 *             "method": "GET",
 *             "path": "/v3/pap_campaigns",
 *         },
 *         "post": {
 *             "path": "/v3/pap_campaigns",
 *             "access_control": "is_granted('IS_FEATURE_GRANTED', 'pap')",
 *             "normalization_context": {"groups": {"pap_campaign_read_after_write"}},
 *         },
 *         "get_kpi": {
 *             "method": "GET",
 *             "path": "/v3/pap_campaigns/kpi",
 *             "controller": "App\Controller\Api\Pap\GetPapCampaignsKpiController",
 *             "access_control": "is_granted('IS_FEATURE_GRANTED', 'pap')",
 *         },
 *     },
 *     subresourceOperations={
 *         "survey_get_subresource": {
 *             "method": "GET",
 *             "path": "/v3/pap_campaigns/{id}/survey",
 *             "requirements": {"id": "%pattern_uuid%"},
 *         },
 *     },
 * )
 *
 * @ApiFilter(ScopeVisibilityFilter::class)
 *
 * @ScopeVisibility
 */
class Campaign implements IndexableEntityInterface, EntityScopeVisibilityInterface
{
    use EntityIdentityTrait;
    use EntityTimestampableTrait;
    use EntityAdministratorTrait;
    use EntityScopeVisibilityTrait;

    /**
     * @var string|null
     *
     * @ORM\Column
     *
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     *
     * @Groups({"pap_campaign_read", "pap_campaign_write", "pap_campaign_read_after_write"})
     */
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"pap_campaign_read", "pap_campaign_write", "pap_campaign_read_after_write"})
     */
    private $brief;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\NotBlank
     * @Assert\GreaterThan(value="0")
     *
     * @Groups({"pap_campaign_read", "pap_campaign_write", "pap_campaign_read_after_write"})
     */
    private $goal;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\NotBlank(groups={"regular_campaign"})
     * @Assert\DateTime
     *
     * @Groups({"pap_campaign_read", "pap_campaign_write", "pap_campaign_read_after_write"})
     */
    private $beginAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\NotBlank(groups={"regular_campaign"})
     * @Assert\DateTime
     *
     * @Groups({"pap_campaign_read", "pap_campaign_write", "pap_campaign_read_after_write"})
     */
    private $finishAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Jecoute\Survey")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Assert\NotBlank
     *
     * @ApiSubresource
     *
     * @Groups({"pap_campaign_write", "pap_campaign_read_after_write"})
     */
    private $survey;

    /**
     * @ORM\Column(type="integer", options={"unsigned": true, "default": 0})
     */
    private int $nbAddresses;

    /**
     * @ORM\Column(type="integer", options={"unsigned": true, "default": 0})
     */
    private int $nbVoters;

    /**
     * @var Collection|CampaignHistory[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Pap\CampaignHistory", mappedBy="campaign", fetch="EXTRA_LAZY")
     */
    private $campaignHistories;

    /**
     * @var VotePlace[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Pap\VotePlace")
     * @ORM\JoinTable(name="pap_campaign_vote_place")
     */
    private $votePlaces;

    public function __construct(
        UuidInterface $uuid = null,
        string $title = null,
        string $brief = null,
        Survey $survey = null,
        int $goal = null,
        \DateTimeInterface $beginAt = null,
        \DateTimeInterface $finishAt = null,
        int $nbAddresses = 0,
        int $nbVoters = 0,
        Zone $zone = null
    ) {
        $this->uuid = $uuid ?? Uuid::uuid4();
        $this->title = $title;
        $this->brief = $brief;
        $this->survey = $survey;
        $this->goal = $goal;
        $this->beginAt = $beginAt;
        $this->finishAt = $finishAt;
        $this->nbAddresses = $nbAddresses;
        $this->nbVoters = $nbVoters;

        $this->campaignHistories = new ArrayCollection();
        $this->votePlaces = new ArrayCollection();

        $this->setZone($zone);
    }

    public function __toString(): string
    {
        return (string) $this->title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getBrief(): ?string
    {
        return $this->brief;
    }

    public function setBrief(?string $brief): void
    {
        $this->brief = $brief;
    }

    public function getGoal(): ?int
    {
        return $this->goal;
    }

    public function setGoal(?int $goal): void
    {
        $this->goal = $goal;
    }

    public function getBeginAt(): ?\DateTime
    {
        return $this->beginAt;
    }

    public function setBeginAt(?\DateTime $beginAt): void
    {
        $this->beginAt = $beginAt;
    }

    public function getFinishAt(): ?\DateTimeInterface
    {
        return $this->finishAt;
    }

    public function setFinishAt(?\DateTime $finishAt): void
    {
        $this->finishAt = $finishAt;
    }

    public function getSurvey(): ?Survey
    {
        return $this->survey;
    }

    public function setSurvey(Survey $survey): void
    {
        $this->survey = $survey;
    }

    public function isFinished(): bool
    {
        return null !== $this->finishAt && $this->finishAt <= new \DateTime();
    }

    public function getNbAddresses(): int
    {
        return $this->nbAddresses;
    }

    public function setNbAddresses(int $nbAddresses): void
    {
        $this->nbAddresses = $nbAddresses;
    }

    public function getNbVoters(): int
    {
        return $this->nbVoters;
    }

    public function setNbVoters(int $nbVoters): void
    {
        $this->nbVoters = $nbVoters;
    }

    /**
     * @return CampaignHistory[]|Collection
     */
    public function getCampaignHistoriesWithDataSurvey(): Collection
    {
        return $this->campaignHistories->filter(function (CampaignHistory $campaignHistory) {
            return $campaignHistory->getDataSurvey();
        });
    }

    public function getCampaignHistoriesToJoin(): Collection
    {
        return $this->campaignHistories->filter(function (CampaignHistory $campaignHistory) {
            return $campaignHistory->isToJoin();
        });
    }

    public function getCampaignHistoriesDoorOpen(): Collection
    {
        return $this->campaignHistories->filter(function (CampaignHistory $campaignHistory) {
            return $campaignHistory->isDoorOpenStatus();
        });
    }

    public function getCampaignHistoriesContactLater(): Collection
    {
        return $this->campaignHistories->filter(function (CampaignHistory $campaignHistory) {
            return $campaignHistory->isContactLaterStatus();
        });
    }

    public function addVotePlace(VotePlace $votePlace): void
    {
        if (!$this->votePlaces->contains($votePlace)) {
            $this->votePlaces->add($votePlace);
        }
    }

    public function removeVotePlace(VotePlace $votePlace): void
    {
        $this->votePlaces->removeElement($votePlace);
    }

    public function getVotePlaces(): Collection
    {
        return $this->votePlaces;
    }

    public function setVotePlaces(array $votePlaces): void
    {
        $this->votePlaces = $votePlaces;
    }

    public function getIndexOptions(): array
    {
        return [];
    }

    public function isIndexable(): bool
    {
        return true;
    }
}
