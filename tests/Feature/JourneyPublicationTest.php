<?php

use App\Enums\JourneyPublicationStatus;
use App\Enums\QuestionType;
use App\Filament\Resources\Wizards\Pages\EditWizard;
use App\Filament\Resources\Wizards\RelationManagers\PublicationsRelationManager;
use App\Models\JourneyPublication;
use App\Models\Question;
use App\Models\User;
use App\Models\Wizard;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

test('wizard model can create a draft and publish it', function () {
    $user = User::factory()->create();
    $wizard = Wizard::factory()->create(['name' => 'Home Quote']);

    Question::factory()->create([
        'wizard_id' => $wizard->id,
        'label' => 'Property address',
        'type' => QuestionType::Text,
        'sort' => 1,
    ]);

    $draft = $wizard->createDraft();

    expect($draft->version)->toBe(1)
        ->and($draft->status)->toBe(JourneyPublicationStatus::Draft)
        ->and($draft->content)->toBeNull();

    $draft->publish($user);
    $wizard->refresh();

    expect($draft->fresh()->status)->toBe(JourneyPublicationStatus::Publish)
        ->and($draft->fresh()->published_by)->toBe($user->id)
        ->and($draft->fresh()->content['name'])->toBe('Home Quote')
        ->and($draft->fresh()->content['questions'])->toHaveCount(1)
        ->and($draft->fresh()->content['questions'][0]['label'])->toBe('Property address')
        ->and($wizard->current_published_version_id)->toBe($draft->id);
});

test('creating a second draft increments the version', function () {
    $user = User::factory()->create();
    $wizard = Wizard::factory()->create();

    $wizard->createDraft()->publish($user);
    $second = $wizard->createDraft();

    expect($second->version)->toBe(2);
});

test('admins can create a draft from the wizard edit page', function () {
    $user = User::factory()->create();
    $wizard = Wizard::factory()->create();

    $this->actingAs($user);

    Livewire::test(PublicationsRelationManager::class, [
        'ownerRecord' => $wizard,
        'pageClass' => EditWizard::class,
    ])
        ->callAction(TestAction::make('createDraft')->table())
        ->assertNotified();

    expect($wizard->publications()->where('status', JourneyPublicationStatus::Draft)->count())->toBe(1);
});

test('admins can publish a draft from the wizard edit page', function () {
    $user = User::factory()->create();
    $wizard = Wizard::factory()->create();

    Question::factory()->create([
        'wizard_id' => $wizard->id,
        'label' => 'Your name',
    ]);

    $draft = $wizard->createDraft();

    $this->actingAs($user);

    Livewire::test(PublicationsRelationManager::class, [
        'ownerRecord' => $wizard,
        'pageClass' => EditWizard::class,
    ])
        ->callAction(TestAction::make('publish')->table($draft))
        ->assertNotified();

    $draft->refresh();
    $wizard->refresh();

    expect($draft->status)->toBe(JourneyPublicationStatus::Publish)
        ->and($draft->published_by)->toBe($user->id)
        ->and($wizard->current_published_version_id)->toBe($draft->id);
});

test('amending the wizard after publishing does not change the published snapshot', function () {
    $user = User::factory()->create();
    $wizard = Wizard::factory()->create(['name' => 'Original Name']);

    $wizard->createDraft()->publish($user);

    $wizard->update(['name' => 'Amended Name']);

    $publication = JourneyPublication::query()->firstOrFail();

    expect($publication->content['name'])->toBe('Original Name')
        ->and($wizard->fresh()->name)->toBe('Amended Name');
});
