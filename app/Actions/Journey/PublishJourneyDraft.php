<?php

namespace App\Actions\Journey;

use App\Domains\Journey\Ports\JourneyRepository;
use App\Domains\Journey\Services\ValidateJourneyDraft;
use App\Domains\Journey\ValidationResult;
use App\Models\JourneyPublication;
use App\Models\User;

class PublishJourneyDraft
{
    public function __construct(
        private readonly ValidateJourneyDraft $validateJourneyDraft,
        private readonly JourneyRepository $journeyRepository,
    ) {}

    /**
     * Validate the wizard's current draft and publish it if — and only if — it still
     * satisfies every product's required questions. Returns the validation outcome
     * either way so the caller can surface any errors.
     */
    public function __invoke(JourneyPublication $draftPublication, User $publisher): ValidationResult
    {
        $draft = $this->journeyRepository->loadDraft($draftPublication->wizard);

        $result = ($this->validateJourneyDraft)($draft);

        if (! $result->passed) {
            return $result;
        }

        $this->journeyRepository->publish($draftPublication, $draft, $publisher);

        return $result;
    }
}
