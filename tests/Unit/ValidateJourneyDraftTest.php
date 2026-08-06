<?php

use App\Domains\Journey\JourneyDraft;
use App\Domains\Journey\Services\ValidateJourneyDraft;
use App\Domains\Product\Ports\ProductRequirementsProvider;

/**
 * @param  string[]  $requiredKeys
 */
function fakeProductRequirementsProvider(array $requiredKeys): ProductRequirementsProvider
{
    return new class($requiredKeys) implements ProductRequirementsProvider
    {
        /**
         * @param  string[]  $requiredKeys
         */
        public function __construct(private readonly array $requiredKeys) {}

        public function requiredQuestionKeysFor(int $wizardId): array
        {
            return $this->requiredKeys;
        }
    };
}

/**
 * @param  array<int, array{id: int, key: string, label: string, type: string, options: array<int, string>|null, is_required: bool, sort: int}>  $questions
 */
function draftWithQuestions(array $questions): JourneyDraft
{
    return new JourneyDraft(
        wizardId: 1,
        name: 'Test Wizard',
        description: null,
        questions: $questions,
    );
}

test('passes when every required question key is present in the draft', function () {
    $validate = new ValidateJourneyDraft(fakeProductRequirementsProvider(['address-key', 'name-key']));

    $draft = draftWithQuestions([
        ['id' => 1, 'key' => 'address-key', 'label' => 'Address', 'type' => 'text', 'options' => null, 'is_required' => true, 'sort' => 1],
        ['id' => 2, 'key' => 'name-key', 'label' => 'Name', 'type' => 'text', 'options' => null, 'is_required' => true, 'sort' => 2],
    ]);

    $result = $validate($draft);

    expect($result->passed)->toBeTrue()
        ->and($result->errors)->toBe([]);
});

test('passes when no questions are required at all', function () {
    $validate = new ValidateJourneyDraft(fakeProductRequirementsProvider([]));

    $result = $validate(draftWithQuestions([]));

    expect($result->passed)->toBeTrue()
        ->and($result->errors)->toBe([]);
});

test('fails and lists the missing keys when required questions are absent', function () {
    $validate = new ValidateJourneyDraft(fakeProductRequirementsProvider(['address-key', 'missing-key']));

    $draft = draftWithQuestions([
        ['id' => 1, 'key' => 'address-key', 'label' => 'Address', 'type' => 'text', 'options' => null, 'is_required' => true, 'sort' => 1],
    ]);

    $result = $validate($draft);

    expect($result->passed)->toBeFalse()
        ->and($result->errors)->toHaveCount(1)
        ->and($result->errors[0])->toContain('missing-key');
});

test('fails for every missing key when multiple are absent', function () {
    $validate = new ValidateJourneyDraft(fakeProductRequirementsProvider(['first-missing', 'second-missing']));

    $result = $validate(draftWithQuestions([]));

    expect($result->passed)->toBeFalse()
        ->and($result->errors)->toHaveCount(2)
        ->and(implode(' ', $result->errors))
        ->toContain('first-missing')
        ->toContain('second-missing');
});
