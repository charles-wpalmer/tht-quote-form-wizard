<?php

namespace App\Domains\Product\Ports;

interface ProductRequirementsProvider
{
    /**
     * @return string[] the Question keys required across all products attached to this wizard
     */
    public function requiredQuestionKeysFor(int $wizardId): array;
}
