<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatGroup;
use App\Models\PamongSiswa;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SiswaChatController extends Controller
{
    public function index()
    {
        $siswa = Auth::guard('siswa')->user();
        $pamongIds = PamongSiswa::query()
            ->when(! $siswa->isGraduated(), fn ($query) => $query->active())
            ->where('siswa_id', $siswa->id)
            ->pluck('pamong_id');
        $pamongList = User::query()
            ->with('role')
            ->where('status', 'active')
            ->where(function ($query) use ($pamongIds) {
                $query->whereIn('id', $pamongIds)
                    ->orWhereHas('role', fn ($roleQuery) => $roleQuery->where('name', 'admin'));
            })
            ->orderByRaw("CASE WHEN role_id = (SELECT id FROM roles WHERE name = 'admin' LIMIT 1) THEN 0 ELSE 1 END")
            ->orderBy('username')
            ->get()
            ->each(function ($contact) {
                $contact->contact_role_label = $contact->hasRole('admin') ? 'Admin' : 'Pamong';
            });
        
        $relatedSiswa = Siswa::active()
            ->where('id', '!=', $siswa->id)
            ->whereHas('pamongAssignments', fn ($query) => $query->whereIn('pamong_id', $pamongIds))
            ->orderBy('nama')
            ->get();
        
        // Get groups where siswa is a member
        $groups = ChatGroup::whereHas('members', function ($query) use ($siswa) {
            $query->where('siswa_id', $siswa->id);
        })
        ->where('is_active', true)
        ->withCount('members')
        ->orderBy('updated_at', 'desc')
        ->get();
        
        return view('siswa.chat.index', compact('siswa', 'pamongList', 'relatedSiswa', 'groups'));
    }

    public function getMessages(Request $request): JsonResponse
    {
        $siswa = Auth::guard('siswa')->user();
        $type = $request->type; // 'pamong' or 'siswa'
        $targetId = $request->target_id;

        $query = Chat::query();

        if ($type === 'pamong') {
            $query->where(function ($q) use ($siswa, $targetId) {
                $q->where(function ($sub) use ($siswa, $targetId) {
                    $sub->where('sender_siswa_id', $siswa->id)
                        ->where('receiver_user_id', $targetId);
                })->orWhere(function ($sub) use ($siswa, $targetId) {
                    $sub->where('sender_user_id', $targetId)
                        ->where('receiver_siswa_id', $siswa->id);
                });
            });
        } else {
            $query->where(function ($q) use ($siswa, $targetId) {
                $q->where(function ($sub) use ($siswa, $targetId) {
                    $sub->where('sender_siswa_id', $siswa->id)
                        ->where('receiver_siswa_id', $targetId);
                })->orWhere(function ($sub) use ($siswa, $targetId) {
                    $sub->where('sender_siswa_id', $targetId)
                        ->where('receiver_siswa_id', $siswa->id);
                });
            });
        }

        $messages = $query->orderBy('created_at', 'asc')->get();

        // Mark as read
        if ($type === 'pamong') {
            Chat::where('sender_user_id', $targetId)
                ->where('receiver_siswa_id', $siswa->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        } else {
            Chat::where('sender_siswa_id', $targetId)
                ->where('receiver_siswa_id', $siswa->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return response()->json([
            'success' => true,
            'messages' => $messages->map(fn($m) => [
                'id' => $m->id,
                'message' => $m->message,
                'message_type' => $m->message_type ?? 'text',
                'attachment_url' => $m->attachment_url,
                'caption' => $m->caption,
                'is_mine' => (int) $m->sender_siswa_id === (int) $siswa->id,
                'sender_name' => $m->sender_name,
                'created_at' => $m->created_at->format('H:i'),
                'date' => $m->created_at->format('d M Y'),
            ]),
        ]);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $siswa = Auth::guard('siswa')->user();
        if ($siswa->isGraduated()) {
            return response()->json([
                'success' => false,
                'message' => 'Chat Pamong tidak tersedia untuk Alumni.',
            ], 403);
        }

        $request->validate([
            'type' => 'required|in:pamong,siswa',
            'target_id' => 'required|integer',
            'message' => 'nullable|string|max:1000',
            'message_type' => 'nullable|in:text,image,link',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $messageType = 'text';
        $attachmentPath = null;
        $caption = null;

        // Handle image upload with caption support
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat/attachments', 'public');
            $messageType = 'image';
            // If there's text with image, store it as caption
            if ($request->message) {
                $caption = $request->message;
            }
        }

        // For text-only messages, store in message field
        $messageContent = $messageType === 'image' ? null : $request->message;

        $chat = Chat::create([
            'sender_siswa_id' => $siswa->id,
            'receiver_user_id' => $request->type === 'pamong' ? $request->target_id : null,
            'receiver_siswa_id' => $request->type === 'siswa' ? $request->target_id : null,
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
                'sender_name' => $siswa->nama,
                'created_at' => $chat->created_at->format('H:i'),
                'date' => $chat->created_at->format('d M Y'),
            ],
        ]);
    }

    public function getUnreadCount(): JsonResponse
    {
        $siswa = Auth::guard('siswa')->user();
        
        $count = Chat::where('receiver_siswa_id', $siswa->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function getUnreadCountPerContact(): JsonResponse
    {
        $siswa = Auth::guard('siswa')->user();
        
        // Get unread count from pamong (users)
        $pamongUnread = Chat::where('receiver_siswa_id', $siswa->id)
            ->where('is_read', false)
            ->whereNotNull('sender_user_id')
            ->selectRaw('sender_user_id as contact_id, COUNT(*) as unread_count')
            ->groupBy('sender_user_id')
            ->pluck('unread_count', 'contact_id')
            ->mapWithKeys(fn ($count, $contactId) => ["pamong_{$contactId}" => $count]);
        
        $siswaUnread = Chat::where('receiver_siswa_id', $siswa->id)
            ->where('is_read', false)
            ->whereNotNull('sender_siswa_id')
            ->selectRaw('sender_siswa_id as contact_id, COUNT(*) as unread_count')
            ->groupBy('sender_siswa_id')
            ->pluck('unread_count', 'contact_id')
            ->mapWithKeys(fn ($count, $contactId) => ["siswa_{$contactId}" => $count]);
        
        return response()->json(['unread_counts' => $pamongUnread->merge($siswaUnread)]);
    }
}
