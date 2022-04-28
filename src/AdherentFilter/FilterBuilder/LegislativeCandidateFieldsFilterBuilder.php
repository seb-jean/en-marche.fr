<?php

namespace App\AdherentFilter\FilterBuilder;

use App\Filter\FilterCollectionBuilder;
use App\Mailchimp\Campaign\AudienceTypeEnum;
use App\Scope\FeatureEnum;
use App\Scope\ScopeEnum;

class LegislativeCandidateFieldsFilterBuilder implements AdherentFilterBuilderInterface
{
    public function supports(string $scope, string $feature = null): bool
    {
        return ScopeEnum::LEGISLATIVE_CANDIDATE === $scope && FeatureEnum::MESSAGES === $feature;
    }

    public function build(string $scope, string $feature = null): array
    {
        return (new FilterCollectionBuilder())
            ->createSelect('audienceTypes', 'Audience')
            ->setRequired(true)
            ->setChoices([
                AudienceTypeEnum::ADHERENT,
                AudienceTypeEnum::LEGISLATIVE_CANDIDATE_NEWSLETTER,
                'all',
            ])
            ->getFilters()
        ;
    }
}