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
 * @property Carbon|null $published_at
 * @property int|null $published_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['wizard_id', 'version', 'status'])]
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
     * Snapshot the wizard's current questions/copy into this publication and make it the wizard's live version.
     */
    public function publish(User $publisher): void
    {
        $this->wizard->loadMissing('questions');

        $this->forceFill([
            'content' => [
                'name' => $this->wizard->name,
                'description' => $this->wizard->description,
                'questions' => $this->wizard->questions->map(fn (Question $question): array => [
                    'id' => $question->id,
                    'label' => $question->label,
                    'type' => $question->type->value,
                    'options' => $question->options,
                    'is_required' => $question->is_required,
                    'sort' => $question->sort,
                ])->all(),
            ],
            'status' => JourneyPublicationStatus::Publish,
            'published_at' => now(),
            'published_by' => $publisher->id,
        ])->save();

        $this->wizard->forceFill(['current_published_version_id' => $this->id])->save();
    }
}
