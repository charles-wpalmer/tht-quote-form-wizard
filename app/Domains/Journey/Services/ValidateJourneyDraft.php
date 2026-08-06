<?php

namespace App\Domains\Journey\Services;

use App\Domains\Journey\JourneyDraft;
use App\Domains\Journey\ValidationResult;
use App\Domains\Product\Ports\ProductRequirementsProvider;

/**
 * Confirms a journey draft still answers every question that a product attached to
 * its wizard depends on, before it's allowed to be published.
 */
final class ValidateJourneyDraft
{
    public function __construct(
        private readonly ProductRequirementsProvider $productRequirements,
    ) {}

    public function __invoke(JourneyDraft $draft): ValidationResult
    {
        $requiredKeys = $this->productRequirements->requiredQuestionKeysFor($draft->wizardId);
        $missingKeys = array_values(array_diff($requiredKeys, $draft->questionKeys()));

        if ($missingKeys === []) {
            return ValidationResult::passed();
        }

        $labelsByKey = [];

        foreach ($draft->questions as $question) {
            $labelsByKey[$question['key']] = $question['label'];
        }

        $errors = array_map(
            static fn (string $key): string => isset($labelsByKey[$key])
                ? sprintf('Missing required question: %s (%s)', $labelsByKey[$key], $key)
                : sprintf('Missing required question: %s', $key),
            $missingKeys,
        );

        return ValidationResult::failed($errors);
    }
}
