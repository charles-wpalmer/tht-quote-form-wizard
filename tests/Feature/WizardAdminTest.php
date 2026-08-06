<?php

use App\Enums\QuestionType;
use App\Filament\Resources\Wizards\Pages\CreateWizard;
use App\Filament\Resources\Wizards\Pages\ListWizards;
use App\Models\Question;
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
                    'key' => 'property_address',
                    'label' => 'Property address',
                    'type' => QuestionType::Text->value,
                    'is_required' => true,
                ],
                [
                    'key' => 'coverage_type',
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
        ->and($wizard->questions->first()->key)->toBe('property_address')
        ->and($wizard->questions->last()->type)->toBe(QuestionType::Select)
        ->and($wizard->questions->last()->options)->toBe(['Basic', 'Premium']);
});

test('admin cannot create two questions with the same key in one wizard', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(CreateWizard::class)
        ->fillForm([
            'name' => 'Home Quote',
            'questions' => [
                [
                    'key' => 'duplicate-key',
                    'label' => 'Property address',
                    'type' => QuestionType::Text->value,
                    'is_required' => true,
                ],
                [
                    'key' => 'duplicate-key',
                    'label' => 'Coverage type',
                    'type' => QuestionType::Text->value,
                    'is_required' => true,
                ],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors();

    expect(Wizard::query()->where('name', 'Home Quote')->exists())->toBeFalse();
});

test('admin cannot reuse a question key that already belongs to another wizard', function () {
    $user = User::factory()->create();
    $existingWizard = Wizard::factory()->create();

    Question::factory()->create([
        'wizard_id' => $existingWizard->id,
        'key' => 'shared-key',
    ]);

    $this->actingAs($user);

    Livewire::test(CreateWizard::class)
        ->fillForm([
            'name' => 'Home Quote',
            'questions' => [
                [
                    'key' => 'shared-key',
                    'label' => 'Property address',
                    'type' => QuestionType::Text->value,
                    'is_required' => true,
                ],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors();

    expect(Wizard::query()->where('name', 'Home Quote')->exists())->toBeFalse();
});
