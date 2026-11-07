{{-- Tombol login sosial. Dipakai di halaman masuk dan daftar. --}}
<div>
    @if (session('oauth_error'))
        <p class="mb-4 rounded border border-redtext/30 bg-redtext/10 px-4 py-3 text-[13px] text-redtext" role="alert">{{ session('oauth_error') }}</p>
    @endif

    <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('social.redirect', 'google') }}"
           class="social-btn flex items-center justify-center gap-2.5 rounded-[10px] border border-kraft/30 px-3 py-2.5 text-[14px] font-medium text-labwhite transition-colors hover:border-kraft/70 hover:bg-labwhite/[.04]">
            <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" aria-hidden="true"><path fill="#4285F4" d="M22.5 12.2c0-.8-.1-1.5-.2-2.2H12v4.2h5.9a5 5 0 01-2.2 3.3v2.7h3.5c2.1-1.9 3.3-4.7 3.3-8z"/><path fill="#34A853" d="M12 23c3 0 5.5-1 7.3-2.7l-3.5-2.7c-1 .7-2.2 1.1-3.8 1.1-2.9 0-5.4-2-6.3-4.6H2.1v2.8A11 11 0 0012 23z"/><path fill="#FBBC05" d="M5.7 14.1a6.6 6.6 0 010-4.2V7.1H2.1a11 11 0 000 9.8l3.6-2.8z"/><path fill="#EA4335" d="M12 5.4c1.6 0 3.1.6 4.2 1.7l3.1-3.1A11 11 0 002.1 7.1l3.6 2.8C6.6 7.4 9.1 5.4 12 5.4z"/></svg>
            Google
        </a>
        <a href="{{ route('social.redirect', 'facebook') }}"
           class="social-btn flex items-center justify-center gap-2.5 rounded-[10px] border border-kraft/30 px-3 py-2.5 text-[14px] font-medium text-labwhite transition-colors hover:border-kraft/70 hover:bg-labwhite/[.04]">
            <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" aria-hidden="true"><path fill="#1877F2" d="M24 12a12 12 0 10-13.9 11.9v-8.4H7.1V12h3V9.4c0-3 1.8-4.7 4.5-4.7 1.3 0 2.7.2 2.7.2v3h-1.5c-1.5 0-2 .9-2 1.9V12h3.4l-.5 3.5h-2.8v8.4A12 12 0 0024 12z"/></svg>
            Facebook
        </a>
    </div>

    <div class="my-3.5 flex items-center gap-3 text-[12px] text-kraft/70">
        <span class="h-px flex-1 bg-kraft/20"></span>{{ __('atau pakai email') }}<span class="h-px flex-1 bg-kraft/20"></span>
    </div>
</div>
