<?php

namespace App\Http\Controllers;

use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use App\Models\ChatGroupMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserGroupChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('pamong.permission:group_chat,view')->only(['index', 'getMessages', 'getUnreadCount', 'getGroupInfo']);
        $this->middleware('pamong.permission:group_chat,send')->only(['sendMessage']);
    }

    private function currentUserIsAdmin(): bool
    {
        return !Auth::guard('siswa')->check() && Auth::check() && Auth::user()->isAdmin();
    }

    /**
     * Get current user/siswa IDs
     * Note: Auth::guard('siswa')->id() returns NIS (auth identifier), not the actual ID
     * So we need to use ->user()->id to get the actual database ID
     */
    private function getCurrentIds(): array
    {
        $userId = null;
        $siswaId = null;
        
        if (Auth::guard('siswa')->check()) {
            $siswaId = Auth::guard('siswa')->user()->id;
        } else {
            $userId = Auth::id();
        }
        
        return [$userId, $siswaId];
    }

    /**
     * Display group chat interface for users
     */
    public function index()
    {
        [$userId, $siswaId] = $this->getCurrentIds();
        $isAdmin = $this->currentUserIsAdmin();

        $groups = ChatGroup::query()
            ->where('is_active', true)
            ->when(!$isAdmin, function ($query) use ($userId, $siswaId) {
                $query->whereHas('members', function ($memberQuery) use ($userId, $siswaId) {
                    if ($userId) {
                        $memberQuery->where('user_id', $userId);
                    } else {
                        $memberQuery->where('siswa_id', $siswaId);
                    }
                });
            })
            ->with(['members.user', 'members.siswa'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $viewName = Auth::guard('siswa')->check() ? 'siswa.group-chat.index' : 'pamong.group-chat.index';
        
        return view($viewName, compact('groups'));
    }

    /**
     * Get messages for a specific group
     */
    public function getMessages(ChatGroup $chatGroup): JsonResponse
    {
        [$userId, $siswaId] = $this->getCurrentIds();
        $isAdmin = $this->currentUserIsAdmin();

        $isMember = $isAdmin || ChatGroupMember::where('chat_group_id', $chatGroup->id)
            ->where(function ($query) use ($userId, $siswaId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('siswa_id', $siswaId);
                }
            })
            ->exists();

        if (!$isMember) {
            return response()->json([
                'success' => false,
                'error' => 'Anda bukan anggota grup ini'
            ], 403);
        }

        $messages = ChatGroupMessage::where('chat_group_id', $chatGroup->id)
            ->with(['senderUser', 'senderSiswa'])
            ->orderBy('created_at', 'asc')
            ->get();

        $messages->each(function ($message) use ($userId, $siswaId) {
            $isMine = ($message->sender_user_id === $userId) || ($message->sender_siswa_id === $siswaId);
            if (! $isMine) {
                $message->markAsReadBy($userId, $siswaId);
            }
        });

        return response()->json([
            'success' => true,
            'messages' => $messages->map(function ($message) use ($userId, $siswaId) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'attachment_url' => $message->attachment_url,
                    'sender_name' => $message->sender_name,
                    'sender_type' => $message->sender_user_id ? 'pamong' : 'siswa',
                    'is_mine' => ($message->sender_user_id === $userId) || ($message->sender_siswa_id === $siswaId),
                    'created_at' => $message->created_at->format('H:i'),
                    'date' => $message->created_at->format('d M Y'),
                    'date_label' => $this->chatDateLabel($message->created_at),
                    'full_date' => $message->created_at->format('Y-m-d'),
                ];
            })
        ]);
    }


    /**
     * Send a message to the group
     */
    public function sendMessage(Request $request, ChatGroup $chatGroup): JsonResponse
    {
        $student = Auth::guard('siswa')->user();
        if ($student?->isGraduated()) {
            return response()->json([
                'success' => false,
                'error' => 'Chat grup tidak tersedia untuk Alumni.',
            ], 403);
        }

        $request->validate([
            'message' => 'nullable|string|max:1000',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        [$userId, $siswaId] = $this->getCurrentIds();
        $isAdmin = $this->currentUserIsAdmin();

        $isMember = $isAdmin || ChatGroupMember::where('chat_group_id', $chatGroup->id)
            ->where(function ($query) use ($userId, $siswaId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('siswa_id', $siswaId);
                }
            })
            ->exists();

        if (!$isMember) {
            return response()->json([
                'success' => false,
                'error' => 'Anda bukan anggota grup ini'
            ], 403);
        }

        if (!$request->message && !$request->hasFile('attachment')) {
            return response()->json([
                'success' => false,
                'error' => 'Pesan atau attachment harus diisi'
            ], 422);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat/group-attachments', 'public');
        }

        $message = ChatGroupMessage::create([
            'chat_group_id' => $chatGroup->id,
            'sender_user_id' => $userId,
            'sender_siswa_id' => $siswaId,
            'message' => $request->message,
            'attachment_path' => $attachmentPath,
        ]);

        $chatGroup->touch();

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'message' => $message->message,
                'attachment_url' => $message->attachment_url,
                'sender_name' => $message->sender_name,
                'sender_type' => $message->sender_user_id ? 'pamong' : 'siswa',
                'is_mine' => true,
                'created_at' => $message->created_at->format('H:i'),
                'date' => $message->created_at->format('d M Y'),
                'date_label' => $this->chatDateLabel($message->created_at),
                'full_date' => $message->created_at->format('Y-m-d'),
            ]
        ]);
    }

    /**
     * Get unread count for all groups
     */
    public function getUnreadCount(): JsonResponse
    {
        [$userId, $siswaId] = $this->getCurrentIds();
        $readerKey = $userId ? "user_{$userId}" : "siswa_{$siswaId}";
        $isAdmin = $this->currentUserIsAdmin();

        $groupIds = $isAdmin
            ? ChatGroup::query()->where('is_active', true)->pluck('id')
            : ChatGroupMember::where(function ($query) use ($userId, $siswaId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('siswa_id', $siswaId);
                }
            })->pluck('chat_group_id');

        // Count unread messages (messages not sent by current user)
        $unreadCount = ChatGroupMessage::whereIn('chat_group_id', $groupIds)
            ->where(function ($query) use ($userId, $siswaId) {
                if ($userId) {
                    $query->where(function ($q) use ($userId) {
                        $q->whereNull('sender_user_id')
                          ->orWhere('sender_user_id', '!=', $userId);
                    });
                } else {
                    $query->where(function ($q) use ($siswaId) {
                        $q->whereNull('sender_siswa_id')
                          ->orWhere('sender_siswa_id', '!=', $siswaId);
                    });
                }
            })
            ->whereRaw("NOT JSON_CONTAINS(COALESCE(is_read_by, '[]'), ?)", [json_encode($readerKey)])
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Get group info
     */
    public function getGroupInfo(ChatGroup $chatGroup): JsonResponse
    {
        [$userId, $siswaId] = $this->getCurrentIds();
        $isAdmin = $this->currentUserIsAdmin();

        $isMember = $isAdmin || ChatGroupMember::where('chat_group_id', $chatGroup->id)
            ->where(function ($query) use ($userId, $siswaId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('siswa_id', $siswaId);
                }
            })
            ->exists();

        if (!$isMember) {
            return response()->json([
                'success' => false,
                'error' => 'Anda bukan anggota grup ini'
            ], 403);
        }

        $chatGroup->load(['members.user', 'members.siswa']);

        return response()->json([
            'success' => true,
            'group' => [
                'id' => $chatGroup->id,
                'name' => $chatGroup->name,
                'description' => $chatGroup->description,
                'type' => $chatGroup->type,
                'member_count' => $chatGroup->members->count(),
                'members' => $chatGroup->members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->user ? $member->user->username : $member->siswa->nama,
                        'type' => $member->user ? 'pamong' : 'siswa',
                        'role' => $member->role,
                        'joined_at' => $member->joined_at->format('d M Y'),
                    ];
                })
            ]
        ]);
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
}
