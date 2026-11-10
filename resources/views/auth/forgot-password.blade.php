<x-layouts::guest title="Recuperar contraseña">
    <x-auth.heading title="Recupera tu contraseña"
                    description="Te enviaremos un enlace para crear una nueva contraseña." />

    <x-auth.status />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <x-input label="Correo electrónico"
                 name="email"
                 type="email"
                 :value="old('email')"
                 autocomplete="username"
                 required
                 autofocus />

        <x-button submit text="Enviar enlace" class="w-full" />
    </form>

    <p class="mt-8 text-center text-sm text-ink-muted">
        <a href="{{ route('login') }}" class="underline-offset-4 hover:text-ink hover:underline">Volver a iniciar sesión</a>
    </p>
</x-layouts::guest>
