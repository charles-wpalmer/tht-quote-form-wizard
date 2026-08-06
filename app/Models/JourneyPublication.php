<?php

namespace App\Models;

use App\Enums\JourneyPublicationStatus;
use Database\Factories\JourneyPublicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $wizard_id
 * @property int $version
 * @property array<string, mixed>|null $content
 * @property JourneyPublicationStatus $status
 * @property bool $rollback
 * @property Carbon|null $published_at
 * @property int|null $published_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['wizard_id', 'version', 'status', 'rollback'])]
class JourneyPublication extends Model
{
    /** @use HasFactory<JourneyPublicationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => JourneyPublicationStatus::class,
            'content' => 'array',
            'rollback' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Wizard, $this>
     */
    public function wizard(): BelongsTo
    {
        return $this->belongsTo(Wizard::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * Publish this version, making it the wizard's live version. Defaults to snapshotting the
     * wizard's current questions/copy; pass an existing snapshot (e.g. from a prior publication)
     * to republish it as-is, such as when rolling back.
     *
     * @param  array<string, mixed>|null  $content
     */
    public function publish(User $publisher, ?array $content = null): void
    {
        $this->forceFill([
            'content' => $content ?? $this->wizard->snapshotContent(),
            'status' => JourneyPublicationStatus::Publish,
            'published_at' => now(),
            'published_by' => $publisher->id,
        ])->save();

        $this->wizard->forceFill(['current_published_version_id' => $this->id])->save();
    }
}
