<?php

namespace App\Entity\ProcurationV2;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Api\Filter\InZoneOfScopeFilter;
use App\Api\Filter\OrTextSearchFilter;
use App\Procuration\V2\RequestStatusEnum;
use App\Validator\Procuration\ManualAssociations;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="procuration_v2_requests")
 * @ORM\Entity(repositoryClass="App\Repository\Procuration\RequestRepository")
 *
 * @ManualAssociations
 *
 * @ApiResource(
 *     attributes={
 *         "routePrefix": "/v3/procuration",
 *         "security": "is_granted('ROLE_OAUTH_SCOPE_JEMENGAGE_ADMIN') and is_granted('IS_FEATURE_GRANTED', 'procurations')",
 *         "pagination_client_items_per_page": true,
 *         "normalization_context": {
 *             "groups": {"procuration_request_list"},
 *         },
 *     },
 *     itemOperations={
 *         "get": {
 *             "path": "/requests/{uuid}",
 *             "requirements": {"uuid": "%pattern_uuid%"},
 *             "normalization_context": {
 *                 "groups": {"procuration_request_read"},
 *                 "enable_tag_translator": true,
 *                 "datetime_format": "Y-m-d",
 *             },
 *         },
 *         "match": {
 *             "method": "POST",
 *             "path": "/requests/{uuid}/match",
 *             "requirements": {"uuid": "%pattern_uuid%"},
 *             "controller": "App\Controller\Api\Procuration\MatchRequestWithProxyController",
 *             "defaults": {"_api_receive": false},
 *         },
 *         "unmatch": {
 *             "method": "POST",
 *             "path": "/requests/{uuid}/unmatch",
 *             "requirements": {"uuid": "%pattern_uuid%"},
 *             "controller": "App\Controller\Api\Procuration\UnmatchRequestAndProxyController",
 *             "defaults": {"_api_receive": false},
 *         },
 *         "update_status": {
 *             "method": "PATCH",
 *             "path": "/requests/{uuid}",
 *             "requirements": {"uuid": "%pattern_uuid%"},
 *             "validation_groups": {"procuration_update_status"},
 *             "normalization_context": {
 *                 "groups": {"procuration_update_status"},
 *             },
 *             "denormalization_context": {
 *                 "groups": {"procuration_update_status"},
 *             },
 *         },
 *     },
 *     collectionOperations={
 *         "get": {
 *             "normalization_context": {
 *                 "groups": {"procuration_request_list"},
 *                 "enable_tag_translator": true,
 *                 "datetime_format": "Y-m-d",
 *             },
 *         },
 *         "get_proxies": {
 *             "method": "GET",
 *             "path": "/requests/{uuid}/proxies",
 *             "requirements": {"uuid": "%pattern_uuid%"},
 *             "controller": "App\Controller\Api\Procuration\GetMatchedProxiesController",
 *             "normalization_context": {
 *                 "groups": {"procuration_matched_proxy"},
 *                 "enable_tag_translator": true,
 *                 "datetime_format": "Y-m-d",
 *             },
 *         },
 *     },
 * )
 *
 * @ApiFilter(InZoneOfScopeFilter::class)
 * @ApiFilter(OrderFilter::class, properties={"createdAt"})
 * @ApiFilter(SearchFilter::class, properties={"status": "exact"})
 * @ApiFilter(OrTextSearchFilter::class, properties={"firstNames": "lastName", "lastName": "firstNames", "email": "email"})
 */
class Request extends AbstractProcuration
{
    /**
     * @ORM\Column(enumType=RequestStatusEnum::class)
     *
     * @Assert\Choice(callback={"App\Procuration\V2\RequestStatusEnum", "getAvailableStatuses"}, groups={"procuration_update_status"})
     *
     * @Groups({
     *     "procuration_request_read",
     *     "procuration_request_list",
     *     "procuration_proxy_list",
     *     "procuration_update_status",
     * })
     */
    public RequestStatusEnum $status = RequestStatusEnum::PENDING;

    /**
     * @Assert\Valid
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\ProcurationV2\Proxy", inversedBy="requests")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     *
     * @Groups({
     *     "procuration_request_read",
     *     "procuration_request_list",
     *     "procuration_proxy_list",
     * })
     */
    public ?Proxy $proxy = null;

    public function setProxy(?Proxy $proxy): void
    {
        $this->proxy = $proxy;

        if ($proxy) {
            $proxy->addRequest($this);
        }
    }

    public function isPending(): bool
    {
        return RequestStatusEnum::PENDING === $this->status;
    }

    public function isCompleted(): bool
    {
        return RequestStatusEnum::COMPLETED === $this->status;
    }

    public function isManual(): bool
    {
        return RequestStatusEnum::MANUAL === $this->status;
    }

    public function markAsPending(): void
    {
        $this->status = RequestStatusEnum::PENDING;
    }

    public function markAsCompleted(): void
    {
        $this->status = RequestStatusEnum::COMPLETED;
    }
}
