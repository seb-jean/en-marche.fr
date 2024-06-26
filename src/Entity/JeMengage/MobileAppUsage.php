<?php

namespace App\Entity\JeMengage;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'jemengage_mobile_app_usage')]
#[ORM\Entity]
class MobileAppUsage
{
    #[ORM\Column(type: 'bigint')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column]
    private ?string $zoneType = null;

    #[ORM\Column]
    private ?string $zoneName = null;

    #[ORM\Column(type: 'bigint')]
    private $uniqueUser;
}
