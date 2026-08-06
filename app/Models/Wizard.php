<?php

namespace App\Models;

use App\Enums\JourneyPublicationStatus;
use Database\Factories\WizardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property int|null $current_published_version_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'description', 'is_active'])]
class Wizard extends Model
{
    /** @use HasFactory<WizardFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort');
    }

    /**
     * @return HasMany<Submission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * @return HasMany<JourneyPublication, $this>
     */
    public function publications(): HasMany
    {
        return $this->hasMany(JourneyPublication::class)->orderByDesc('version');
    }

    /**
     * @return BelongsTo<JourneyPublication, $this>
     */
    public function currentPublishedVersion(): BelongsTo
    {
        return $this->belongsTo(JourneyPublication::class, 'current_published_version_id');
    }

    /**
     * Start a new draft version, ready to be amended and published.
     */
    public function createDraft(bool $rollback = false): JourneyPublication
    {
        $nextVersion = ($this->publications()->max('version') ?? 0) + 1;

        return $this->publications()->create([
            'version' => $nextVersion,
            'status' => JourneyPublicationStatus::Draft,
            'rollback' => $rollback,
        ]);
    }

    /**
     * Publish a new version that reuses a prior publication's snapshot as-is, and restores
     * the wizard's live editable state to match, so the admin editor shows what is live.
     */
    public function rollbackTo(JourneyPublication $publication, User $user): JourneyPublication
    {
        $draft = $this->createDraft(rollback: true);

        $draft->publish($user, $publication->content);
        $this->restoreLiveStateFrom($publication->content);

        return $draft;
    }

    /**
     * Overwrite this wizard's live editable name, description, and questions to match a
     * previously published snapshot.
     *
     * @param  array<string, mixed>  $content
     */
    public function restoreLiveStateFrom(array $content): void
    {
        $this->update([
            'name' => $content['name'],
            'description' => $content['description'],
        ]);

        $this->questions()->delete();

        foreach ($content['questions'] as $question) {
            $this->questions()->create([
                'key' => $question['key'],
                'label' => $question['label'],
                'type' => $question['type'],
                'options' => $question['options'],
                'is_required' => $question['is_required'],
                'sort' => $question['sort'],
            ]);
        }
    }

    /**
     * Snapshot this wizard's current questions and copy into a publishable content array.
     *
     * @return array<string, mixed>
     */
    public function snapshotContent(): array
    {
        $this->loadMissing('questions');

        return [
            'name' => $this->name,
            'description' => $this->description,
            'questions' => $this->questions->map(fn (Question $question): array => [
                'id' => $question->id,
                'key' => $question->key,
                'label' => $question->label,
                'type' => $question->type->value,
                'options' => $question->options,
                'is_required' => $question->is_required,
                'sort' => $question->sort,
            ])->all(),
        ];
    }

    /**
     * @param  Builder<Wizard>  $query
     * @return Builder<Wizard>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Wizard>  $query
     * @return Builder<Wizard>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('current_published_version_id');
    }
}
