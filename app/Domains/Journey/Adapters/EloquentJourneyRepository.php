<?php

namespace App\Domains\Journey\Adapters;

use App\Domains\Journey\JourneyDraft;
use App\Domains\Journey\Ports\JourneyRepository;
use App\Enums\JourneyPublicationStatus;
use App\Models\JourneyPublication;
use App\Models\Question;
use App\Models\User;
use App\Models\Wizard;
use Illuminate\Support\Facades\DB;

class EloquentJourneyRepository implements JourneyRepository
{
    public function loadDraft(Wizard $wizard): JourneyDraft
    {
        $wizard->loadMissing('questions');

        return new JourneyDraft(
            wizardId: $wizard->id,
            name: $wizard->name,
            description: $wizard->description,
            questions: $wizard->questions->map(fn (Question $question): array => [
                'id' => $question->id,
                'key' => $question->key,
                'label' => $question->label,
                'type' => $question->type->value,
                'options' => $question->options,
                'is_required' => $question->is_required,
                'sort' => $question->sort,
            ])->all(),
        );
    }

    public function createDraft(Wizard $wizard, bool $rollback = false): JourneyPublication
    {
        $nextVersion = ($wizard->publications()->max('version') ?? 0) + 1;

        return $wizard->publications()->create([
            'version' => $nextVersion,
            'status' => JourneyPublicationStatus::Draft,
            'rollback' => $rollback,
        ]);
    }

    public function publish(JourneyPublication $publication, JourneyDraft $draft, User $publisher): void
    {
        DB::transaction(function () use ($publication, $draft, $publisher): void {
            $publication->forceFill([
                'content' => $draft->toSnapshotArray(),
                'status' => JourneyPublicationStatus::Publish,
                'published_at' => now(),
                'published_by' => $publisher->id,
            ])->save();

            $publication->wizard->forceFill([
                'current_published_version_id' => $publication->id,
            ])->save();
        });
    }

    public function republish(JourneyPublication $publication, JourneyPublication $source, User $publisher): void
    {
        // The rollback path reuses a prior publication's content verbatim instead of
        // snapshotting the live wizard, and deliberately does not run
        // ValidateJourneyDraft again: that content already passed validation the
        // first time it was published. Re-validating on rollback is a separate,
        // debatable design decision (products may have changed their requirements
        // since) that we're intentionally not making here rather than deciding
        // silently — republishing an old version is always allowed.
        DB::transaction(function () use ($publication, $source, $publisher): void {
            $publication->forceFill([
                'content' => $source->content,
                'status' => JourneyPublicationStatus::Publish,
                'published_at' => now(),
                'published_by' => $publisher->id,
            ])->save();

            $publication->wizard->forceFill([
                'current_published_version_id' => $publication->id,
            ])->save();
        });
    }

    public function restoreLiveState(Wizard $wizard, array $content): void
    {
        DB::transaction(function () use ($wizard, $content): void {
            $wizard->update([
                'name' => $content['name'],
                'description' => $content['description'],
            ]);

            $wizard->questions()->delete();

            foreach ($content['questions'] as $question) {
                $wizard->questions()->create([
                    'key' => $question['key'],
                    'label' => $question['label'],
                    'type' => $question['type'],
                    'options' => $question['options'],
                    'is_required' => $question['is_required'],
                    'sort' => $question['sort'],
                ]);
            }
        });
    }
}
