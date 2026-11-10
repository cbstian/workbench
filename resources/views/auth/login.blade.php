<x-layouts::guest title="Iniciar sesión">
    <x-auth.heading title="Inicia sesión" description="Ingresa con tu correo y contraseña." />

    <x-auth.status />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <x-input label="Correo electrónico"
                 name="email"
                 type="email"
                 :value="old('email')"
                 autocomplete="username"
                 required
                 autofocus />

        <x-password label="Contraseña"
                    name="password"
                    autocomplete="current-password"
                    required />

        <div class="flex items-center justify-between gap-4">
            <x-checkbox label="Recordarme" name="remember" value="1" />

            <a href="{{ route('password.request') }}" class="text-sm text-ink-muted underline-offset-4 hover:text-ink hover:underline">
                ¿Olvidaste tu contraseña?
            </a>
        </div>

        <x-button submit text="Continuar" class="w-full" />
    </form>
</x-layouts::guest>
