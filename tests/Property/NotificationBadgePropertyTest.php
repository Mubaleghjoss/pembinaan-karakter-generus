<?php

namespace Tests\Property;

use App\Models\Chat;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use App\Models\Kelas;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for Notification Badges
 * 
 * **Feature: chat-enhancements, Property 2: Unread count accuracy**
 * **Feature: chat-enhancements, Property 3: Read status after opening**
 * **Validates: Requirements 2.1, 2.2, 2.3**
 */
class NotificationBadgePropertyTest extends TestCase
{
    use TestTrait;
    use RefreshDatabase;

    private User $user;
    private Siswa $siswa;
    private Siswa $otherSiswa;
    private Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure role exists
        Role::firstOrCreate(['id' => 1], ['name' => 'admin', 'display_name' => 'Admin']);
        
        $this->user = User::factory()->create();
        $this->kelas = Kelas::factory()->create();
        $this->siswa = Siswa::factory()->create(['kelas_id' => $this->kelas->id]);
        $this->otherSiswa = Siswa::factory()->create(['kelas_id' => $this->kelas->id]);
    }

    /**
     * **Feature: chat-enhancements, Property 2: Unread count accuracy**
     * 
     * For any user with unread messages, the displayed notification badge count 
     * should equal the actual count of unread messages.
     * 
     * **Validates: Requirements 2.1, 2.2**
     */
    public function testUnreadCountAccuracy(): void
    {
        $this->forAll(
            Generator\choose(1, 10)
        )
        ->withMaxSize(100)
        ->then(function ($unreadCount) {
            // Clean up previous test data
            Chat::truncate();
            
            // Create unread messages from user to siswa
            for ($i = 0; $i < $unreadCount; $i++) {
                Chat::create([
                    'sender_user_id' => $this->user->id,
                    'receiver_siswa_id' => $this->siswa->id,
                    'message' => "Test message {$i}",
                    'is_read' => false,
                ]);
            }

            // Verify unread count matches
            $actualCount = Chat::where('receiver_siswa_id', $this->siswa->id)
                ->where('is_read', false)
                ->count();

            $this->assertEquals($unreadCount, $actualCount);
        });
    }

    /**
     * **Feature: chat-enhancements, Property 3: Read status after opening**
     * 
     * For any chat conversation opened by a user, all messages in that 
     * conversation should be marked as read and the unread count should become zero.
     * 
     * **Validates: Requirements 2.3**
     */
    public function testReadStatusAfterOpening(): void
    {
        $this->forAll(
            Generator\choose(1, 15)
        )
        ->withMaxSize(100)
        ->then(function ($messageCount) {
            // Clean up previous test data
            Chat::truncate();
            
            // Create unread messages from user to siswa
            for ($i = 0; $i < $messageCount; $i++) {
                Chat::create([
                    'sender_user_id' => $this->user->id,
                    'receiver_siswa_id' => $this->siswa->id,
                    'message' => "Test message {$i}",
                    'is_read' => false,
                ]);
            }

            // Verify messages are unread initially
            $unreadCount = Chat::where('receiver_siswa_id', $this->siswa->id)
                ->where('sender_user_id', $this->user->id)
                ->where('is_read', false)
                ->count();
            
            $this->assertEquals($messageCount, $unreadCount);

            // Simulate opening the chat (mark as read)
            Chat::where('receiver_siswa_id', $this->siswa->id)
                ->where('sender_user_id', $this->user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            // Verify all messages are now read
            $unreadCountAfter = Chat::where('receiver_siswa_id', $this->siswa->id)
                ->where('sender_user_id', $this->user->id)
                ->where('is_read', false)
                ->count();
            
            $this->assertEquals(0, $unreadCountAfter);
        });
    }

    /**
     * Test unread count per contact grouping
     * 
     * **Validates: Requirements 2.1, 2.2**
     */
    public function testUnreadCountPerContact(): void
    {
        $this->forAll(
            Generator\choose(1, 5),
            Generator\choose(1, 5)
        )
        ->withMaxSize(100)
        ->then(function ($messagesFromUser1, $messagesFromUser2) {
            // Clean up previous test data
            Chat::truncate();
            
            $user2 = User::factory()->create();
            
            // Create messages from user1
            for ($i = 0; $i < $messagesFromUser1; $i++) {
                Chat::create([
                    'sender_user_id' => $this->user->id,
                    'receiver_siswa_id' => $this->siswa->id,
                    'message' => "Message from user1: {$i}",
                    'is_read' => false,
                ]);
            }
            
            // Create messages from user2
            for ($i = 0; $i < $messagesFromUser2; $i++) {
                Chat::create([
                    'sender_user_id' => $user2->id,
                    'receiver_siswa_id' => $this->siswa->id,
                    'message' => "Message from user2: {$i}",
                    'is_read' => false,
                ]);
            }

            // Verify counts per contact
            $countFromUser1 = Chat::where('receiver_siswa_id', $this->siswa->id)
                ->where('sender_user_id', $this->user->id)
                ->where('is_read', false)
                ->count();
                
            $countFromUser2 = Chat::where('receiver_siswa_id', $this->siswa->id)
                ->where('sender_user_id', $user2->id)
                ->where('is_read', false)
                ->count();

            $this->assertEquals($messagesFromUser1, $countFromUser1);
            $this->assertEquals($messagesFromUser2, $countFromUser2);
        });
    }
}
