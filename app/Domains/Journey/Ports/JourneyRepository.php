<?php

namespace App\Domains\Journey\Ports;

use App\Domains\Journey\JourneyDraft;
use App\Models\JourneyPublication;
use App\Models\User;
use App\Models\Wizard;

/**
 * Persistence boundary for authoring, publishing, and rolling back journey versions.
 * Wizard/JourneyPublication/User are referenced here as the port's own contract types,
 * not as a leak of the persistence implementation into the domain logic itself.
 */
interface JourneyRepository
{
    public function loadDraft(Wizard $wizard): JourneyDraft;

    public function createDraft(Wizard $wizard, bool $rollback = false): JourneyPublication;

    public function publish(JourneyPublication $publication, JourneyDraft $draft, User $publisher): void;

    public function republish(JourneyPublication $publication, JourneyPublication $source, User $publisher): void;

    /**
     * @param  array<string, mixed>  $content
     */
    public function restoreLiveState(Wizard $wizard, array $content): void;
}
