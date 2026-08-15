<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PamongChatController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $siswaList = $this->visibleSiswaQuery($user)
            ->orderBy('nama')
            ->get();
        $recipientScopeLabel = $user->isAdmin() ? 'siswa aktif' : 'siswa binaan Anda';

        $groups = ChatGroup::query()
            ->where('is_active', true)
            ->when(! $user->hasRole('admin'), function ($query) use ($user) {
                $query->whereHas('members', function ($memberQuery) use ($user) {
                    $memberQuery->where('user_id', $user->id);
                });
            })
            ->withCount('members')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn (ChatGroup $group) => $this->formatGroupForChat($group, $user))
            ->values();

        $canCreateGroupChat = $user->hasPamongCrudPermission('group_chat', 'create');
        $groupUserCandidates = $this->groupUserCandidateQuery($user)
            ->get()
            ->map(fn (User $candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->name ?: $candidate->username,
                'username' => $candidate->username,
                'role_label' => $candidate->operationalRoleLabel(),
            ])
            ->values();
        $groupSiswaCandidates = $siswaList
            ->map(fn (Siswa $siswa) => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'nama' => $siswa->nama,
                'kelas' => $siswa->school_grade ? [
                    'id' => $siswa->school_grade,
                    'nama' => $siswa->school_grade_label,
                ] : null,
            ])
            ->values();

        return view('pamong.chat.index', compact(
            'siswaList',
            'groups',
            'recipientScopeLabel',
            'canCreateGroupChat',
            'groupUserCandidates',
            'groupSiswaCandidates'
        ));
    }

    public function getMessages(Request $request): JsonResponse
    {
        $user = Auth::user();
        $siswaId = $request->integer('siswa_id');

        if (! $this->canChatWithSiswa($user, $siswaId)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda hanya dapat membuka chat siswa binaan.',
            ], 403);
        }

        $messages = Chat::where(function ($q) use ($user, $siswaId) {
            $q->where(function ($sub) use ($user, $siswaId) {
                $sub->where('sender_user_id', $user->id)
                    ->where('receiver_siswa_id', $siswaId);
            })->orWhere(function ($sub) use ($user, $siswaId) {
                $sub->where('sender_siswa_id', $siswaId)
                    ->where('receiver_user_id', $user->id);
            });
        })->orderBy('created_at', 'asc')->get();

        Chat::where('sender_siswa_id', $siswaId)
            ->where('receiver_user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'messages' => $messages->map(fn ($m) => [
                'id' => $m->id,
                'message' => $m->message,
                'message_type' => $m->message_type ?? 'text',
                'attachment_url' => $m->attachment_url,
                'caption' => $m->caption,
                'is_mine' => $m->sender_user_id === $user->id,
                'sender_name' => $m->sender_name,
                'created_at' => $m->created_at->format('H:i'),
                'date' => $m->created_at->format('d M Y'),
                'date_label' => $this->chatDateLabel($m->created_at),
                'full_date' => $m->created_at->format('Y-m-d'),
            ]),
        ]);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'siswa_id' => 'required|integer|exists:siswa,id',
            'message' => 'nullable|string|max:1000',
            'message_type' => 'nullable|in:text,image,link',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = Auth::user();
        if (! $this->canChatWithSiswa($user, $request->integer('siswa_id'))) {
            return response()->json([
                'success' => false,
                'message' => 'Anda hanya dapat mengirim chat ke siswa binaan.',
            ], 403);
        }

        $messageType = 'text';
        $attachmentPath = null;
        $caption = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat/attachments', 'public');
            $messageType = 'image';
            if ($request->message) {
                $caption = $request->message;
            }
        }

        $messageContent = $messageType === 'image' ? null : $request->message;

        $chat = Chat::create([
            'sender_user_id' => $user->id,
            'receiver_siswa_id' => $request->integer('siswa_id'),
            'message' => $messageContent,
            'message_type' => $messageType,
            'attachment_path' => $attachmentPath,
            'caption' => $caption,
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $chat->id,
                'message' => $chat->message,
                'message_type' => $chat->message_type,
                'attachment_url' => $chat->attachment_url,
                'caption' => $chat->caption,
                'is_mine' => true,
                'sender_name' => $user->username,
                'created_at' => $chat->created_at->format('H:i'),
                'date' => $chat->created_at->format('d M Y'),
                'date_label' => $this->chatDateLabel($chat->created_at),
                'full_date' => $chat->created_at->format('Y-m-d'),
            ],
        ]);
    }

    public function getUnreadCount(): JsonResponse
    {
        $user = Auth::user();
        $allowedSiswaIds = $this->visibleSiswaQuery($user)->pluck('id');

        $count = Chat::where('receiver_user_id', $user->id)
            ->where('is_read', false)
            ->whereIn('sender_siswa_id', $allowedSiswaIds)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function getUnreadCountPerContact(): JsonResponse
    {
        $user = Auth::user();
        $allowedSiswaIds = $this->visibleSiswaQuery($user)->pluck('id');

        $siswaUnread = Chat::where('receiver_user_id', $user->id)
            ->where('is_read', false)
            ->whereNotNull('sender_siswa_id')
            ->whereIn('sender_siswa_id', $allowedSiswaIds)
            ->selectRaw('sender_siswa_id as contact_id, COUNT(*) as unread_count')
            ->groupBy('sender_siswa_id')
            ->pluck('unread_count', 'contact_id')
            ->mapWithKeys(fn ($count, $contactId) => ["siswa_{$contactId}" => $count]);

        return response()->json(['unread_counts' => $siswaUnread]);
    }

    public function broadcastForm()
    {
        $user = Auth::user();
        $siswaCount = $this->visibleSiswaQuery($user)->count();

        return view('pamong.chat.broadcast', compact('siswaCount'));
    }

    public function sendBroadcast(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'nullable|string|max:1000',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = Auth::user();
        $messageType = 'text';
        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat/attachments', 'public');
            $messageType = 'image';
        }

        if ($messageType === 'text' && $request->message) {
            $trimmedMessage = trim($request->message);
            if (preg_match('/^https?:\/\/[^\s]+$/', $trimmedMessage)) {
                $messageType = 'link';
            }
        }

        $now = now();
        $sentCount = 0;

        $this->visibleSiswaQuery($user)
            ->select('id')
            ->orderBy('id')
            ->chunk(500, function ($siswaChunk) use ($user, $request, $messageType, $attachmentPath, $now, &$sentCount) {
                $payload = $siswaChunk->map(function ($siswa) use ($user, $request, $messageType, $attachmentPath, $now) {
                    return [
                        'sender_user_id' => $user->id,
                        'receiver_siswa_id' => $siswa->id,
                        'message' => $request->message,
                        'message_type' => $messageType,
                        'attachment_path' => $attachmentPath,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->all();

                if (! empty($payload)) {
                    Chat::insert($payload);
                    $sentCount += count($payload);
                }
            });

        return response()->json([
            'success' => true,
            'message' => "Pesan berhasil dikirim ke {$sentCount} siswa.",
            'sent_count' => $sentCount,
        ]);
    }

    public function storeGroup(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'siswa_ids' => ['nullable', 'array'],
            'siswa_ids.*' => ['integer', 'exists:siswa,id'],
            'admin_user_ids' => ['nullable', 'array'],
            'admin_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $userIds = $this->normalizedIdCollection($validated['user_ids'] ?? [])
            ->reject(fn (int $id) => $id === $user->id)
            ->values();
        $adminUserIds = $this->normalizedIdCollection($validated['admin_user_ids'] ?? [])
            ->reject(fn (int $id) => $id === $user->id)
            ->values();
        $userIds = $userIds->merge($adminUserIds)->unique()->values();
        $siswaIds = $this->normalizedIdCollection($validated['siswa_ids'] ?? []);

        if ($userIds->isEmpty() && $siswaIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Pilih minimal satu anggota grup.',
            ], 422);
        }

        $allowedUserIds = $this->groupUserCandidateQuery($user)->pluck('id');
        if ($userIds->diff($allowedUserIds)->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada admin atau pamong yang tidak dapat dipilih.',
            ], 403);
        }

        $allowedSiswaIds = $this->visibleSiswaQuery($user)->pluck('id');
        if ($siswaIds->diff($allowedSiswaIds)->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada siswa yang tidak termasuk scope akses Anda.',
            ], 403);
        }

        try {
            $group = DB::transaction(function () use ($validated, $user, $userIds, $adminUserIds, $siswaIds) {
                $group = ChatGroup::create([
                    'name' => trim($validated['name']),
                    'description' => trim($validated['description'] ?? '') ?: null,
                    'type' => ChatGroup::TYPE_CUSTOM,
                    'created_by' => $user->id,
                    'is_active' => true,
                ]);

                ChatGroupMember::create([
                    'chat_group_id' => $group->id,
                    'user_id' => $user->id,
                    'role' => ChatGroupMember::ROLE_ADMIN,
                    'joined_at' => now(),
                ]);

                foreach ($userIds as $userId) {
                    ChatGroupMember::create([
                        'chat_group_id' => $group->id,
                    'user_id' => $userId,
                    'role' => $adminUserIds->contains($userId)
                        ? ChatGroupMember::ROLE_ADMIN
                        : ChatGroupMember::ROLE_MEMBER,
                    'joined_at' => now(),
                ]);
            }

                foreach ($siswaIds as $siswaId) {
                    ChatGroupMember::create([
                        'chat_group_id' => $group->id,
                        'siswa_id' => $siswaId,
                        'role' => ChatGroupMember::ROLE_MEMBER,
                        'joined_at' => now(),
                    ]);
                }

                return $group->fresh()->loadCount('members');
            });

            return response()->json([
                'success' => true,
                'message' => 'Grup chat berhasil dibuat.',
                'group' => $this->formatGroupForChat($group, $user),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat grup chat.',
            ], 500);
        }
    }

    public function editGroup(ChatGroup $chatGroup): JsonResponse
    {
        $user = Auth::user();

        if (! $this->canManageGroup($user, $chatGroup)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan admin grup ini.',
            ], 403);
        }

        $chatGroup->load(['members.user.role', 'members.siswa.kelas'])->loadCount('members');

        return response()->json([
            'success' => true,
            'group' => $this->formatGroupForChat($chatGroup, $user, true),
        ]);
    }

    public function updateGroup(Request $request, ChatGroup $chatGroup): JsonResponse
    {
        $user = Auth::user();

        if (! $this->canManageGroup($user, $chatGroup)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan admin grup ini.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'siswa_ids' => ['nullable', 'array'],
            'siswa_ids.*' => ['integer', 'exists:siswa,id'],
            'admin_user_ids' => ['nullable', 'array'],
            'admin_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $userIds = $this->normalizedIdCollection($validated['user_ids'] ?? []);
        $adminUserIds = $this->normalizedIdCollection($validated['admin_user_ids'] ?? []);
        $siswaIds = $this->normalizedIdCollection($validated['siswa_ids'] ?? []);

        $protectedAdminIds = collect([$chatGroup->created_by]);
        if (! $user->isAdmin()) {
            $protectedAdminIds->push($user->id);
        }
        $protectedAdminIds = $protectedAdminIds
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $userIds = $userIds->merge($adminUserIds)->merge($protectedAdminIds)->unique()->values();
        $adminUserIds = $adminUserIds->merge($protectedAdminIds)->unique()->values();

        $allowedUserIds = $this->groupUserCandidateQuery($user)
            ->pluck('id')
            ->merge($protectedAdminIds)
            ->unique()
            ->values();
        if ($userIds->diff($allowedUserIds)->isNotEmpty() || $adminUserIds->diff($allowedUserIds)->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada admin atau pamong yang tidak dapat dipilih.',
            ], 403);
        }

        $allowedSiswaIds = $this->visibleSiswaQuery($user)->pluck('id');
        if ($siswaIds->diff($allowedSiswaIds)->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada siswa yang tidak termasuk scope akses Anda.',
            ], 403);
        }

        try {
            $group = DB::transaction(function () use ($validated, $chatGroup, $userIds, $adminUserIds, $siswaIds) {
                $chatGroup->update([
                    'name' => trim($validated['name']),
                    'description' => trim($validated['description'] ?? '') ?: null,
                ]);

                ChatGroupMember::query()
                    ->where('chat_group_id', $chatGroup->id)
                    ->whereNotNull('user_id')
                    ->whereNotIn('user_id', $userIds)
                    ->delete();

                ChatGroupMember::query()
                    ->where('chat_group_id', $chatGroup->id)
                    ->whereNotNull('siswa_id')
                    ->whereNotIn('siswa_id', $siswaIds)
                    ->delete();

                foreach ($userIds as $userId) {
                    ChatGroupMember::updateOrCreate(
                        [
                            'chat_group_id' => $chatGroup->id,
                            'user_id' => $userId,
                        ],
                        [
                            'role' => $adminUserIds->contains($userId)
                                ? ChatGroupMember::ROLE_ADMIN
                                : ChatGroupMember::ROLE_MEMBER,
                            'joined_at' => now(),
                        ]
                    );
                }

                foreach ($siswaIds as $siswaId) {
                    ChatGroupMember::updateOrCreate(
                        [
                            'chat_group_id' => $chatGroup->id,
                            'siswa_id' => $siswaId,
                        ],
                        [
                            'role' => ChatGroupMember::ROLE_MEMBER,
                            'joined_at' => now(),
                        ]
                    );
                }

                return $chatGroup->fresh()->loadCount('members');
            });

            return response()->json([
                'success' => true,
                'message' => 'Grup chat berhasil diperbarui.',
                'group' => $this->formatGroupForChat($group, $user),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui grup chat.',
            ], 500);
        }
    }

    protected function visibleSiswaQuery($user)
    {
        return Siswa::query()
            ->where('is_active', true)
            ->forUser($user);
    }

    protected function chatDateLabel($date): string
    {
        if ($date->isToday()) {
            return 'Hari ini';
        }

        if ($date->isYesterday()) {
            return 'Kemarin';
        }

        return $date->translatedFormat('d F Y');
    }

    protected function normalizedIdCollection(array $ids)
    {
        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();
    }

    protected function groupUserCandidateQuery($user)
    {
        return User::query()
            ->select(['id', 'name', 'username', 'role_id', 'status'])
            ->with('role:id,name,display_name')
            ->where('status', 'active')
            ->whereKeyNot($user->id)
            ->whereHas('role', fn ($query) => $query->whereIn('name', User::attendanceRoleNames()))
            ->orderBy('name')
            ->orderBy('username');
    }

    protected function canManageGroup(User $user, ChatGroup $group): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ((int) $group->created_by === (int) $user->id) {
            return true;
        }

        return ChatGroupMember::query()
            ->where('chat_group_id', $group->id)
            ->where('user_id', $user->id)
            ->where('role', ChatGroupMember::ROLE_ADMIN)
            ->exists();
    }

    protected function formatGroupForChat(ChatGroup $group, ?User $viewer = null, bool $includeMembers = false): array
    {
        $membersCount = $group->members_count ?? $group->members()->count();

        $data = [
            'id' => $group->id,
            'name' => $group->name,
            'description' => $group->description,
            'type' => $group->type,
            'created_by' => $group->created_by,
            'members_count' => $membersCount,
            'can_manage' => $viewer ? $this->canManageGroup($viewer, $group) : false,
            'updated_at' => $group->updated_at?->toISOString(),
        ];

        if ($includeMembers) {
            $members = $group->relationLoaded('members')
                ? $group->members
                : $group->members()->with(['user.role', 'siswa.kelas'])->get();

            $data['user_ids'] = $members->whereNotNull('user_id')->pluck('user_id')->values();
            $data['siswa_ids'] = $members->whereNotNull('siswa_id')->pluck('siswa_id')->values();
            $data['admin_user_ids'] = $members
                ->whereNotNull('user_id')
                ->where('role', ChatGroupMember::ROLE_ADMIN)
                ->pluck('user_id')
                ->values();
            $data['members'] = $members->map(fn (ChatGroupMember $member) => [
                'id' => $member->id,
                'role' => $member->role,
                'type' => $member->user_id ? 'user' : 'siswa',
                'user_id' => $member->user_id,
                'siswa_id' => $member->siswa_id,
                'name' => $member->user
                    ? ($member->user->name ?: $member->user->username)
                    : $member->siswa?->nama,
                'username' => $member->user?->username,
                'role_label' => $member->user?->operationalRoleLabel(),
                'kelas' => $member->siswa?->kelas?->nama,
            ])->values();
        }

        return $data;
    }

    protected function canChatWithSiswa($user, int $siswaId): bool
    {
        if ($siswaId <= 0) {
            return false;
        }

        return $this->visibleSiswaQuery($user)
            ->whereKey($siswaId)
            ->exists();
    }
}
