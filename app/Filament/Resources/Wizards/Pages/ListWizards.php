<?php

namespace App\Filament\Resources\Wizards\Pages;

use App\Filament\Resources\Wizards\WizardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWizards extends ListRecords
{
    protected static string $resource = WizardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
