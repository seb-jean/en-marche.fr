<?php

namespace App\Pap;

use MyCLabs\Enum\Enum;

class BuildingStatusEnum extends Enum
{
    public const TODO = 'todo';
    public const ONGOING = 'ongoing';
    public const COMPLETED = 'completed';
}
