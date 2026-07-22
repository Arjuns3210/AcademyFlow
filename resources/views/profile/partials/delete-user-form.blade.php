<section>
    <header>
        <h2 class="h5">
            {{ __('Delete Account') }}
        </h2>

        <p class="text-muted small">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <x-danger-button data-bs-toggle="modal" data-bs-target="#confirmUserDeletion">
        {{ __('Delete Account') }}
    </x-danger-button>

    <div class="modal fade" id="confirmUserDeletion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')

                    <div class="modal-header">
                        <h2 class="h5 modal-title">{{ __('Are you sure you want to delete your account?') }}</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <p class="text-muted small">
                            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                        </p>

                        <x-input-label for="password" value="{{ __('Password') }}" class="visually-hidden" />
                        <x-text-input id="password" name="password" type="password" placeholder="{{ __('Password') }}" />
                        <x-input-error :messages="$errors->userDeletion->get('password')" />
                    </div>

                    <div class="modal-footer">
                        <x-secondary-button data-bs-dismiss="modal">
                            {{ __('Cancel') }}
                        </x-secondary-button>
                        <x-danger-button>
                            {{ __('Delete Account') }}
                        </x-danger-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
