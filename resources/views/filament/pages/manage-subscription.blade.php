<x-filament-panels::page>
    <form wire:submit="update">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Update Subscription
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
