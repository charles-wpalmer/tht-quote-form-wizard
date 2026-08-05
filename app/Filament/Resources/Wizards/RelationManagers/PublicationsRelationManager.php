<?php

namespace App\Filament\Resources\Wizards\RelationManagers;

use App\Enums\JourneyPublicationStatus;
use App\Models\JourneyPublication;
use App\Models\Wizard;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PublicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'publications';

    protected static ?string $title = 'Publications';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version')
            ->columns([
                TextColumn::make('version')
                    ->formatStateUsing(fn (int $state): string => 'v'.$state)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('publisher.name')
                    ->label('Published by')
                    ->placeholder('—'),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('version', 'desc')
            ->headerActions([
                Action::make('createDraft')
                    ->label('Create draft')
                    ->icon(Heroicon::OutlinedPlus)
                    ->visible(fn (): bool => ! $this->hasPendingDraft())
                    ->action(function (): void {
                        /** @var Wizard $wizard */
                        $wizard = $this->getOwnerRecord();
                        $wizard->createDraft();

                        Notification::make()
                            ->title('Draft created')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('publish')
                    ->label('Publish')
                    ->icon(Heroicon::OutlinedRocketLaunch)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('This will snapshot the wizard\'s current questions and copy, and make it the version visitors see.')
                    ->visible(fn (JourneyPublication $record): bool => $record->status === JourneyPublicationStatus::Draft)
                    ->action(function (JourneyPublication $record): void {
                        $record->publish(auth()->user());

                        Notification::make()
                            ->title('Published v'.$record->version)
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->visible(fn (JourneyPublication $record): bool => $record->status === JourneyPublicationStatus::Draft),
            ]);
    }

    private function hasPendingDraft(): bool
    {
        /** @var Wizard $wizard */
        $wizard = $this->getOwnerRecord();

        return $wizard->publications()
            ->where('status', JourneyPublicationStatus::Draft)
            ->exists();
    }
}
