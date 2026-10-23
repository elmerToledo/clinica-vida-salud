<x-app-layout>
    <x-slot name="header">
        <h2>Mi <em>cuenta</em></h2>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 card-soft">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <!-- CU07: cambiar contraseña (exige la contraseña actual) -->
            <div class="p-4 sm:p-8 card-soft">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
