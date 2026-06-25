<?php

namespace Tests\Feature;

use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use App\Models\Kelas;
use App\Models\PamongPermission;
use App\Models\PamongSiswa;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PamongChatGroupCreationTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;

    protected Role $teacherRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'permissions' => ['*'],
        ]);
        $this->teacherRole = Role::create([
            'name' => 'teacher',
            'display_name' => 'Pamong',
            'permissions' => ['view_students'],
        ]);
    }

    public function test_pamong_with_create_permission_can_create_custom_group(): void
    {
        $kelas = Kelas::create(['nama' => 'Kelas Chat', 'kode_kelas' => 'KGC', 'is_active' => true]);
        $creator = $this->createPamong('pamong_creator');
        $otherPamong = $this->createPamong('pamong_member');
        $siswa = Siswa::create([
            'nis' => 'CHAT_GROUP_' . uniqid(),
            'nama' => 'Siswa Grup Chat',
            'kelas_id' => $kelas->id,
            'is_active' => true,
            'status' => 'active',
        ]);

        PamongSiswa::create([
            'pamong_id' => $creator->id,
            'siswa_id' => $siswa->id,
        ]);

        PamongPermission::create([
            'user_id' => $creator->id,
            'menu_permissions' => ['group_chat'],
            'crud_permissions' => ['group_chat' => ['view', 'create', 'send']],
            'is_excluded' => false,
        ]);

        $response = $this->actingAs($creator)->postJson(route('pamong.chat.groups.store'), [
            'name' => 'Koordinasi Chat',
            'description' => 'Grup koordinasi terbatas',
            'user_ids' => [$otherPamong->id],
            'siswa_ids' => [$siswa->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('group.name', 'Koordinasi Chat')
            ->assertJsonPath('group.members_count', 3);

        $groupId = $response->json('group.id');

        $this->assertDatabaseHas('chat_groups', [
            'id' => $groupId,
            'name' => 'Koordinasi Chat',
            'type' => ChatGroup::TYPE_CUSTOM,
            'created_by' => $creator->id,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('chat_group_members', [
            'chat_group_id' => $groupId,
            'user_id' => $creator->id,
            'role' => ChatGroupMember::ROLE_ADMIN,
        ]);
        $this->assertDatabaseHas('chat_group_members', [
            'chat_group_id' => $groupId,
            'user_id' => $otherPamong->id,
            'role' => ChatGroupMember::ROLE_MEMBER,
        ]);
        $this->assertDatabaseHas('chat_group_members', [
            'chat_group_id' => $groupId,
            'siswa_id' => $siswa->id,
            'role' => ChatGroupMember::ROLE_MEMBER,
        ]);
    }

    public function test_pamong_cannot_add_siswa_outside_scope_to_group(): void
    {
        $kelas = Kelas::create(['nama' => 'Kelas Chat Scope', 'kode_kelas' => 'KCS', 'is_active' => true]);
        $creator = $this->createPamong('pamong_scope');
        $outsideSiswa = Siswa::create([
            'nis' => 'CHAT_OUTSIDE_' . uniqid(),
            'nama' => 'Siswa Luar Scope',
            'kelas_id' => $kelas->id,
            'is_active' => true,
            'status' => 'active',
        ]);

        PamongPermission::create([
            'user_id' => $creator->id,
            'menu_permissions' => ['group_chat'],
            'crud_permissions' => ['group_chat' => ['view', 'create', 'send']],
            'is_excluded' => false,
        ]);

        $response = $this->actingAs($creator)->postJson(route('pamong.chat.groups.store'), [
            'name' => 'Grup Tidak Sah',
            'siswa_ids' => [$outsideSiswa->id],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('chat_groups', ['name' => 'Grup Tidak Sah']);
    }

    public function test_group_creator_can_update_name_members_and_group_admins(): void
    {
        $kelas = Kelas::create(['nama' => 'Kelas Edit Grup', 'kode_kelas' => 'KEG', 'is_active' => true]);
        $creator = $this->createPamong('pamong_edit_creator');
        $adminMember = $this->createPamong('pamong_edit_admin');
        $siswa = Siswa::create([
            'nis' => 'CHAT_EDIT_' . uniqid(),
            'nama' => 'Siswa Edit Grup',
            'kelas_id' => $kelas->id,
            'is_active' => true,
            'status' => 'active',
        ]);

        PamongSiswa::create(['pamong_id' => $creator->id, 'siswa_id' => $siswa->id]);

        PamongPermission::create([
            'user_id' => $creator->id,
            'menu_permissions' => ['group_chat'],
            'crud_permissions' => ['group_chat' => ['view', 'create', 'send']],
            'is_excluded' => false,
        ]);

        $group = ChatGroup::create([
            'name' => 'Nama Lama',
            'description' => null,
            'type' => ChatGroup::TYPE_CUSTOM,
            'created_by' => $creator->id,
            'is_active' => true,
        ]);
        ChatGroupMember::create([
            'chat_group_id' => $group->id,
            'user_id' => $creator->id,
            'role' => ChatGroupMember::ROLE_ADMIN,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($creator)->putJson(route('pamong.chat.groups.update', $group), [
            'name' => 'Nama Baru',
            'description' => 'Deskripsi baru',
            'user_ids' => [$adminMember->id],
            'admin_user_ids' => [$adminMember->id],
            'siswa_ids' => [$siswa->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('group.name', 'Nama Baru')
            ->assertJsonPath('group.members_count', 3);

        $this->assertDatabaseHas('chat_groups', [
            'id' => $group->id,
            'name' => 'Nama Baru',
            'description' => 'Deskripsi baru',
        ]);
        $this->assertDatabaseHas('chat_group_members', [
            'chat_group_id' => $group->id,
            'user_id' => $adminMember->id,
            'role' => ChatGroupMember::ROLE_ADMIN,
        ]);
        $this->assertDatabaseHas('chat_group_members', [
            'chat_group_id' => $group->id,
            'siswa_id' => $siswa->id,
        ]);
    }

    public function test_group_admin_can_manage_members_without_create_permission(): void
    {
        $kelas = Kelas::create(['nama' => 'Kelas Admin Grup', 'kode_kelas' => 'KAG', 'is_active' => true]);
        $creator = $this->createPamong('pamong_group_creator');
        $groupAdmin = $this->createPamong('pamong_group_admin');
        $siswa = Siswa::create([
            'nis' => 'CHAT_ADMIN_' . uniqid(),
            'nama' => 'Siswa Admin Grup',
            'kelas_id' => $kelas->id,
            'is_active' => true,
            'status' => 'active',
        ]);

        PamongSiswa::create(['pamong_id' => $groupAdmin->id, 'siswa_id' => $siswa->id]);

        PamongPermission::create([
            'user_id' => $groupAdmin->id,
            'menu_permissions' => ['group_chat'],
            'crud_permissions' => ['group_chat' => ['view', 'send']],
            'is_excluded' => false,
        ]);

        $group = ChatGroup::create([
            'name' => 'Grup Admin',
            'type' => ChatGroup::TYPE_CUSTOM,
            'created_by' => $creator->id,
            'is_active' => true,
        ]);
        ChatGroupMember::create([
            'chat_group_id' => $group->id,
            'user_id' => $creator->id,
            'role' => ChatGroupMember::ROLE_ADMIN,
            'joined_at' => now(),
        ]);
        ChatGroupMember::create([
            'chat_group_id' => $group->id,
            'user_id' => $groupAdmin->id,
            'role' => ChatGroupMember::ROLE_ADMIN,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($groupAdmin)->putJson(route('pamong.chat.groups.update', $group), [
            'name' => 'Grup Admin Update',
            'user_ids' => [],
            'admin_user_ids' => [],
            'siswa_ids' => [$siswa->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('group.name', 'Grup Admin Update')
            ->assertJsonPath('group.members_count', 3);

        $this->assertDatabaseHas('chat_group_members', [
            'chat_group_id' => $group->id,
            'user_id' => $groupAdmin->id,
            'role' => ChatGroupMember::ROLE_ADMIN,
        ]);
        $this->assertDatabaseHas('chat_group_members', [
            'chat_group_id' => $group->id,
            'siswa_id' => $siswa->id,
        ]);
    }

    public function test_regular_group_member_cannot_manage_group(): void
    {
        $creator = $this->createPamong('pamong_owner_regular');
        $member = $this->createPamong('pamong_regular_member');

        PamongPermission::create([
            'user_id' => $member->id,
            'menu_permissions' => ['group_chat'],
            'crud_permissions' => ['group_chat' => ['view', 'send']],
            'is_excluded' => false,
        ]);

        $group = ChatGroup::create([
            'name' => 'Grup Member Biasa',
            'type' => ChatGroup::TYPE_CUSTOM,
            'created_by' => $creator->id,
            'is_active' => true,
        ]);
        ChatGroupMember::create([
            'chat_group_id' => $group->id,
            'user_id' => $member->id,
            'role' => ChatGroupMember::ROLE_MEMBER,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($member)->putJson(route('pamong.chat.groups.update', $group), [
            'name' => 'Tidak Boleh',
            'user_ids' => [],
            'siswa_ids' => [],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('chat_groups', [
            'id' => $group->id,
            'name' => 'Grup Member Biasa',
        ]);
    }

    protected function createPamong(string $username): User
    {
        return User::create([
            'username' => $username,
            'email' => $username . '@test.com',
            'password' => 'password123',
            'role_id' => $this->teacherRole->id,
            'status' => 'active',
        ]);
    }
}
