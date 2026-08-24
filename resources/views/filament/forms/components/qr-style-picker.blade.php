{{--
    A radio group rendered as selectable sample tiles. This panel has no custom
    theme, so classes from the application's tailwind.config.js never reach the
    admin bundle — the styles below are self-contained and take their colours
    from the palette custom properties Filament itself defines.
--}}
@php
    $id = $getId();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
@endphp

@once
    @push('styles')
        <style>
            .eq-style-picker {
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
            }

            .eq-style-tile {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.5rem;
                width: 8.5rem;
                padding: 0.75rem;
                border: 1px solid rgb(var(--gray-200));
                border-radius: 0.75rem;
                background-color: #fff;
                cursor: pointer;
                transition: border-color 150ms ease, box-shadow 150ms ease;
            }

            .dark .eq-style-tile {
                border-color: rgb(var(--gray-700));
                background-color: rgb(var(--gray-800));
            }

            .eq-style-tile:hover {
                border-color: rgb(var(--gray-300));
            }

            .dark .eq-style-tile:hover {
                border-color: rgb(var(--gray-600));
            }

            .eq-style-input {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border: 0;
            }

            .eq-style-tile:has(.eq-style-input:checked) {
                border-color: rgb(var(--primary-600));
                box-shadow: 0 0 0 1px rgb(var(--primary-600));
            }

            .eq-style-tile:has(.eq-style-input:focus-visible) {
                outline: 2px solid rgb(var(--primary-600));
                outline-offset: 2px;
            }

            .eq-style-plate {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                padding: 0.375rem;
                background-color: #fff;
                border-radius: 0.5rem;
            }

            .eq-style-plate img {
                display: block;
                width: 100%;
                height: auto;
            }

            .eq-style-label {
                display: flex;
                align-items: center;
                min-height: 2rem;
                font-size: 0.75rem;
                line-height: 1rem;
                text-align: center;
                color: rgb(var(--gray-600));
            }

            .dark .eq-style-label {
                color: rgb(var(--gray-400));
            }

            .eq-style-tile:has(.eq-style-input:checked) .eq-style-label {
                font-weight: 600;
                color: rgb(var(--primary-600));
            }

            .eq-style-tile:has(.eq-style-input:disabled) {
                cursor: not-allowed;
                opacity: 0.7;
            }
        </style>
    @endpush
@endonce

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="eq-style-picker" role="radiogroup">
        @foreach ($getOptions() as $value => $label)
            <label class="eq-style-tile" for="{{ $id }}-{{ $value }}">
                <input
                    type="radio"
                    class="eq-style-input"
                    id="{{ $id }}-{{ $value }}"
                    name="{{ $id }}"
                    value="{{ $value }}"
                    @disabled($isDisabled || $isOptionDisabled($value, $label))
                    {{ $applyStateBindingModifiers('wire:model') }}="{{ $statePath }}"
                />

                <span class="eq-style-plate">
                    <img
                        src="{{ \App\Models\QrCode::styleSample($value) }}"
                        alt="{{ $label }} sample"
                    />
                </span>

                <span class="eq-style-label">{{ $label }}</span>
            </label>
        @endforeach
    </div>
</x-dynamic-component>
