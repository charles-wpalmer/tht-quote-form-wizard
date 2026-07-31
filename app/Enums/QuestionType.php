<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum QuestionType: string implements HasLabel
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Email = 'email';
    case Number = 'number';
    case Select = 'select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';

    public function getLabel(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Textarea => 'Textarea',
            self::Email => 'Email',
            self::Number => 'Number',
            self::Select => 'Select',
            self::Radio => 'Radio',
            self::Checkbox => 'Checkbox',
        };
    }

    public function requiresOptions(): bool
    {
        return match ($this) {
            self::Select, self::Radio, self::Checkbox => true,
            default => false,
        };
    }
}
