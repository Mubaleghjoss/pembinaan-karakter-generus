<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckPamongPermission;
use App\Models\Berita;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeritaSocialLinksFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckPamongPermission::class);
    }

    public function test_admin_can_store_optional_social_links_in_berita_metadata(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('berita.store'), [
                'judul' => 'Berita dengan Media Sosial',
                'isi' => 'Isi berita untuk pengujian.',
                'status' => 'published',
                'social_links' => [
                    'instagram' => 'https://www.instagram.com/p/example/',
                    'tiktok' => 'https://www.tiktok.com/@pkg/video/123',
                    'youtube' => '',
                ],
            ])
            ->assertRedirect(route('berita.index'));

        $berita = Berita::query()->where('judul', 'Berita dengan Media Sosial')->firstOrFail();

        $this->assertSame([
            'instagram' => 'https://www.instagram.com/p/example/',
            'tiktok' => 'https://www.tiktok.com/@pkg/video/123',
        ], $berita->social_links);
    }

    public function test_updating_social_links_preserves_unrelated_metadata(): void
    {
        $admin = User::factory()->admin()->create();
        $berita = Berita::factory()->create([
            'author_id' => $admin->id,
            'metadata' => [
                'source' => 'legacy-import',
                'social_links' => [
                    'instagram' => 'https://www.instagram.com/p/old/',
                ],
            ],
        ]);

        $this->actingAs($admin)
            ->put(route('berita.update', $berita), [
                'judul' => $berita->judul,
                'isi' => $berita->isi,
                'status' => 'published',
                'social_links' => [
                    'instagram' => '',
                    'youtube' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                ],
            ])
            ->assertRedirect(route('berita.index'));

        $berita->refresh();

        $this->assertSame('legacy-import', data_get($berita->metadata, 'source'));
        $this->assertSame([
            'youtube' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ], $berita->social_links);
    }

    public function test_social_link_must_be_a_valid_http_or_https_url(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('berita.store'), [
                'judul' => 'Berita Link Tidak Valid',
                'isi' => 'Isi berita untuk pengujian.',
                'status' => 'draft',
                'social_links' => [
                    'instagram' => 'javascript:alert(1)',
                ],
            ])
            ->assertSessionHasErrors('social_links.instagram');
    }

    public function test_public_news_displays_filled_links_and_teleports_fullscreen_gallery(): void
    {
        $admin = User::factory()->admin()->create();
        $berita = Berita::factory()->published()->create([
            'author_id' => $admin->id,
            'cover_path' => 'berita/covers/example.jpg',
            'images' => ['berita/sliders/one.jpg'],
            'metadata' => [
                'social_links' => [
                    'facebook' => 'https://www.facebook.com/pkg/posts/123',
                ],
            ],
        ]);

        $this->get(route('public.berita', $berita->slug))
            ->assertOk()
            ->assertSee('https://www.facebook.com/pkg/posts/123', false)
            ->assertSee('Facebook')
            ->assertSee('Lihat foto sampul layar penuh')
            ->assertSee('x-teleport="body"', false)
            ->assertSee('openLightbox(1)', false);
    }

    public function test_public_news_uses_title_slug_and_redirects_legacy_urls(): void
    {
        $admin = User::factory()->admin()->create();
        $berita = Berita::factory()->published()->create([
            'judul' => 'Sosialisasi Program PKG serta Penyaksian Pengurus',
            'author_id' => $admin->id,
        ]);

        $canonicalUrl = route('public.berita', $berita->slug);
        $this->assertStringEndsWith(
            '/berita/sosialisasi-program-pkg-serta-penyaksian-pengurus',
            $canonicalUrl
        );
        $this->get($canonicalUrl)->assertOk();
        $this->get('/berita/'.$berita->id)->assertRedirect($canonicalUrl);
        $this->get('/berita-publik/'.$berita->slug)->assertRedirect($canonicalUrl);
    }
}
