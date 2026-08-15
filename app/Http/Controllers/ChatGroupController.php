<?php

namespace App\Http\Controllers;

use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use App\Models\ChatGroupMessage;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatGroupController extends Controller
{
    private function activePamongUsers()
    {
        return User::query()
            ->with('role')
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->whereIn('name', User::operationalRoleNames()));
    }

    private function activeStaffUsers()
    {
        return User::query()
            ->with('role')
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->whereIn('name', User::attendanceRoleNames()));
    }

    private function attachTypeMembers(ChatGroup $group, string $type): void
    {
        if ($type === ChatGroup::TYPE_ALL_PAMONG || $type === ChatGroup::TYPE_ALL_USERS) {
            $userIds = $type === ChatGroup::TYPE_ALL_PAMONG
                ? $this->activePamongUsers()->pluck('id')
                : $this->activeStaffUsers()->pluck('id');

            foreach ($userIds as $userId) {
                ChatGroupMember::firstOrCreate(
                    [
                        'chat_group_id' => $group->id,
                        'user_id' => $userId,
                    ],
                    [
                        'role' => $userId === Auth::id() ? ChatGroupMember::ROLE_ADMIN : ChatGroupMember::ROLE_MEMBER,
                        'joined_at' => now(),
                    ]
                );
            }
        }

        if ($type === ChatGroup::TYPE_ALL_SISWA || $type === ChatGroup::TYPE_ALL_USERS) {
            $siswaIds = Siswa::active()->pluck('id');

            foreach ($siswaIds as $siswaId) {
                ChatGroupMember::firstOrCreate(
                    [
                        'chat_group_id' => $group->id,
                        'siswa_id' => $siswaId,
                    ],
                    [
                        'role' => ChatGroupMember::ROLE_MEMBER,
                        'joined_at' => now(),
                    ]
                );
            }
        }
    }

    /**
     * Display a listing of chat groups
     */
    public function index()
    {
        $groups = ChatGroup::with(['creator', 'members'])
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.chat-groups.index', compact('groups'));
    }

    /**
     * Show the form for creating a new chat group
     */
    public function create()
    {
        $users = $this->activeStaffUsers()->orderBy('username')->get();
        $siswaList = Siswa::active()->with('kelas')->get();

        return view('admin.chat-groups.create', compact('users', 'siswaList'));
    }

    /**
     * Store a newly created chat group
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:custom,all_pamong,all_siswa,all_users',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'siswa_ids' => 'nullable|array',
            'siswa_ids.*' => 'exists:siswa,id',
        ]);

        try {
            DB::beginTransaction();

            $group = ChatGroup::create([
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'created_by' => Auth::id(),
                'is_active' => true,
            ]);

            // Add creator as admin member
            ChatGroupMember::create([
                'chat_group_id' => $group->id,
                'user_id' => Auth::id(),
                'role' => ChatGroupMember::ROLE_ADMIN,
                'joined_at' => now(),
            ]);

            if ($request->type === ChatGroup::TYPE_CUSTOM && $request->user_ids) {
                foreach ($request->user_ids as $userId) {
                    if ($userId != Auth::id()) {
                        ChatGroupMember::create([
                            'chat_group_id' => $group->id,
                            'user_id' => $userId,
                            'role' => ChatGroupMember::ROLE_MEMBER,
                            'joined_at' => now(),
                        ]);
                    }
                }
            }

            if ($request->type === ChatGroup::TYPE_CUSTOM && $request->siswa_ids) {
                foreach ($request->siswa_ids as $siswaId) {
                    ChatGroupMember::create([
                        'chat_group_id' => $group->id,
                        'siswa_id' => $siswaId,
                        'role' => ChatGroupMember::ROLE_MEMBER,
                        'joined_at' => now(),
                    ]);
                }
            }

            if ($request->type !== ChatGroup::TYPE_CUSTOM) {
                $this->attachTypeMembers($group, $request->type);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Grup chat berhasil dibuat',
                'group' => $group->load('members'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Gagal membuat grup chat: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified chat group
     */
    public function show(ChatGroup $chatGroup)
    {
        $chatGroup->load(['members.user', 'members.siswa', 'creator']);
        
        return view('admin.chat-groups.show', compact('chatGroup'));
    }

    /**
     * Show the form for editing the specified chat group
     */
    public function edit(ChatGroup $chatGroup)
    {
        $users = $this->activeStaffUsers()->orderBy('username')->get();
        $siswaList = Siswa::active()->with('kelas')->get();
        $chatGroup->load(['members.user', 'members.siswa']);

        return view('admin.chat-groups.edit', compact('chatGroup', 'users', 'siswaList'));
    }

    /**
     * Update the specified chat group
     */
    public function update(Request $request, ChatGroup $chatGroup): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:custom,all_pamong,all_siswa,all_users',
            'is_active' => 'boolean',
        ]);

        $chatGroup->update([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grup chat berhasil diperbarui',
            'group' => $chatGroup,
        ]);
    }

    /**
     * Remove the specified chat group
     */
    public function destroy(ChatGroup $chatGroup): JsonResponse
    {
        try {
            $chatGroup->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Grup chat berhasil dinonaktifkan',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Gagal menonaktifkan grup chat',
            ], 500);
        }
    }

    /**
     * Add all pamong (users) to the group
     */
    public function addAllPamong(ChatGroup $chatGroup): JsonResponse
    {
        $users = $this->activePamongUsers()->get();
        $existingUserIds = $chatGroup->members()->whereNotNull('user_id')->pluck('user_id')->toArray();

        $added = 0;
        foreach ($users as $user) {
            if (!in_array($user->id, $existingUserIds)) {
                ChatGroupMember::create([
                    'chat_group_id' => $chatGroup->id,
                    'user_id' => $user->id,
                    'role' => ChatGroupMember::ROLE_MEMBER,
                    'joined_at' => now(),
                ]);
                $added++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$added} pamong berhasil ditambahkan ke grup",
        ]);
    }

    /**
     * Add all siswa to the group
     */
    public function addAllSiswa(ChatGroup $chatGroup): JsonResponse
    {
        $siswaList = Siswa::active()->get();
        $existingSiswaIds = $chatGroup->members()->whereNotNull('siswa_id')->pluck('siswa_id')->toArray();

        $added = 0;
        foreach ($siswaList as $siswa) {
            if (!in_array($siswa->id, $existingSiswaIds)) {
                ChatGroupMember::create([
                    'chat_group_id' => $chatGroup->id,
                    'siswa_id' => $siswa->id,
                    'role' => ChatGroupMember::ROLE_MEMBER,
                    'joined_at' => now(),
                ]);
                $added++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$added} siswa berhasil ditambahkan ke grup",
        ]);
    }

    /**
     * Add all users (pamong + siswa) to the group
     */
    public function addAllUsers(ChatGroup $chatGroup): JsonResponse
    {
        $this->addAllPamong($chatGroup);
        $this->addAllSiswa($chatGroup);

        return response()->json([
            'success' => true,
            'message' => 'Semua pengguna berhasil ditambahkan ke grup',
        ]);
    }

    /**
     * Get messages for a group
     */
    public function getMessages(ChatGroup $chatGroup): JsonResponse
    {
        $messages = ChatGroupMessage::where('chat_group_id', $chatGroup->id)
            ->with(['senderUser', 'senderSiswa'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'messages' => $messages->map(fn($m) => [
                'id' => $m->id,
                'message' => $m->message,
                'attachment_url' => $m->attachment_url,
                'sender_name' => $m->sender_name,
                'is_mine' => $m->sender_user_id === Auth::id(),
                'created_at' => $m->created_at->format('H:i'),
                'date' => $m->created_at->format('d M Y'),
            ]),
        ]);
    }

    /**
     * Send a message to the group
     */
    public function sendMessage(Request $request, ChatGroup $chatGroup): JsonResponse
    {
        $request->validate([
            'message' => 'nullable|string|max:1000',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat/group-attachments', 'public');
        }

        // Determine sender type
        $senderUserId = null;
        $senderSiswaId = null;

        if (Auth::guard('siswa')->check()) {
            $senderSiswaId = Auth::guard('siswa')->id();
        } else {
            $senderUserId = Auth::id();
        }

        $message = ChatGroupMessage::create([
            'chat_group_id' => $chatGroup->id,
            'sender_user_id' => $senderUserId,
            'sender_siswa_id' => $senderSiswaId,
            'message' => $request->message,
            'attachment_path' => $attachmentPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'message' => $message->message,
                'attachment_url' => $message->attachment_url,
                'sender_name' => $message->sender_name,
                'is_mine' => true,
                'created_at' => $message->created_at->format('H:i'),
                'date' => $message->created_at->format('d M Y'),
            ],
        ]);
    }

    /**
     * Remove a member from the group
     */
    public function removeMember(ChatGroup $chatGroup, ChatGroupMember $member): JsonResponse
    {
        if ($member->chat_group_id !== $chatGroup->id) {
            return response()->json([
                'success' => false,
                'error' => 'Member tidak ditemukan di grup ini',
            ], 404);
        }

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Member berhasil dihapus dari grup',
        ]);
    }
}
