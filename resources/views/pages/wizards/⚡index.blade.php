<?php

use App\Models\Wizard;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Wizards')] class extends Component
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Wizard>
     */
    #[Computed]
    public function wizards()
    {
        return Wizard::query()
            ->active()
            ->published()
            ->with('currentPublishedVersion')
            ->orderBy('name')
            ->get();
    }
}; ?>

<div class="flex w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Wizards') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Choose a wizard to fill out.') }}</flux:text>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->wizards as $wizard)
            <a
                href="{{ route('wizards.show', $wizard) }}"
                wire:navigate
                class="block rounded-xl border border-zinc-200 p-5 transition hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-500"
            >
                @php
                    $content = $wizard->currentPublishedVersion->content;
                    $questionCount = count($content['questions'] ?? []);
                @endphp
                <flux:heading size="lg">{{ $content['name'] }}</flux:heading>
                @if ($content['description'] ?? null)
                    <flux:text class="mt-2 line-clamp-3">{{ $content['description'] }}</flux:text>
                @endif
                <flux:text class="mt-4 text-sm">
                    {{ trans_choice(':count question|:count questions', $questionCount, ['count' => $questionCount]) }}
                </flux:text>
            </a>
        @empty
            <flux:callout icon="information-circle" class="md:col-span-2 xl:col-span-3">
                {{ __('No active wizards are available yet.') }}
            </flux:callout>
        @endforelse
    </div>
</div>
