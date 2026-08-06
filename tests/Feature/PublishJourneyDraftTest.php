<?php

use App\Actions\Journey\PublishJourneyDraft;
use App\Domains\Journey\Ports\JourneyRepository;
use App\Enums\JourneyPublicationStatus;
use App\Models\Product;
use App\Models\Question;
use App\Models\User;
use App\Models\Wizard;

test('publishing is rejected when a linked product requires a question that no longer exists', function () {
    $user = User::factory()->create();
    $wizard = Wizard::factory()->create();
    $question = Question::factory()->create(['wizard_id' => $wizard->id, 'label' => 'Property address']);

    Product::factory()->create([
        'wizard_id' => $wizard->id,
        'required_questions' => [$question->key],
    ]);

    $question->delete();

    $draft = app(JourneyRepository::class)->createDraft($wizard);

    $result = app(PublishJourneyDraft::class)($draft, $user);

    expect($result->passed)->toBeFalse()
        ->and($result->errors)->toHaveCount(1)
        ->and($result->errors[0])->toContain($question->key)
        ->and($draft->fresh()->status)->toBe(JourneyPublicationStatus::Draft)
        ->and($wizard->fresh()->current_published_version_id)->toBeNull();
});

test('publishing succeeds when the draft satisfies every product requirement', function () {
    $user = User::factory()->create();
    $wizard = Wizard::factory()->create();
    $question = Question::factory()->create(['wizard_id' => $wizard->id, 'label' => 'Property address']);

    Product::factory()->create([
        'wizard_id' => $wizard->id,
        'required_questions' => [$question->key],
    ]);

    $draft = app(JourneyRepository::class)->createDraft($wizard);

    $result = app(PublishJourneyDraft::class)($draft, $user);

    expect($result->passed)->toBeTrue()
        ->and($result->errors)->toBe([])
        ->and($draft->fresh()->status)->toBe(JourneyPublicationStatus::Publish)
        ->and($wizard->fresh()->current_published_version_id)->toBe($draft->id);
});

test('rolling back republishes prior content without re-running product validation', function () {
    $user = User::factory()->create();
    $wizard = Wizard::factory()->create();
    $question = Question::factory()->create(['wizard_id' => $wizard->id, 'label' => 'Property address']);

    $product = Product::factory()->create([
        'wizard_id' => $wizard->id,
        'required_questions' => [$question->key],
    ]);

    $repository = app(JourneyRepository::class);

    $v1 = $repository->createDraft($wizard);
    app(PublishJourneyDraft::class)($v1, $user);

    // Delete both the question a product depends on (which would fail draft
    // validation if it ran) and the product itself, then confirm rollback still
    // succeeds — proving republish() never invokes ValidateJourneyDraft.
    $question->delete();
    $product->delete();

    $v2 = $repository->createDraft($wizard, rollback: true);
    $repository->republish($v2, $v1, $user);
    $repository->restoreLiveState($wizard, $v1->content);
    $wizard->refresh();

    expect($v2->fresh()->status)->toBe(JourneyPublicationStatus::Publish)
        ->and($wizard->current_published_version_id)->toBe($v2->id)
        ->and($wizard->questions()->where('label', 'Property address')->exists())->toBeTrue();
});
