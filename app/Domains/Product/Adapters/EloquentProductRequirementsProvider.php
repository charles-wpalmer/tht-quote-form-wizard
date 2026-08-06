<?php

namespace App\Domains\Product\Adapters;

use App\Domains\Product\Ports\ProductRequirementsProvider;
use App\Models\Product;

class EloquentProductRequirementsProvider implements ProductRequirementsProvider
{
    /**
     * @return string[]
     */
    public function requiredQuestionKeysFor(int $wizardId): array
    {
        return Product::query()
            ->where('wizard_id', $wizardId)
            ->pluck('required_questions')
            ->filter()
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }
}
