<?php

namespace App\Models;

use App\Enums\QuestionType;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $wizard_id
 * @property string $label
 * @property QuestionType $type
 * @property array<int, string>|null $options
 * @property bool $is_required
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['wizard_id', 'label', 'type', 'options', 'is_required', 'sort'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'options' => 'array',
            'is_required' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Wizard, $this>
     */
    public function wizard(): BelongsTo
    {
        return $this->belongsTo(Wizard::class);
    }
}
