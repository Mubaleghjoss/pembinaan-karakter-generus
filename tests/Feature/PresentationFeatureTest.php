<?php

namespace Tests\Feature;

use App\Models\Presentation;
use App\Models\Role;
use App\Models\User;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Vite;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class PresentationFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        app(Vite::class)->useHotFile(storage_path('framework/testing-vite-hot'));
    }

    public function test_admin_can_create_edit_save_and_publish_zoom_presentation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('presentations.index'))
            ->assertOk()
            ->assertSee('Presentasi Materi')
            ->assertSee('Buat Presentasi Baru');

        $this->actingAs($admin)
            ->post(route('presentations.store'), [
                'title' => 'Akhlakul Karimah',
                'description' => 'Materi pembinaan generus.',
            ])
            ->assertRedirect();

        $presentation = Presentation::query()->firstOrFail();
        $this->assertCount(1, $presentation->canvas_data['frames']);

        $this->actingAs($admin)
            ->get(route('presentations.edit', $presentation))
            ->assertOk()
            ->assertSee('Editor Presentasi')
            ->assertSee('data-add-frame', false)
            ->assertSee('data-add-text', false)
            ->assertSee('data-add-image', false)
            ->assertSee('data-add-logo', false)
            ->assertSee('data-add-youtube', false)
            ->assertSee('data-add-link', false)
            ->assertSee('data-add-shape', false)
            ->assertSee('data-add-line', false)
            ->assertSee('data-add-canvas-text', false)
            ->assertSee('data-add-canvas-line', false)
            ->assertSee('data-add-diagram', false)
            ->assertSee('data-editor-block', false)
            ->assertSee('data-block-context-menu', false)
            ->assertSee('data-arrange-frames', false)
            ->assertSee('data-save-layout', false)
            ->assertSee('data-editor-fit', false)
            ->assertSee('data-editor-undo', false)
            ->assertSee('data-editor-redo', false)
            ->assertSee('data-save-before-open', false)
            ->assertSee('penanda hijau')
            ->assertSee('Unduh PDF')
            ->assertSee('Unduh PPTX');

        $canvas = [
            'version' => 1,
            'width' => 1200,
            'height' => 800,
            'elements' => [[
                'id' => 'canvas-text-1',
                'type' => 'text',
                'x' => 120,
                'y' => 40,
                'width' => 1700,
                'height' => 90,
                'rotation' => 0,
                'text' => 'Alur Pembinaan Generus',
                'fontSize' => 36,
                'color' => '#ffffff',
                'backgroundColor' => 'transparent',
                'align' => 'center',
                'bold' => true,
            ], [
                'id' => 'canvas-line-1',
                'type' => 'line',
                'x' => 150,
                'y' => 150,
                'width' => 420,
                'height' => 40,
                'rotation' => 10,
                'strokeWidth' => 5,
                'lineStyle' => 'dotted',
                'arrow' => 'end',
                'color' => '#34d399',
                'backgroundColor' => 'transparent',
            ]],
            'layoutSnapshot' => [
                'savedAt' => '2026-07-25T18:15:00+07:00',
                'frames' => [[
                    'id' => 'frame-pembuka',
                    'x' => 840,
                    'y' => 620,
                    'width' => 800,
                    'height' => 450,
                ]],
                'elements' => [[
                    'id' => 'canvas-text-1',
                    'x' => 120,
                    'y' => 40,
                    'width' => 1700,
                    'height' => 90,
                    'rotation' => 0,
                ]],
            ],
            'frames' => [[
                'id' => 'frame-pembuka',
                'title' => 'Pembuka',
                'x' => 1000,
                'y' => 700,
                'width' => 800,
                'height' => 450,
                'backgroundColor' => '#ffffff',
                'titleColor' => '#be123c',
                'titleFontSize' => 30,
                'shape' => 'custom',
                'borderRadius' => 36,
                'elements' => [[
                    'id' => 'text-1',
                    'type' => 'text',
                    'x' => 50,
                    'y' => 70,
                    'width' => 700,
                    'height' => 140,
                    'text' => 'Pahami, praktikkan, dan teladankan.',
                    'fontSize' => 42,
                    'color' => '#064e3b',
                    'backgroundColor' => 'transparent',
                    'align' => 'center',
                    'bold' => true,
                ], [
                    'id' => 'diagram-1',
                    'type' => 'diagram',
                    'x' => 80,
                    'y' => 240,
                    'width' => 640,
                    'height' => 140,
                    'diagramType' => 'radial',
                    'items' => ['Ilmu', 'Amal', 'Teladan'],
                    'color' => '#047857',
                    'backgroundColor' => 'transparent',
                    'centerText' => 'Karakter',
                    'nodeShape' => 'circle',
                ], [
                    'id' => 'youtube-1',
                    'type' => 'youtube',
                    'x' => 80,
                    'y' => 40,
                    'width' => 320,
                    'height' => 180,
                    'youtubeUrl' => 'https://youtu.be/dQw4w9WgXcQ',
                    'title' => 'Video pembinaan',
                    'color' => '#ffffff',
                    'backgroundColor' => '#0f172a',
                ], [
                    'id' => 'link-1',
                    'type' => 'link',
                    'x' => 430,
                    'y' => 40,
                    'width' => 240,
                    'height' => 70,
                    'text' => 'Buka materi',
                    'url' => 'https://pkgenerus.my.id/materi',
                    'linkStyle' => 'button',
                    'color' => '#ffffff',
                    'backgroundColor' => '#047857',
                ], [
                    'id' => 'shape-1',
                    'type' => 'shape',
                    'x' => 430,
                    'y' => 130,
                    'width' => 220,
                    'height' => 120,
                    'text' => 'Amanah',
                    'shapeType' => 'hexagon',
                    'fontSize' => 28,
                    'color' => '#ffffff',
                    'backgroundColor' => '#0f766e',
                ], [
                    'id' => 'line-1',
                    'type' => 'line',
                    'x' => 80,
                    'y' => 390,
                    'width' => 640,
                    'height' => 40,
                    'rotation' => 0,
                    'strokeWidth' => 5,
                    'lineStyle' => 'dashed',
                    'arrow' => 'both',
                    'color' => '#0f766e',
                    'backgroundColor' => 'transparent',
                ]],
            ]],
        ];

        $this->actingAs($admin)
            ->putJson(route('presentations.update', $presentation), [
                'title' => 'Akhlakul Karimah',
                'description' => 'Materi pembinaan generus.',
                'background_color' => '#0f172a',
                'path_mode' => 'overview_between',
                'canvas_data' => $canvas,
            ])
            ->assertOk()
            ->assertJsonFragment(['message' => 'Presentasi tersimpan.']);

        $this->assertDatabaseHas('presentations', [
            'id' => $presentation->id,
            'path_mode' => 'overview_between',
            'is_published' => false,
        ]);
        $savedCanvas = $presentation->fresh()->canvas_data;
        $this->assertSame(1940, $savedCanvas['width']);
        $this->assertSame(1270, $savedCanvas['height']);
        $this->assertSame('custom', $savedCanvas['frames'][0]['shape']);
        $this->assertEquals(36, $savedCanvas['frames'][0]['borderRadius']);
        $this->assertSame('#be123c', $savedCanvas['frames'][0]['titleColor']);
        $this->assertEquals(30, $savedCanvas['frames'][0]['titleFontSize']);
        $this->assertSame('dQw4w9WgXcQ', $savedCanvas['frames'][0]['elements'][2]['youtubeId']);
        $this->assertSame('https://pkgenerus.my.id/materi', $savedCanvas['frames'][0]['elements'][3]['url']);
        $this->assertSame('hexagon', $savedCanvas['frames'][0]['elements'][4]['shapeType']);
        $this->assertSame('dashed', $savedCanvas['frames'][0]['elements'][5]['lineStyle']);
        $this->assertSame('both', $savedCanvas['frames'][0]['elements'][5]['arrow']);
        $this->assertCount(2, $savedCanvas['elements']);
        $this->assertSame('Alur Pembinaan Generus', $savedCanvas['elements'][0]['text']);
        $this->assertEquals(1700, $savedCanvas['elements'][0]['width']);
        $this->assertSame('dotted', $savedCanvas['elements'][1]['lineStyle']);
        $this->assertSame('end', $savedCanvas['elements'][1]['arrow']);
        $this->assertSame('frame-pembuka', $savedCanvas['layoutSnapshot']['frames'][0]['id']);
        $this->assertEquals(840, $savedCanvas['layoutSnapshot']['frames'][0]['x']);
        $this->assertSame('canvas-text-1', $savedCanvas['layoutSnapshot']['elements'][0]['id']);
        $this->assertEquals(1700, $savedCanvas['layoutSnapshot']['elements'][0]['width']);

        $this->get(route('public.presentations.show', $presentation))->assertNotFound();

        $this->actingAs($admin)
            ->patch(route('presentations.publish', $presentation))
            ->assertRedirect();

        $this->get(route('public.presentations.show', $presentation->fresh()))
            ->assertOk()
            ->assertSee('presentation-viewer', false)
            ->assertSee('data-viewer-bar', false)
            ->assertSee('data-viewer-controls', false)
            ->assertSee('data-viewer-fit', false)
            ->assertSee('Akhlakul Karimah')
            ->assertDontSee(route('presentations.edit', $presentation), false)
            ->assertDontSee(route('presentations.export.pdf', $presentation), false)
            ->assertDontSee(route('presentations.export.pptx', $presentation), false);
    }

    public function test_image_upload_is_scoped_to_presentation_and_removed_with_it(): void
    {
        $admin = $this->admin();
        $presentation = Presentation::create([
            'created_by' => $admin->id,
            'title' => 'Presentasi Gambar',
            'slug' => 'presentasi-gambar-test',
            'background_color' => '#0f172a',
            'path_mode' => 'direct',
            'canvas_data' => [
                'version' => 1,
                'width' => 2400,
                'height' => 1400,
                'frames' => [[
                    'id' => 'frame-1',
                    'title' => 'Frame 1',
                    'x' => 100,
                    'y' => 100,
                    'width' => 800,
                    'height' => 450,
                    'backgroundColor' => '#ffffff',
                    'elements' => [],
                ]],
            ],
        ]);

        $response = $this->actingAs($admin)
            ->post(route('presentations.assets.store', $presentation), [
                'image' => UploadedFile::fake()->image('materi.png', 1200, 800),
            ])
            ->assertCreated();

        $asset = $presentation->assets()->firstOrFail();
        Storage::disk('public')->assertExists($asset->path);
        $response->assertJsonPath('asset.id', $asset->id);

        $this->actingAs($admin)
            ->delete(route('presentations.destroy', $presentation))
            ->assertRedirect(route('presentations.index'));

        $this->assertDatabaseMissing('presentations', ['id' => $presentation->id]);
        $this->assertDatabaseMissing('presentation_assets', ['id' => $asset->id]);
        Storage::disk('public')->assertMissing($asset->path);
    }

    public function test_guest_cannot_open_editor_or_management_page(): void
    {
        $this->get(route('presentations.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_download_pdf_and_editable_powerpoint_exports(): void
    {
        $admin = $this->admin();
        $presentation = Presentation::create([
            'created_by' => $admin->id,
            'title' => 'Materi Ekspor PKG',
            'slug' => 'materi-ekspor-pkg',
            'background_color' => '#0f172a',
            'path_mode' => 'direct',
            'canvas_data' => [
                'version' => 1,
                'width' => 2400,
                'height' => 1400,
                'frames' => [[
                    'id' => 'frame-1',
                    'title' => 'Pembuka',
                    'x' => 100,
                    'y' => 100,
                    'width' => 800,
                    'height' => 450,
                    'backgroundColor' => '#ffffff',
                    'elements' => [[
                        'id' => 'text-1',
                        'type' => 'text',
                        'x' => 80,
                        'y' => 80,
                        'width' => 640,
                        'height' => 120,
                        'text' => 'Teks ini tetap bisa diedit',
                        'fontSize' => 40,
                        'color' => '#064e3b',
                        'backgroundColor' => 'transparent',
                        'align' => 'center',
                        'bold' => true,
                    ], [
                        'id' => 'diagram-1',
                        'type' => 'diagram',
                        'x' => 100,
                        'y' => 250,
                        'width' => 600,
                        'height' => 120,
                        'diagramType' => 'process',
                        'items' => ['Ilmu', 'Amal', 'Teladan'],
                        'color' => '#047857',
                        'backgroundColor' => '#d1fae5',
                    ]],
                ], [
                    'id' => 'frame-2',
                    'title' => 'Penutup',
                    'x' => 1000,
                    'y' => 100,
                    'width' => 800,
                    'height' => 450,
                    'backgroundColor' => '#ecfdf5',
                    'elements' => [[
                        'id' => 'text-2',
                        'type' => 'text',
                        'x' => 100,
                        'y' => 150,
                        'width' => 600,
                        'height' => 120,
                        'text' => 'Terima kasih',
                        'fontSize' => 44,
                        'color' => '#065f46',
                        'backgroundColor' => 'transparent',
                        'align' => 'center',
                        'bold' => true,
                    ]],
                ]],
            ],
        ]);

        $this->actingAs($admin)->post(route('presentations.assets.store', $presentation), [
            'image' => UploadedFile::fake()->image('ilustrasi.png', 640, 360),
        ])->assertCreated();
        $asset = $presentation->assets()->firstOrFail();
        $canvas = $presentation->canvas_data;
        $canvas['frames'][0]['elements'][] = [
            'id' => 'image-1',
            'type' => 'image',
            'x' => 600,
            'y' => 300,
            'width' => 160,
            'height' => 90,
            'assetId' => $asset->id,
            'alt' => 'Ilustrasi materi',
            'fit' => 'contain',
            'rotation' => 0,
            'color' => '#0f172a',
            'backgroundColor' => 'transparent',
        ];
        $canvas['frames'][0]['elements'][] = [
            'id' => 'logo-1',
            'type' => 'logo',
            'x' => 30,
            'y' => 300,
            'width' => 100,
            'height' => 100,
            'assetId' => $asset->id,
            'alt' => 'Logo PKG',
            'fit' => 'contain',
            'shape' => 'circle',
            'rotation' => 0,
            'color' => '#0f172a',
            'backgroundColor' => 'transparent',
        ];
        $canvas['frames'][0]['elements'][] = [
            'id' => 'youtube-1',
            'type' => 'youtube',
            'x' => 150,
            'y' => 300,
            'width' => 260,
            'height' => 100,
            'youtubeUrl' => 'https://youtu.be/dQw4w9WgXcQ',
            'youtubeId' => 'dQw4w9WgXcQ',
            'title' => 'Video PKG',
            'color' => '#ffffff',
            'backgroundColor' => '#0f172a',
        ];
        $canvas['frames'][0]['elements'][] = [
            'id' => 'link-1',
            'type' => 'link',
            'x' => 420,
            'y' => 300,
            'width' => 180,
            'height' => 80,
            'text' => 'Materi lanjut',
            'url' => 'https://pkgenerus.my.id/materi',
            'linkStyle' => 'button',
            'color' => '#ffffff',
            'backgroundColor' => '#047857',
        ];
        $canvas['frames'][1]['elements'][] = [
            'id' => 'shape-1',
            'type' => 'shape',
            'x' => 260,
            'y' => 280,
            'width' => 280,
            'height' => 100,
            'text' => 'Bentuk dapat diedit',
            'shapeType' => 'hexagon',
            'fontSize' => 24,
            'color' => '#ffffff',
            'backgroundColor' => '#0f766e',
        ];
        $canvas['frames'][1]['elements'][] = [
            'id' => 'line-1',
            'type' => 'line',
            'x' => 160,
            'y' => 400,
            'width' => 480,
            'height' => 30,
            'rotation' => -8,
            'strokeWidth' => 5,
            'lineStyle' => 'dotted',
            'arrow' => 'end',
            'color' => '#047857',
            'backgroundColor' => 'transparent',
        ];
        $presentation->update(['canvas_data' => $canvas]);

        $pdf = $this->actingAs($admin)->get(route('presentations.export.pdf', $presentation));
        $pdf->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('materi-ekspor-pkg.pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());

        $pptx = $this->actingAs($admin)->get(route('presentations.export.pptx', $presentation));
        $pptx->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            )
            ->assertDownload('materi-ekspor-pkg.pptx');

        $path = $pptx->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $this->assertNotFalse($zip->locateName('[Content_Types].xml'));
        $this->assertNotFalse($zip->locateName('ppt/presentation.xml'));
        $this->assertNotFalse($zip->locateName('ppt/slides/slide1.xml'));
        $this->assertNotFalse($zip->locateName('ppt/slides/_rels/slide1.xml.rels'));
        $this->assertNotFalse($zip->locateName('ppt/slides/slide2.xml'));
        $this->assertNotFalse($zip->locateName('ppt/slides/_rels/slide2.xml.rels'));
        $this->assertNotFalse($zip->locateName('ppt/media/image1.png'));
        $this->assertStringContainsString(
            'Teks ini tetap bisa diedit',
            (string) $zip->getFromName('ppt/slides/slide1.xml')
        );
        $this->assertStringContainsString('Video YouTube', (string) $zip->getFromName('ppt/slides/slide1.xml'));
        $this->assertStringContainsString('Materi lanjut', (string) $zip->getFromName('ppt/slides/slide1.xml'));
        $this->assertStringContainsString('Bentuk dapat diedit', (string) $zip->getFromName('ppt/slides/slide2.xml'));
        $this->assertStringContainsString('Garis', (string) $zip->getFromName('ppt/slides/slide2.xml'));

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (! str_ends_with($name, '.xml') && ! str_ends_with($name, '.rels')) {
                continue;
            }

            $document = new DOMDocument;
            $this->assertTrue(
                $document->loadXML((string) $zip->getFromIndex($index)),
                "XML PowerPoint tidak valid: {$name}"
            );
        }
        $zip->close();

        auth()->logout();
        $this->get(route('presentations.export.pdf', $presentation))->assertRedirect(route('login'));
        $this->get(route('presentations.export.pptx', $presentation))->assertRedirect(route('login'));
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(
            ['name' => User::ROLE_ADMIN],
            ['display_name' => 'Administrator', 'description' => 'Administrator']
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);
    }
}
