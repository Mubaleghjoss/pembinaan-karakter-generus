<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use App\Models\ChatGroupMessage;
use App\Models\PamongSiswa;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminBroadcastController extends Controller
{
    private function activePamongQuery()
    {
        return User::query()
            ->with('role')
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->whereIn('name', User::operationalRoleNames()));
    }

    private function activeStaffQuery()
    {
        return User::query()
            ->with('role')
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->whereIn('name', User::attendanceRoleNames()));
    }

    private function resolveMessagePayload(Request $request): array
    {
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

        return [$messageType, $attachmentPath];
    }

    public function index()
    {
        $siswaCount = Siswa::where('is_active', true)->count();
        $pamongCount = $this->activePamongQuery()->count();
        $userCount = $this->activeStaffQuery()
            ->where('id', '!=', Auth::id())
            ->count();
        $pamongGroupCount = PamongSiswa::query()
            ->select('pamong_id')
            ->distinct()
            ->count('pamong_id');
        
        return view('admin.broadcast.index', compact('siswaCount', 'pamongCount', 'userCount', 'pamongGroupCount'));
    }

    public function sendToSiswa(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'nullable|string|max:1000',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = Auth::user();
        [$messageType, $attachmentPath] = $this->resolveMessagePayload($request);

        $siswaList = Siswa::where('is_active', true)->get();

        $sentCount = 0;
        foreach ($siswaList as $siswa) {
            Chat::create([
                'sender_user_id' => $user->id,
                'receiver_siswa_id' => $siswa->id,
                'message' => $request->message,
                'message_type' => $messageType,
                'attachment_path' => $attachmentPath,
            ]);
            $sentCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Pesan berhasil dikirim ke {$sentCount} siswa.",
            'sent_count' => $sentCount,
        ]);
    }

    public function sendToPamong(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'nullable|string|max:1000',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = Auth::user();
        [$messageType, $attachmentPath] = $this->resolveMessagePayload($request);

        $pamongList = $this->activePamongQuery()->get();

        $sentCount = 0;
        foreach ($pamongList as $pamong) {
            Chat::create([
                'sender_user_id' => $user->id,
                'receiver_user_id' => $pamong->id,
                'message' => $request->message,
                'message_type' => $messageType,
                'attachment_path' => $attachmentPath,
            ]);
            $sentCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Pesan berhasil dikirim ke {$sentCount} pamong.",
            'sent_count' => $sentCount,
        ]);
    }

    public function sendToUsers(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'nullable|string|max:1000',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $sender = Auth::user();
        [$messageType, $attachmentPath] = $this->resolveMessagePayload($request);

        $users = $this->activeStaffQuery()
            ->where('id', '!=', $sender->id)
            ->get();

        $sentCount = 0;
        foreach ($users as $user) {
            Chat::create([
                'sender_user_id' => $sender->id,
                'receiver_user_id' => $user->id,
                'message' => $request->message,
                'message_type' => $messageType,
                'attachment_path' => $attachmentPath,
            ]);
            $sentCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Pesan berhasil dikirim ke {$sentCount} user.",
            'sent_count' => $sentCount,
        ]);
    }

    public function sendToPamongGroups(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'nullable|string|max:1000',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $sender = Auth::user();
        [$messageType, $attachmentPath] = $this->resolveMessagePayload($request);

        $pamongs = $this->activePamongQuery()
            ->whereIn('id', PamongSiswa::query()->select('pamong_id')->distinct())
            ->get();

        $sentCount = 0;
        foreach ($pamongs as $pamong) {
            $marker = "[AUTO_PAMONG_GROUP:{$pamong->id}]";
            $group = ChatGroup::query()->firstOrCreate(
                ['description' => $marker],
                [
                    'name' => 'Grup Pamong - ' . $pamong->username,
                    'type' => ChatGroup::TYPE_CUSTOM,
                    'created_by' => $sender->id,
                    'is_active' => true,
                ]
            );

            $group->update([
                'name' => 'Grup Pamong - ' . $pamong->username,
                'is_active' => true,
                'created_by' => $group->created_by ?: $sender->id,
            ]);

            ChatGroupMember::firstOrCreate(
                [
                    'chat_group_id' => $group->id,
                    'user_id' => $sender->id,
                ],
                [
                    'role' => ChatGroupMember::ROLE_ADMIN,
                    'joined_at' => now(),
                ]
            );

            ChatGroupMember::firstOrCreate(
                [
                    'chat_group_id' => $group->id,
                    'user_id' => $pamong->id,
                ],
                [
                    'role' => ChatGroupMember::ROLE_MEMBER,
                    'joined_at' => now(),
                ]
            );

            $assignedSiswaIds = PamongSiswa::query()
                ->where('pamong_id', $pamong->id)
                ->pluck('siswa_id');

            $existingSiswaIds = ChatGroupMember::query()
                ->where('chat_group_id', $group->id)
                ->whereNotNull('siswa_id')
                ->pluck('siswa_id');

            foreach ($assignedSiswaIds as $siswaId) {
                if (! $existingSiswaIds->contains($siswaId)) {
                    ChatGroupMember::create([
                        'chat_group_id' => $group->id,
                        'siswa_id' => $siswaId,
                        'role' => ChatGroupMember::ROLE_MEMBER,
                        'joined_at' => now(),
                    ]);
                }
            }

            ChatGroupMember::query()
                ->where('chat_group_id', $group->id)
                ->whereNotNull('siswa_id')
                ->whereNotIn('siswa_id', $assignedSiswaIds->all())
                ->delete();

            ChatGroupMessage::create([
                'chat_group_id' => $group->id,
                'sender_user_id' => $sender->id,
                'message' => $request->message,
                'attachment_path' => $attachmentPath,
                'is_read_by' => ["user_{$sender->id}"],
            ]);

            $group->touch();
            $sentCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Pesan berhasil dikirim ke {$sentCount} grup pamong.",
            'sent_count' => $sentCount,
        ]);
    }
}
