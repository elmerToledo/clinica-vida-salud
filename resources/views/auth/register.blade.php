<x-guest-layout>
    <h1 class="text-lg font-semibold text-gray-900">Crear cuenta de paciente</h1>
    <p class="mt-1 mb-5 text-sm text-gray-600">Con su cuenta podrá reservar y consultar sus citas médicas.</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <x-campo name="nombres" label="Nombres" required maxlength="100" autofocus autocomplete="given-name"
                 pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.'\-]+" title="Solo letras y espacios" />

        <x-campo name="apellidos" label="Apellidos" required maxlength="100" autocomplete="family-name"
                 pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.'\-]+" title="Solo letras y espacios" />

        <x-campo name="ci" label="CI (carnet de identidad)" required maxlength="20"
                 pattern="[0-9A-Za-z\-]+" title="Solo letras, números y guiones" />

        <div class="grid gap-4 sm:grid-cols-2">
            <x-campo name="fecha_nacimiento" label="Fecha de nacimiento" type="date" required
                     min="1900-01-02" :max="now()->format('Y-m-d')" autocomplete="bday" />

            <x-campo-select name="sexo" label="Sexo" required>
                <option value="">Seleccione…</option>
                <option value="F" @selected(old('sexo') === 'F')>Femenino</option>
                <option value="M" @selected(old('sexo') === 'M')>Masculino</option>
            </x-campo-select>
        </div>

        <x-campo name="telefono" label="Teléfono" type="tel" required maxlength="20" autocomplete="tel"
                 pattern="[0-9+\s\-]{7,20}" title="De 7 a 20 caracteres: números, espacios, + o guiones" />

        <x-campo name="direccion" label="Dirección (opcional)" maxlength="255" autocomplete="street-address" />

        <x-campo name="email" label="Correo electrónico" type="email" required maxlength="150" autocomplete="username"
                 ayuda="Con este correo (o con el usuario que se le asigne) ingresará al sistema." />

        <x-campo name="password" label="Contraseña" type="password" required autocomplete="new-password"
                 ayuda="Mínimo 8 caracteres, con letras y números." />

        <x-campo name="password_confirmation" label="Confirmar contraseña" type="password" required autocomplete="new-password" />

        <p class="text-xs text-gray-500"><span class="text-red-600">*</span> Campos obligatorios</p>

        <div class="flex items-center justify-between pt-1">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500" href="{{ route('login') }}">
                ¿Ya tiene cuenta?
            </a>

            <x-boton>Crear cuenta</x-boton>
        </div>
    </form>
</x-guest-layout>
