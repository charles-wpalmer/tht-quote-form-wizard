<?php

namespace App\Filament\Resources\Wizards\Schemas;

use App\Enums\QuestionType;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class WizardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Wizard details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
                Section::make('Questions')
                    ->schema([
                        Repeater::make('questions')
                            ->relationship()
                            ->orderColumn('sort')
                            ->schema([
                                TextInput::make('label')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Select::make('type')
                                    ->options(QuestionType::class)
                                    ->required()
                                    ->live(),
                                TagsInput::make('options')
                                    ->placeholder('Add an option')
                                    ->visible(fn (Get $get): bool => self::typeRequiresOptions($get('type')))
                                    ->required(fn (Get $get): bool => self::typeRequiresOptions($get('type'))),
                                Toggle::make('is_required')
                                    ->label('Required')
                                    ->default(true),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Add question')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'New question'),
                    ]),
            ]);
    }

    private static function typeRequiresOptions(mixed $type): bool
    {
        if ($type instanceof QuestionType) {
            return $type->requiresOptions();
        }

        if (! is_string($type)) {
            return false;
        }

        return QuestionType::tryFrom($type)?->requiresOptions() ?? false;
    }
}
