{{--
    Public landing: only the brand name, centered, over a subtle animated
    backdrop (see resources/css/components/landing.css). The name links to
    the login screen (or the dashboard when already signed in).
--}}
<x-layouts::base class="overflow-hidden">
    <div class="landing-backdrop" aria-hidden="true">
        <div class="landing-backdrop__glow landing-backdrop__glow--warm"></div>
        <div class="landing-backdrop__glow landing-backdrop__glow--neutral"></div>
        <div class="landing-backdrop__grid"></div>
    </div>

    <main class="relative z-10 grid min-h-dvh place-items-center px-6">
        <h1>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
               class="block rounded-xl px-4 py-2 text-ink transition-opacity hover:opacity-80">
                <x-brand.wordmark class="landing-wordmark block text-6xl sm:text-8xl" />
            </a>
        </h1>
    </main>
</x-layouts::base>
