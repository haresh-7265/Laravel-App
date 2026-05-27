<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Logout From All Devices') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('This will immediately log you out of all other active sessions across all devices. Please enter your password to confirm.') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-logout-all')"
    >{{ __('Logout All Devices') }}</x-danger-button>

    <x-modal name="confirm-logout-all" :show="$errors->logoutAll->isNotEmpty()" focusable>
        <form method="post" action="{{ route('logout.all') }}" class="p-6">
            @csrf

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Are you sure you want to logout from all devices?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('All active sessions on other devices will be immediately terminated. Please enter your password to confirm.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="logout_password" value="{{ __('Password') }}" class="sr-only" />

                <x-text-input
                    id="logout_password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Password') }}"
                />
                {{-- {{ dd($errors) }} --}}
                <x-input-error :messages="$errors->logoutAll->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Logout All Devices') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>