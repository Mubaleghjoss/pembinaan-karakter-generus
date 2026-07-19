<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiswaChatAlignmentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_sent_message_remains_mine_after_messages_are_reloaded(): void
    {
        $siswa = Siswa::factory()->create();
        $pamong = User::factory()->create();

        $sentResponse = $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.chat.send'), [
                'type' => 'pamong',
                'target_id' => $pamong->id,
                'message' => 'Pesan dari siswa',
            ]);

        $sentResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message.is_mine', true);

        $sentId = $sentResponse->json('message.id');

        $reply = Chat::create([
            'sender_user_id' => $pamong->id,
            'receiver_siswa_id' => $siswa->id,
            'message' => 'Balasan dari pamong',
            'message_type' => 'text',
        ]);

        $messages = $this->getJson(route('siswa.chat.messages', [
            'type' => 'pamong',
            'target_id' => $pamong->id,
        ]));

        $messages
            ->assertOk()
            ->assertJsonFragment([
                'id' => $sentId,
                'is_mine' => true,
            ])
            ->assertJsonFragment([
                'id' => $reply->id,
                'is_mine' => false,
            ]);
    }

    public function test_chat_foreign_keys_are_cast_to_integers(): void
    {
        $chat = new Chat([
            'sender_siswa_id' => '12',
            'sender_user_id' => '13',
            'receiver_siswa_id' => '14',
            'receiver_user_id' => '15',
        ]);

        $this->assertSame(12, $chat->sender_siswa_id);
        $this->assertSame(13, $chat->sender_user_id);
        $this->assertSame(14, $chat->receiver_siswa_id);
        $this->assertSame(15, $chat->receiver_user_id);
    }
}
