<?php

use App\Enums\QuestionType;
use App\Models\Submission;
use App\Models\Wizard;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Fill wizard')] class extends Component
{
    #[Locked]
    public Wizard $wizard;

    /** @var array<string, mixed> */
    public array $answers = [];

    public bool $submitted = false;

    public function mount(Wizard $wizard): void
    {
        abort_unless($wizard->is_active, 404);

        $this->wizard = $wizard->load(['questions']);

        foreach ($this->wizard->questions as $question) {
            $this->answers[(string) $question->id] = $question->type === QuestionType::Checkbox ? [] : null;
        }
    }

    public function submit(): void
    {
        $this->wizard->loadMissing('questions');

        $rules = [];
        $attributes = [];

        foreach ($this->wizard->questions as $question) {
            $key = 'answers.'.$question->id;
            $attributes[$key] = $question->label;

            $rules[$key] = match ($question->type) {
                QuestionType::Email => [$question->is_required ? 'required' : 'nullable', 'email', 'max:255'],
                QuestionType::Number => [$question->is_required ? 'required' : 'nullable', 'numeric'],
                QuestionType::Textarea, QuestionType::Text => [$question->is_required ? 'required' : 'nullable', 'string', 'max:5000'],
                QuestionType::Select, QuestionType::Radio => [
                    $question->is_required ? 'required' : 'nullable',
                    'string',
                    Rule::in($question->options ?? []),
                ],
                QuestionType::Checkbox => array_values(array_filter([
                    $question->is_required ? 'required' : 'nullable',
                    'array',
                    $question->is_required ? 'min:1' : null,
                ])),
            };

            if ($question->type === QuestionType::Checkbox) {
                $rules[$key.'.*'] = ['string', Rule::in($question->options ?? [])];
            }
        }

        $this->validate($rules, [], $attributes);

        $storedAnswers = [];

        foreach ($this->wizard->questions as $question) {
            $storedAnswers[] = [
                'question_id' => $question->id,
                'label' => $question->label,
                'type' => $question->type->value,
                'value' => $this->answers[(string) $question->id] ?? null,
            ];
        }

        Submission::query()->create([
            'wizard_id' => $this->wizard->id,
            'answers' => $storedAnswers,
        ]);

        $this->submitted = true;

        Flux::toast(variant: 'success', text: __('Thanks! Your responses have been saved.'));
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6">
    <div>
        <flux:button
            variant="ghost"
            size="sm"
            icon="arrow-left"
            :href="route('wizards.index')"
            wire:navigate
            class="mb-4"
        >
            {{ __('All wizards') }}
        </flux:button>

        <flux:heading size="xl">{{ $wizard->name }}</flux:heading>
        @if ($wizard->description)
            <flux:text class="mt-1">{{ $wizard->description }}</flux:text>
        @endif
    </div>

    @if ($submitted)
        <flux:callout icon="check-circle" variant="success">
            <flux:callout.heading>{{ __('Submission received') }}</flux:callout.heading>
            <flux:text>{{ __('Your responses have been saved. You can close this page or fill out another wizard.') }}</flux:text>
            <div class="mt-4">
                <flux:button :href="route('wizards.index')" wire:navigate variant="primary">
                    {{ __('Back to wizards') }}
                </flux:button>
            </div>
        </flux:callout>
    @else
        <form wire:submit="submit" class="space-y-6">
            @foreach ($wizard->questions as $question)
                @php
                    $field = 'answers.'.$question->id;
                @endphp

                <div wire:key="question-{{ $question->id }}">
                    @switch ($question->type)
                        @case (\App\Enums\QuestionType::Textarea)
                            <flux:textarea
                                wire:model="answers.{{ $question->id }}"
                                :label="$question->label"
                                :required="$question->is_required"
                                rows="4"
                            />
                            @break

                        @case (\App\Enums\QuestionType::Email)
                            <flux:input
                                wire:model="answers.{{ $question->id }}"
                                type="email"
                                :label="$question->label"
                                :required="$question->is_required"
                            />
                            @break

                        @case (\App\Enums\QuestionType::Number)
                            <flux:input
                                wire:model="answers.{{ $question->id }}"
                                type="number"
                                :label="$question->label"
                                :required="$question->is_required"
                            />
                            @break

                        @case (\App\Enums\QuestionType::Select)
                            <flux:select
                                wire:model="answers.{{ $question->id }}"
                                :label="$question->label"
                                :placeholder="__('Select an option')"
                                :required="$question->is_required"
                            >
                                @foreach ($question->options ?? [] as $option)
                                    <flux:select.option :value="$option">{{ $option }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            @break

                        @case (\App\Enums\QuestionType::Radio)
                            <flux:radio.group
                                wire:model="answers.{{ $question->id }}"
                                :label="$question->label"
                                :required="$question->is_required"
                            >
                                @foreach ($question->options ?? [] as $option)
                                    <flux:radio :value="$option" :label="$option" />
                                @endforeach
                            </flux:radio.group>
                            <flux:error :name="$field" />
                            @break

                        @case (\App\Enums\QuestionType::Checkbox)
                            <flux:fieldset>
                                <flux:legend>{{ $question->label }}@if ($question->is_required) * @endif</flux:legend>
                                <div class="mt-2 space-y-2">
                                    @foreach ($question->options ?? [] as $option)
                                        <flux:checkbox
                                            wire:model="answers.{{ $question->id }}"
                                            :value="$option"
                                            :label="$option"
                                        />
                                    @endforeach
                                </div>
                                <flux:error :name="$field" />
                            </flux:fieldset>
                            @break

                        @default
                            <flux:input
                                wire:model="answers.{{ $question->id }}"
                                type="text"
                                :label="$question->label"
                                :required="$question->is_required"
                            />
                    @endswitch
                </div>
            @endforeach

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    {{ __('Submit') }}
                </flux:button>
            </div>
        </form>
    @endif
</div>
