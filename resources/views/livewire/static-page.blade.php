<div class="mx-auto max-w-[860px] px-6 pb-24 pt-12 md:pt-16">
    <h1 class="krm-h2 font-bold" data-reveal>{{ $c['title'] }}</h1>
    <p class="mt-3 max-w-[58ch] text-[14.5px] leading-relaxed text-kraft/75">{{ $c['intro'] }}</p>
    @isset($c['updated'])<p class="krm-readout mt-2 text-[11.5px] text-kraft/45">{{ $c['updated'] }}</p>@endisset

    @if ($page === 'faq')
        <div class="mt-10 space-y-10">
            @foreach ($c['groups'] as $g)
                <section aria-labelledby="g{{ $loop->index }}">
                    <h2 id="g{{ $loop->index }}" class="krm-readout text-[11.5px] tracking-[0.16em] text-kraft/55">{{ mb_strtoupper($g['title']) }}</h2>
                    <div class="mt-3 divide-y divide-kraft/15 rounded border border-kraft/20">
                        @foreach ($g['items'] as $it)
                            <details class="group px-5 py-4">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-[15px] font-semibold marker:hidden [&::-webkit-details-marker]:hidden">
                                    <span>{{ $it['q'] }}</span>
                                    <svg viewBox="0 0 20 20" class="h-4 w-4 shrink-0 text-kraft/55 transition-transform group-open:rotate-45" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><path d="M10 4v12M4 10h12"/></svg>
                                </summary>
                                <p class="mt-3 max-w-[64ch] text-[14px] leading-relaxed text-kraft/75">{{ $it['a'] }}</p>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org', '@type' => 'FAQPage',
            'mainEntity' => collect($c['groups'])->flatMap(fn ($g) => $g['items'])->map(fn ($it) => [
                '@type' => 'Question', 'name' => $it['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $it['a']],
            ])->values()->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @else
        <div class="mt-10 space-y-8">
            @foreach ($c['sections'] as $s)
                <section>
                    <h2 class="font-display text-[1.1rem] font-semibold">{{ $s['h'] }}</h2>
                    @foreach ($s['p'] ?? [] as $para)
                        <p class="mt-2 max-w-[66ch] text-[14.5px] leading-relaxed text-kraft/75">{{ $para }}</p>
                    @endforeach
                    @if (! empty($s['li']))
                        <ul class="mt-2 max-w-[66ch] list-disc space-y-1.5 pl-5 text-[14.5px] leading-relaxed text-kraft/75">
                            @foreach ($s['li'] as $li)<li>{{ $li }}</li>@endforeach
                        </ul>
                    @endif
                </section>
            @endforeach
        </div>
    @endif

    <nav class="mt-14 border-t border-kraft/15 pt-6" aria-label="{{ __('Halaman lain') }}">
        <ul class="flex flex-wrap gap-x-6 gap-y-2 text-[13.5px]">
            @foreach (\App\Livewire\StaticPage::PAGES as $key => $route)
                @continue($key === $page)
                <li><a href="{{ route($route) }}" wire:navigate class="text-kraft/70 underline-offset-4 transition-colors hover:text-labwhite hover:underline">{{ __('pages.'.$key.'.title') }}</a></li>
            @endforeach
        </ul>
    </nav>
</div>
