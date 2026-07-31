<?php

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Submission;
use App\Models\User;
use App\Models\Wizard;
use Livewire\Livewire;

test('guests cannot view wizards', function () {
    $this->get(route('wizards.index'))->assertRedirect(route('login'));
});

test('authenticated users can view active wizards', function () {
    $user = User::factory()->create();
    $active = Wizard::factory()->create(['name' => 'Quote Form']);
    Wizard::factory()->inactive()->create(['name' => 'Hidden Wizard']);

    $this->actingAs($user)
        ->get(route('wizards.index'))
        ->assertOk()
        ->assertSee('Quote Form')
        ->assertDontSee('Hidden Wizard');
});

test('users can submit a wizard form', function () {
    $user = User::factory()->create();
    $wizard = Wizard::factory()->create();

    $textQuestion = Question::factory()->create([
        'wizard_id' => $wizard->id,
        'label' => 'Your name',
        'type' => QuestionType::Text,
        'sort' => 1,
    ]);

    $selectQuestion = Question::factory()->select()->create([
        'wizard_id' => $wizard->id,
        'label' => 'Package',
        'sort' => 2,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::wizards.show', ['wizard' => $wizard])
        ->set('answers.'.$textQuestion->id, 'Jane Doe')
        ->set('answers.'.$selectQuestion->id, 'Option A')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $submission = Submission::query()->first();

    expect($submission)->not->toBeNull()
        ->and($submission->wizard_id)->toBe($wizard->id)
        ->and($submission->answers)->toHaveCount(2)
        ->and($submission->answers[0]['value'])->toBe('Jane Doe')
        ->and($submission->answers[1]['value'])->toBe('Option A');
});

test('required questions are validated', function () {
    $user = User::factory()->create();
    $wizard = Wizard::factory()->create();

    Question::factory()->create([
        'wizard_id' => $wizard->id,
        'label' => 'Your name',
        'type' => QuestionType::Text,
        'is_required' => true,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::wizards.show', ['wizard' => $wizard])
        ->set('answers', [])
        ->call('submit')
        ->assertHasErrors();

    expect(Submission::query()->count())->toBe(0);
});

test('inactive wizards return not found', function () {
    $user = User::factory()->create();
    $wizard = Wizard::factory()->inactive()->create();

    $this->actingAs($user)
        ->get(route('wizards.show', $wizard))
        ->assertNotFound();
});
