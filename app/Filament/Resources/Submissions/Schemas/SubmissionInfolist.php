<?php

namespace App\Filament\Resources\Submissions\Schemas;

use App\Models\Submission;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextEntry::make('wizard.name')
                            ->label('Wizard'),
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Submitted at'),
                        KeyValueEntry::make('answers')
                            ->label('Answers')
                            ->state(function (Submission $record): array {
                                $display = [];

                                foreach ($record->answers as $answer) {
                                    $value = $answer['value'] ?? null;

                                    if (is_array($value)) {
                                        $value = implode(', ', $value);
                                    }

                                    if (is_bool($value)) {
                                        $value = $value ? 'Yes' : 'No';
                                    }

                                    $display[$answer['label'] ?? 'Answer'] = (string) ($value ?? '');
                                }

                                return $display;
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
