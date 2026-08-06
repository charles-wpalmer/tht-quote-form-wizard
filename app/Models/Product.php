<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $wizard_id
 * @property string $name
 * @property array<int, string>|null $required_questions
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['wizard_id', 'name', 'required_questions'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required_questions' => 'array',
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
