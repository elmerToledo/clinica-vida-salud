<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <h1 class="text-lg font-semibold text-gray-900 mb-4">Iniciar sesión</h1>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Usuario o correo (CU01) -->
        <div>
            <x-input-label for="email" value="Usuario o correo electrónico" />
            <x-text-input id="email" class="block mt-1 w-full" type="text" name="email" :value="old('email')" required autofocus autocomplete="username" maxlength="255" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Contraseña -->
        <div class="mt-4">
            <x-input-label for="password" value="Contraseña" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Recordarme -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">Recordarme en este equipo</span>
            </label>
        </div>

        <div class="flex items-center justify-between mt-5">
            @if (Route::has('register'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500" href="{{ route('register') }}">
                    Crear cuenta de paciente
                </a>
            @endif

            <x-boton class="ms-3">Ingresar</x-boton>
        </div>
    </form>
</x-guest-layout>
