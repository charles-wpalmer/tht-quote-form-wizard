<?php

namespace App\Domains\Journey;

/**
 * A wizard's editable content — its copy and questions — ready to be validated and
 * published as a new JourneyPublication version.
 */
final class JourneyDraft
{
    /**
     * @param  array<int, array{id: int, key: string, label: string, type: string, options: array<int, string>|null, is_required: bool, sort: int}>  $questions
     */
    public function __construct(
        public readonly int $wizardId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly array $questions,
    ) {}

    /**
     * @return string[]
     */
    public function questionKeys(): array
    {
        return array_values(array_map(
            static fn (array $question): string => $question['key'],
            $this->questions,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function toSnapshotArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'questions' => array_values($this->questions),
        ];
    }
}
