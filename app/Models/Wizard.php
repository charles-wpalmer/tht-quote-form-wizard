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
    public function createDraft(): JourneyPublication
    {
        $nextVersion = ($this->publications()->max('version') ?? 0) + 1;

        return $this->publications()->create([
            'version' => $nextVersion,
            'status' => JourneyPublicationStatus::Draft,
        ]);
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
