<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Question;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('wizard_id')
                            ->label('Wizard')
                            ->relationship('wizard', 'name')
                            ->required()
                            ->live()
                            ->searchable()
                            ->preload()
                            ->afterStateUpdated(fn (Set $set) => $set('required_questions', [])),
                        Select::make('required_questions')
                            ->label('Required questions')
                            ->multiple()
                            ->searchable()
                            ->disabled(fn (Get $get): bool => blank($get('wizard_id')))
                            ->helperText(fn (Get $get): string => blank($get('wizard_id'))
                                ? 'Select a wizard first.'
                                : 'Questions a wizard submission must answer for this product to apply.')
                            ->options(fn (Get $get): array => Question::query()
                                ->where('wizard_id', $get('wizard_id'))
                                ->orderBy('label')
                                ->pluck('label', 'key')
                                ->all())
                            ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                $validKeys = Question::query()
                                    ->where('wizard_id', $get('wizard_id'))
                                    ->pluck('key');

                                if (collect((array) $value)->diff($validKeys)->isNotEmpty()) {
                                    $fail('Required questions must belong to the selected wizard.');
                                }
                            }),
                    ]),
            ]);
    }
}
