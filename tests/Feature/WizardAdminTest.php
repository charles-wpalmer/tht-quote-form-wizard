<?php

use App\Enums\QuestionType;
use App\Filament\Resources\Wizards\Pages\CreateWizard;
use App\Filament\Resources\Wizards\Pages\ListWizards;
use App\Models\User;
use App\Models\Wizard;
use Livewire\Livewire;

test('admin can list wizards', function () {
    $user = User::factory()->create();
    $wizard = Wizard::factory()->create(['name' => 'Insurance Quote']);

    $this->actingAs($user);

    Livewire::test(ListWizards::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$wizard]);
});

test('admin can create a wizard with questions', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(CreateWizard::class)
        ->fillForm([
            'name' => 'Home Quote',
            'description' => 'Collect home details',
            'is_active' => true,
            'questions' => [
                [
                    'label' => 'Property address',
                    'type' => QuestionType::Text->value,
                    'is_required' => true,
                ],
                [
                    'label' => 'Coverage type',
                    'type' => QuestionType::Select->value,
                    'options' => ['Basic', 'Premium'],
                    'is_required' => true,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $wizard = Wizard::query()->where('name', 'Home Quote')->first();

    expect($wizard)->not->toBeNull()
        ->and($wizard->questions)->toHaveCount(2)
        ->and($wizard->questions->first()->label)->toBe('Property address')
        ->and($wizard->questions->last()->type)->toBe(QuestionType::Select)
        ->and($wizard->questions->last()->options)->toBe(['Basic', 'Premium']);
});
