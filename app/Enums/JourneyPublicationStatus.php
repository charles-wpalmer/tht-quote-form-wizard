<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum JourneyPublicationStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Publish = 'publish';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Publish => 'Published',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Publish => 'success',
        };
    }
}
