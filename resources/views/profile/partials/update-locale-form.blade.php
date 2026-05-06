<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Language Preference') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Choose your preferred language. This will be remembered across sessions.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.locale') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="preferred_locale" :value="__('Language')" />
            <select id="preferred_locale"
                    name="preferred_locale"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900
                           dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600
                           focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                @foreach(\App\Http\Middleware\SetLocale::SUPPORTED as $code)
                    <option value="{{ $code }}" @selected(old('preferred_locale', $user->preferred_locale) === $code)>
                        {{ match($code) {
                            'en' => 'English',
                            'ar' => 'العربية (Arabic)',
                            default => strtoupper($code),
                        } }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('preferred_locale')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'locale-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
