<x-layouts::guest title="Nueva contraseña">
    <x-auth.heading title="Crea una nueva contraseña" description="Elige una contraseña segura que no uses en otro sitio." />

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-input label="Correo electrónico"
                 name="email"
                 type="email"
                 :value="old('email', $request->email)"
                 autocomplete="username"
                 required />

        <x-password label="Nueva contraseña"
                    name="password"
                    autocomplete="new-password"
                    required
                    autofocus />

        <x-password label="Confirmar contraseña"
                    name="password_confirmation"
                    autocomplete="new-password"
                    required />

        <x-button submit text="Guardar contraseña" class="w-full" />
    </form>
</x-layouts::guest>
