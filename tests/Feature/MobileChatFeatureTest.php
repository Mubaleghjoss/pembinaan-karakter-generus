<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\PamongSiswa;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileChatFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswa_can_list_contacts_and_send_to_assigned_pamong(): void
    {
        [$siswa, $pamong] = $this->assignedPair();
        $token = $siswa->createToken('test', ['siswa', 'mobile-chat'])->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/mobile/chat/contacts')
            ->assertOk()
            ->assertJsonPath('data.0.id', $pamong->id)
            ->assertJsonPath('data.0.type', 'pamong');

        $this->withToken($token)->postJson('/api/v1/mobile/chat/messages', [
            'type' => 'pamong',
            'target_id' => $pamong->id,
            'message' => '  Assalamu alaikum  ',
        ])->assertCreated()
            ->assertJsonPath('data.message', 'Assalamu alaikum');

        $this->assertDatabaseHas('chats', [
            'sender_siswa_id' => $siswa->id,
            'receiver_user_id' => $pamong->id,
            'message' => 'Assalamu alaikum',
            'is_read' => false,
        ]);
    }

    public function test_graduated_student_cannot_create_mobile_chat(): void
    {
        $siswa = Siswa::factory()->create(['status' => 'graduated', 'is_active' => true]);
        $pamong = $this->pamong();
        PamongSiswa::create(['pamong_id' => $pamong->id, 'siswa_id' => $siswa->id]);
        $token = $siswa->createToken('test', ['siswa', 'mobile-chat'])->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/mobile/chat/messages', [
            'type' => 'pamong',
            'target_id' => $pamong->id,
            'message' => 'Tidak boleh',
        ])->assertForbidden();

        $this->assertDatabaseCount('chats', 0);
    }

    public function test_blank_message_is_rejected_without_creating_chat(): void
    {
        [$siswa, $pamong] = $this->assignedPair();
        $token = $siswa->createToken('test', ['siswa', 'mobile-chat'])->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/mobile/chat/messages', [
            'type' => 'pamong',
            'target_id' => $pamong->id,
            'message' => " \t\n ",
        ])->assertStatus(422)
            ->assertJsonPath('errors.message.0', 'Pesan tidak boleh kosong.');

        $this->assertDatabaseCount('chats', 0);
    }

    public function test_siswa_cannot_chat_with_unassigned_pamong(): void
    {
        $siswa = Siswa::factory()->create();
        $pamong = $this->pamong();
        $token = $siswa->createToken('test', ['siswa', 'mobile-chat'])->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/mobile/chat/messages', [
            'type' => 'pamong',
            'target_id' => $pamong->id,
            'message' => 'Tidak boleh',
        ])->assertForbidden();
    }

    public function test_mark_read_only_updates_inbound_messages_for_authorized_contact(): void
    {
        [$siswa, $pamong] = $this->assignedPair();
        $otherSiswa = Siswa::factory()->create();
        $token = $siswa->createToken('test', ['siswa', 'mobile-chat'])->plainTextToken;

        $inbound = Chat::create([
            'sender_user_id' => $pamong->id,
            'receiver_siswa_id' => $siswa->id,
            'message' => 'Masuk',
            'message_type' => Chat::TYPE_TEXT,
            'is_read' => false,
        ]);
        $outbound = Chat::create([
            'sender_siswa_id' => $siswa->id,
            'receiver_user_id' => $pamong->id,
            'message' => 'Keluar',
            'message_type' => Chat::TYPE_TEXT,
            'is_read' => false,
        ]);
        $unrelated = Chat::create([
            'sender_user_id' => $pamong->id,
            'receiver_siswa_id' => $otherSiswa->id,
            'message' => 'Lain',
            'message_type' => Chat::TYPE_TEXT,
            'is_read' => false,
        ]);

        $this->withToken($token)->postJson('/api/v1/mobile/chat/messages/read', [
            'type' => 'pamong',
            'target_id' => $pamong->id,
        ])->assertOk()->assertJsonPath('data.updated', 1);

        $this->assertTrue($inbound->fresh()->is_read);
        $this->assertFalse($outbound->fresh()->is_read);
        $this->assertFalse($unrelated->fresh()->is_read);
    }

    public function test_ortu_token_cannot_use_mobile_chat(): void
    {
        $siswa = Siswa::factory()->create([
            'ortu_username' => 'ortu-test',
            'ortu_password' => Hash::make('password'),
        ]);
        $token = $siswa->createToken('ortu-mobile', ['ortu'])->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/mobile/chat/contacts')
            ->assertForbidden()
            ->assertJsonPath('message', 'Invalid ability provided.');
    }

    public function test_token_without_mobile_chat_ability_is_rejected(): void
    {
        $siswa = Siswa::factory()->create();
        $token = $siswa->createToken('test', ['siswa'])->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/mobile/chat/contacts')
            ->assertForbidden();
    }

    private function assignedPair(): array
    {
        $siswa = Siswa::factory()->create();
        $pamong = $this->pamong();
        PamongSiswa::create(['pamong_id' => $pamong->id, 'siswa_id' => $siswa->id]);

        return [$siswa, $pamong];
    }

    private function pamong(): User
    {
        $role = Role::query()->firstOrCreate([
            'name' => User::ROLE_TEACHER,
        ], [
            'display_name' => 'Pamong',
            'permissions' => ['view_students'],
            'is_active' => true,
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
