<?php

namespace App\Filament\Resources\Wizards\Pages;

use App\Filament\Resources\Wizards\WizardResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWizard extends EditRecord
{
    protected static string $resource = WizardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
