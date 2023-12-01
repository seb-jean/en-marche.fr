<?php

/** @see templates/components/Molecules/ReCheckBoxField.php */

namespace App\Twig\Components\Molecules;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class ReCheckboxField
{
    public ?string $iconTooltip;
    public string $status = 'default';
    public ?string $icon;
}
