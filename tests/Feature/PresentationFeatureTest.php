<?php

namespace Tests\Feature;

use App\Models\Presentation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Vite;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

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
            ->assertSee('data-add-diagram', false);

        $canvas = [
            'version' => 1,
            'width' => 2400,
            'height' => 1400,
            'frames' => [[
                'id' => 'frame-pembuka',
                'title' => 'Pembuka',
                'x' => 120,
                'y' => 160,
                'width' => 800,
                'height' => 450,
                'backgroundColor' => '#ffffff',
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
                    'diagramType' => 'process',
                    'items' => ['Ilmu', 'Amal', 'Teladan'],
                    'color' => '#047857',
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

        $this->get(route('public.presentations.show', $presentation))->assertNotFound();

        $this->actingAs($admin)
            ->patch(route('presentations.publish', $presentation))
            ->assertRedirect();

        $this->get(route('public.presentations.show', $presentation->fresh()))
            ->assertOk()
            ->assertSee('presentation-viewer', false)
            ->assertSee('Akhlakul Karimah')
            ->assertDontSee(route('presentations.edit', $presentation), false);
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
