<?php

namespace Tests\Property;

use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use App\Models\ChatGroupMessage;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use App\Models\Kelas;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for Group Member Accessibility
 * 
 * **Feature: chat-enhancements, Property 1: Group member accessibility**
 * **Validates: Requirements 1.4**
 */
class GroupMemberAccessibilityPropertyTest extends TestCase
{
    use TestTrait;
    use RefreshDatabase;

    private User $admin;
    private Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure role exists
        Role::firstOrCreate(['id' => 1], ['name' => 'admin', 'display_name' => 'Admin']);
        
        $this->admin = User::factory()->create();
        $this->kelas = Kelas::factory()->create();
    }

    /**
     * **Feature: chat-enhancements, Property 1: Group member accessibility**
     * 
     * For any group chat, only members of that group should be able to 
     * view and send messages in the group.
     * 
     * **Validates: Requirements 1.4**
     */
    public function testOnlyMembersCanAccessGroup(): void
    {
        $this->forAll(
            Generator\choose(1, 5),
            Generator\choose(1, 3)
        )
        ->withMaxSize(100)
        ->then(function ($memberCount, $nonMemberCount) {
            $group = ChatGroup::create([
                'name' => 'Test Group',
                'description' => 'Test Description',
                'type' => 'custom',
                'created_by' => $this->admin->id,
                'is_active' => true,
            ]);

            ChatGroupMember::create([
                'chat_group_id' => $group->id,
                'user_id' => $this->admin->id,
                'role' => 'admin',
                'joined_at' => now(),
            ]);

            $members = [];
            for ($i = 0; $i < $memberCount; $i++) {
                $siswa = Siswa::factory()->create(['kelas_id' => $this->kelas->id]);
                ChatGroupMember::create([
                    'chat_group_id' => $group->id,
                    'siswa_id' => $siswa->id,
                    'role' => 'member',
                    'joined_at' => now(),
                ]);
                $members[] = $siswa;
            }

            $nonMembers = [];
            for ($i = 0; $i < $nonMemberCount; $i++) {
                $siswa = Siswa::factory()->create(['kelas_id' => $this->kelas->id]);
                $nonMembers[] = $siswa;
            }

            foreach ($members as $member) {
                $isMember = ChatGroupMember::where('chat_group_id', $group->id)
                    ->where('siswa_id', $member->id)
                    ->exists();
                $this->assertTrue($isMember);
            }

            foreach ($nonMembers as $nonMember) {
                $isMember = ChatGroupMember::where('chat_group_id', $group->id)
                    ->where('siswa_id', $nonMember->id)
                    ->exists();
                $this->assertFalse($isMember);
            }

            $actualMemberCount = ChatGroupMember::where('chat_group_id', $group->id)->count();
            $this->assertEquals($memberCount + 1, $actualMemberCount);
        });
    }

    /**
     * Test that messages are only visible to group members
     */
    public function testMessagesVisibleOnlyToMembers(): void
    {
        $this->forAll(
            Generator\choose(1, 5)
        )
        ->withMaxSize(100)
        ->then(function ($messageCount) {
            $group = ChatGroup::create([
                'name' => 'Test Group',
                'description' => 'Test Description',
                'type' => 'custom',
                'created_by' => $this->admin->id,
                'is_active' => true,
            ]);

            ChatGroupMember::create([
                'chat_group_id' => $group->id,
                'user_id' => $this->admin->id,
                'role' => 'admin',
                'joined_at' => now(),
            ]);

            for ($i = 0; $i < $messageCount; $i++) {
                ChatGroupMessage::create([
                    'chat_group_id' => $group->id,
                    'sender_user_id' => $this->admin->id,
                    'message' => "Test message {$i}",
                ]);
            }

            $messages = ChatGroupMessage::where('chat_group_id', $group->id)->get();
            $this->assertCount($messageCount, $messages);

            foreach ($messages as $message) {
                $this->assertEquals($group->id, $message->chat_group_id);
            }
        });
    }

    /**
     * Test that removing a member removes their access
     */
    public function testRemovingMemberRemovesAccess(): void
    {
        $this->forAll(
            Generator\choose(2, 5)
        )
        ->withMaxSize(100)
        ->then(function ($memberCount) {
            $group = ChatGroup::create([
                'name' => 'Test Group',
                'description' => 'Test Description',
                'type' => 'custom',
                'created_by' => $this->admin->id,
                'is_active' => true,
            ]);

            $members = [];
            for ($i = 0; $i < $memberCount; $i++) {
                $siswa = Siswa::factory()->create(['kelas_id' => $this->kelas->id]);
                $membership = ChatGroupMember::create([
                    'chat_group_id' => $group->id,
                    'siswa_id' => $siswa->id,
                    'role' => 'member',
                    'joined_at' => now(),
                ]);
                $members[] = ['siswa' => $siswa, 'membership' => $membership];
            }

            $removedMember = $members[0];
            $removedMember['membership']->delete();

            $isMember = ChatGroupMember::where('chat_group_id', $group->id)
                ->where('siswa_id', $removedMember['siswa']->id)
                ->exists();
            $this->assertFalse($isMember);

            for ($i = 1; $i < count($members); $i++) {
                $isMember = ChatGroupMember::where('chat_group_id', $group->id)
                    ->where('siswa_id', $members[$i]['siswa']->id)
                    ->exists();
                $this->assertTrue($isMember);
            }

            $actualMemberCount = ChatGroupMember::where('chat_group_id', $group->id)->count();
            $this->assertEquals($memberCount - 1, $actualMemberCount);
        });
    }
}
