@php
    $currentSocialLinks = old(
        'social_links',
        isset($berita) ? $berita->social_links : []
    );
@endphp

<section class="pkg-card-soft space-y-4 p-4 sm:p-5">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Tautan Postingan Media Sosial</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Opsional. Tautan yang diisi akan muncul pada halaman berita publik.
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach($socialPlatforms as $platform => $label)
            <div>
                <label for="social_link_{{ $platform }}" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $label }}
                </label>
                <input
                    type="url"
                    name="social_links[{{ $platform }}]"
                    id="social_link_{{ $platform }}"
                    value="{{ $currentSocialLinks[$platform] ?? '' }}"
                    class="pkg-field w-full"
                    placeholder="https://..."
                    inputmode="url"
                >
                @error("social_links.{$platform}")
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
    </div>
</section>
