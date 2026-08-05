<?php

namespace App\Filament\Resources\Wizards;

use App\Filament\Resources\Wizards\Pages\CreateWizard;
use App\Filament\Resources\Wizards\Pages\EditWizard;
use App\Filament\Resources\Wizards\Pages\ListWizards;
use App\Filament\Resources\Wizards\RelationManagers\PublicationsRelationManager;
use App\Filament\Resources\Wizards\Schemas\WizardForm;
use App\Filament\Resources\Wizards\Tables\WizardsTable;
use App\Models\Wizard;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WizardResource extends Resource
{
    protected static ?string $model = Wizard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return WizardForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WizardsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PublicationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWizards::route('/'),
            'create' => CreateWizard::route('/create'),
            'edit' => EditWizard::route('/{record}/edit'),
        ];
    }
}
