@php($editando = $usuario->exists)

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $editando ? 'Modificar usuario' : 'Nuevo usuario' }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ $editando ? route('usuarios.update', $usuario) : route('usuarios.store') }}"
                  class="bg-white shadow sm:rounded-lg p-4 sm:p-8 space-y-5">
                @csrf
                @if ($editando)
                    @method('PUT')
                @endif

                @if ($errors->any())
                    <div role="alert" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                        Revise los campos marcados: hay datos que no se pudieron guardar.
                    </div>
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-campo name="name" label="Nombre completo" :value="$usuario->name" required maxlength="100" class="sm:col-span-2" />

                    <x-campo name="usuario" label="Usuario" :value="$usuario->usuario" required minlength="3" maxlength="50"
                             pattern="[a-z0-9._\-]{3,50}" title="De 3 a 50 caracteres: minúsculas, números, punto, guion o guion bajo"
                             ayuda="Con este nombre (o con el correo) inicia sesión." />

                    <x-campo name="email" label="Correo electrónico" type="email" :value="$usuario->email" required maxlength="255" />

                    <x-campo-select name="rol" label="Rol" required>
                        @foreach (\App\Models\User::ROLES as $valor => $nombre)
                            <option value="{{ $valor }}" @selected(old('rol', $usuario->rol) === $valor)>{{ $nombre }}</option>
                        @endforeach
                    </x-campo-select>

                    <x-campo-check name="activo" label="Cuenta activa" :checked="$usuario->activo"
                                   ayuda="Una cuenta inactiva no puede iniciar sesión." class="self-end" />

                    <x-campo name="password" :label="$editando ? 'Nueva contraseña' : 'Contraseña'" type="password"
                             :required="! $editando" minlength="8" autocomplete="new-password"
                             :ayuda="$editando ? 'Déjela vacía para no cambiarla. Mínimo 8 caracteres, con letras y números.' : 'Mínimo 8 caracteres, con letras y números.'" />

                    <x-campo name="password_confirmation" label="Confirmar contraseña" type="password"
                             :required="! $editando" minlength="8" autocomplete="new-password" />
                </div>

                <p class="text-xs text-gray-500"><span class="text-red-600">*</span> Campos obligatorios</p>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <x-boton tipo="secundario" :href="route('usuarios.index')">Cancelar</x-boton>
                    <x-boton>{{ $editando ? 'Guardar cambios' : 'Registrar usuario' }}</x-boton>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
