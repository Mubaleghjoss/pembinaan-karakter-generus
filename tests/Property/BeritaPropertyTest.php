<?php

namespace Tests\Property;

use App\Models\Berita;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Property-based tests for Berita Management functionality.
 *
 * **Feature: qr-generate-berita, Properties 4-9**
 * **Validates: Requirements 3.1, 3.3, 3.4, 3.5, 4.1, 4.2, 4.4**
 */
class BeritaPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup role sebelum setiap test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    /**
     * Seed roles yang diperlukan untuk test.
     */
    private function seedRoles(): void
    {
        if (Role::count() === 0) {
            Role::create([
                'id' => 1,
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full system access',
                'permissions' => ['view_students', 'manage_students'],
                'is_active' => true,
            ]);
        }
    }

    /**
     * **Feature: qr-generate-berita, Property 4: Berita creation persists all fields**
     * **Validates: Requirements 3.1**
     *
     * Property: For any valid berita data (non-empty title and content),
     * after creation, querying the berita should return the same title, content, and status.
     */
    public function test_berita_creation_persists_all_fields(): void
    {
        $user = User::factory()->create(['role_id' => 1]);
        $statuses = ['draft', 'published'];

        // Test dengan berbagai kombinasi data valid
        for ($i = 0; $i < 10; $i++) {
            $judul = 'Judul Berita Test ' . $i . ' ' . Str::random(10);
            $isi = 'Isi berita test ' . $i . '. Lorem ipsum dolor sit amet ' . Str::random(50);
            $status = $statuses[$i % 2];

            $berita = Berita::create([
                'judul' => $judul,
                'isi' => $isi,
                'status' => $status,
                'author_id' => $user->id,
                'published_at' => $status === 'published' ? now() : null,
            ]);

            // Query kembali dari database
            $retrieved = Berita::find($berita->id);

            $this->assertNotNull($retrieved, 'Berita harus tersimpan di database');
            $this->assertEquals($judul, $retrieved->judul, 'Judul harus sama');
            $this->assertEquals($isi, $retrieved->isi, 'Isi harus sama');
            $this->assertEquals($status, $retrieved->status, 'Status harus sama');
            $this->assertEquals($user->id, $retrieved->author_id, 'Author ID harus sama');
        }
    }


    /**
     * **Feature: qr-generate-berita, Property 5: Berita validation rejects empty fields**
     * **Validates: Requirements 3.5**
     *
     * Property: For any berita data with empty title or empty content,
     * the creation should fail validation.
     */
    public function test_berita_validation_rejects_empty_title(): void
    {
        $user = User::factory()->create(['role_id' => 1]);
        $this->actingAs($user);

        // Test dengan berbagai variasi judul kosong
        $emptyTitles = ['', '   ', "\t", "\n", '    '];

        foreach ($emptyTitles as $emptyTitle) {
            $response = $this->post(route('berita.store'), [
                'judul' => $emptyTitle,
                'isi' => 'Isi berita yang valid',
                'status' => 'draft',
            ]);

            $response->assertSessionHasErrors('judul');
        }
    }

    /**
     * **Feature: qr-generate-berita, Property 5: Berita validation rejects empty fields**
     * **Validates: Requirements 3.5**
     *
     * Property: For any berita data with empty content, the creation should fail validation.
     */
    public function test_berita_validation_rejects_empty_content(): void
    {
        $user = User::factory()->create(['role_id' => 1]);
        $this->actingAs($user);

        // Test dengan berbagai variasi isi kosong
        $emptyContents = ['', '   ', "\t", "\n"];

        foreach ($emptyContents as $emptyContent) {
            $response = $this->post(route('berita.store'), [
                'judul' => 'Judul yang valid',
                'isi' => $emptyContent,
                'status' => 'draft',
            ]);

            $response->assertSessionHasErrors('isi');
        }
    }

    /**
     * **Feature: qr-generate-berita, Property 6: Berita update persists changes**
     * **Validates: Requirements 3.3**
     *
     * Property: For any existing berita and valid update data,
     * after update, the berita should reflect the new values.
     */
    public function test_berita_update_persists_changes(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        for ($i = 0; $i < 10; $i++) {
            // Buat berita awal
            $berita = Berita::factory()->create(['author_id' => $user->id]);
            $originalUpdatedAt = $berita->updated_at;

            // Data update baru
            $statuses = ['draft', 'published', 'archived'];
            $newJudul = 'Updated Judul ' . $i . ' ' . Str::random(10);
            $newIsi = 'Updated isi ' . $i . '. Lorem ipsum ' . Str::random(50);
            $newStatus = $statuses[$i % 3];

            // Tunggu sebentar agar timestamp berbeda
            sleep(1);

            // Update berita
            $berita->update([
                'judul' => $newJudul,
                'isi' => $newIsi,
                'status' => $newStatus,
            ]);

            // Refresh dan verifikasi
            $berita->refresh();

            $this->assertEquals($newJudul, $berita->judul, 'Judul harus terupdate');
            $this->assertEquals($newIsi, $berita->isi, 'Isi harus terupdate');
            $this->assertEquals($newStatus, $berita->status, 'Status harus terupdate');
            $this->assertTrue(
                $berita->updated_at->greaterThan($originalUpdatedAt),
                'updated_at harus berubah setelah update'
            );
        }
    }

    /**
     * **Feature: qr-generate-berita, Property 7: Berita deletion removes record**
     * **Validates: Requirements 3.4**
     *
     * Property: For any existing berita, after deletion,
     * querying by that ID should return null/not found.
     */
    public function test_berita_deletion_removes_record(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        for ($i = 0; $i < 10; $i++) {
            // Buat berita
            $berita = Berita::factory()->create(['author_id' => $user->id]);
            $beritaId = $berita->id;

            // Verifikasi berita ada
            $this->assertNotNull(Berita::find($beritaId), 'Berita harus ada sebelum dihapus');

            // Hapus berita
            $berita->delete();

            // Verifikasi berita sudah tidak ada
            $this->assertNull(Berita::find($beritaId), 'Berita harus null setelah dihapus');
        }
    }


    /**
     * **Feature: qr-generate-berita, Property 8: Published berita visibility**
     * **Validates: Requirements 4.1, 4.2**
     *
     * Property: For any berita with status 'published', it should appear in public queries.
     * For any berita with status 'draft', it should not appear in public queries.
     */
    public function test_published_berita_visibility(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        // Buat beberapa berita dengan status berbeda
        $publishedBerita = [];
        $draftBerita = [];
        $archivedBerita = [];

        for ($i = 0; $i < 5; $i++) {
            $publishedBerita[] = Berita::factory()->published()->create(['author_id' => $user->id]);
            $draftBerita[] = Berita::factory()->draft()->create(['author_id' => $user->id]);
            $archivedBerita[] = Berita::factory()->archived()->create(['author_id' => $user->id]);
        }

        // Query dengan scope published
        $publicBerita = Berita::published()->get();

        // Verifikasi semua published berita muncul
        foreach ($publishedBerita as $berita) {
            $this->assertTrue(
                $publicBerita->contains('id', $berita->id),
                'Published berita harus muncul di public query'
            );
        }

        // Verifikasi draft berita tidak muncul
        foreach ($draftBerita as $berita) {
            $this->assertFalse(
                $publicBerita->contains('id', $berita->id),
                'Draft berita tidak boleh muncul di public query'
            );
        }

        // Verifikasi archived berita tidak muncul
        foreach ($archivedBerita as $berita) {
            $this->assertFalse(
                $publicBerita->contains('id', $berita->id),
                'Archived berita tidak boleh muncul di public query'
            );
        }
    }

    /**
     * **Feature: qr-generate-berita, Property 8: Published berita visibility**
     * **Validates: Requirements 4.1, 4.2**
     *
     * Property: Published scope should only return berita with published_at in the past.
     */
    public function test_published_scope_respects_published_at_date(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        // Berita published dengan tanggal di masa lalu
        $pastPublished = Berita::factory()->create([
            'author_id' => $user->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // Berita published dengan tanggal di masa depan
        $futurePublished = Berita::factory()->create([
            'author_id' => $user->id,
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $publicBerita = Berita::published()->get();

        $this->assertTrue(
            $publicBerita->contains('id', $pastPublished->id),
            'Berita dengan published_at di masa lalu harus muncul'
        );

        $this->assertFalse(
            $publicBerita->contains('id', $futurePublished->id),
            'Berita dengan published_at di masa depan tidak boleh muncul'
        );
    }

    /**
     * **Feature: qr-generate-berita, Property 9: Berita filter by status**
     * **Validates: Requirements 4.4**
     *
     * Property: For any status filter value, the filtered results
     * should only contain berita with matching status.
     */
    public function test_berita_filter_by_status(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        // Buat berita dengan berbagai status
        $statuses = ['draft', 'published', 'archived'];
        $beritaByStatus = [];

        foreach ($statuses as $status) {
            $beritaByStatus[$status] = [];
            for ($i = 0; $i < 3; $i++) {
                $beritaByStatus[$status][] = Berita::factory()->create([
                    'author_id' => $user->id,
                    'status' => $status,
                    'published_at' => $status === 'published' ? now() : null,
                ]);
            }
        }

        // Test filter untuk setiap status
        foreach ($statuses as $filterStatus) {
            $filtered = Berita::where('status', $filterStatus)->get();

            // Semua hasil harus memiliki status yang sesuai
            foreach ($filtered as $berita) {
                $this->assertEquals(
                    $filterStatus,
                    $berita->status,
                    "Filtered berita harus memiliki status '{$filterStatus}'"
                );
            }

            // Jumlah hasil harus sesuai dengan yang dibuat
            $this->assertCount(
                count($beritaByStatus[$filterStatus]),
                $filtered,
                "Jumlah berita dengan status '{$filterStatus}' harus sesuai"
            );
        }
    }

    /**
     * **Feature: qr-generate-berita, Property 9: Berita filter by status**
     * **Validates: Requirements 4.4**
     *
     * Property: When no status filter is applied, all berita should be returned.
     */
    public function test_berita_no_filter_returns_all(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        // Buat berita dengan berbagai status
        $totalCount = 0;
        foreach (['draft', 'published', 'archived'] as $status) {
            $count = rand(2, 4);
            Berita::factory()->count($count)->create([
                'author_id' => $user->id,
                'status' => $status,
                'published_at' => $status === 'published' ? now() : null,
            ]);
            $totalCount += $count;
        }

        // Query tanpa filter
        $allBerita = Berita::all();

        $this->assertCount(
            $totalCount,
            $allBerita,
            'Query tanpa filter harus mengembalikan semua berita'
        );
    }
}
