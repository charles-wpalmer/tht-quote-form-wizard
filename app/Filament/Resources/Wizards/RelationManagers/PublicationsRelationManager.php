<?php

namespace App\Filament\Resources\Wizards\RelationManagers;

use App\Actions\Journey\PublishJourneyDraft;
use App\Domains\Journey\Ports\JourneyRepository;
use App\Enums\JourneyPublicationStatus;
use App\Filament\Resources\Wizards\WizardResource;
use App\Models\JourneyPublication;
use App\Models\Wizard;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
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
                IconColumn::make('rollback')
                    ->label('Rollback')
                    ->boolean(),
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
                        app(JourneyRepository::class)->createDraft($wizard);

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
                        $result = app(PublishJourneyDraft::class)($record, auth()->user());

                        if (! $result->passed) {
                            Notification::make()
                                ->title('Cannot publish v'.$record->version)
                                ->body(implode("\n", $result->errors))
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Published v'.$record->version)
                            ->success()
                            ->send();
                    }),
                Action::make('rollback')
                    ->label('Rollback to this version')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('This will republish this version\'s snapshot as-is and restore it as the wizard\'s live, editable questions and copy.')
                    ->visible(function (JourneyPublication $record): bool {
                        /** @var Wizard $wizard */
                        $wizard = $this->getOwnerRecord();

                        return $record->status === JourneyPublicationStatus::Publish
                            && $record->id !== $wizard->current_published_version_id;
                    })
                    ->action(function (JourneyPublication $record): void {
                        /** @var Wizard $wizard */
                        $wizard = $this->getOwnerRecord();
                        $repository = app(JourneyRepository::class);

                        $new = $repository->createDraft($wizard, rollback: true);
                        $repository->republish($new, $record, auth()->user());
                        $repository->restoreLiveState($wizard, $record->content);

                        Notification::make()
                            ->title('Rolled back to v'.$record->version.', published as v'.$new->version)
                            ->success()
                            ->send();
                    })
                    ->successRedirectUrl(fn (JourneyPublication $record): string => WizardResource::getUrl('edit', ['record' => $record->wizard_id])),
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
