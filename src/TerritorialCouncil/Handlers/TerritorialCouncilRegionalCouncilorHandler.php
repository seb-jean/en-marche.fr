<?php

namespace App\TerritorialCouncil\Handlers;

use App\Entity\ElectedRepresentative\MandateTypeEnum;
use App\Entity\TerritorialCouncil\TerritorialCouncilQualityEnum;

class TerritorialCouncilRegionalCouncilorHandler extends AbstractTerritorialCouncilElectedRepresentativeHandler
{
    protected static function getQualityName(): string
    {
        return TerritorialCouncilQualityEnum::REGIONAL_COUNCILOR;
    }

    protected function getMandateTypes(): array
    {
        return [MandateTypeEnum::REGIONAL_COUNCIL];
    }
}
