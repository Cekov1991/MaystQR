{{--
    Styled with Filament's own Blade components and only the layout utilities
    Filament itself compiles. This panel has no custom theme, so classes from
    the application's tailwind.config.js never reach the admin bundle.
--}}
<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-3">
                    <x-filament::badge :color="$lapsed ? 'danger' : 'warning'">
                        {{ $lapsed ? 'Inactive' : 'Trial' }}
                    </x-filament::badge>

                    <span class="font-medium">{{ $heading }}</span>
                </div>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $description }}
                </p>
            </div>

            <x-filament::button
                tag="a"
                :href="route('filament.admin.pages.billing')"
                :color="$lapsed ? 'danger' : 'primary'"
            >
                {{ $action }}
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
