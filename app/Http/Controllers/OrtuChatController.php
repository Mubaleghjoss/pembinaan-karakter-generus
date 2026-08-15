<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\User;
use App\Models\PamongSiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrtuChatController extends Controller
{
    public function index()
    {
        $siswa = Auth::guard('ortu')->user();

        // Get pamong assigned to this siswa
        $pamongIds = PamongSiswa::query()
            ->when(! $siswa->isGraduated(), fn ($query) => $query->active())
            ->where('siswa_id', $siswa->id)
            ->pluck('pamong_id');
        $pamongList = User::whereIn('id', $pamongIds)->get();

        // If no assigned pamong, show all pamong
        if ($pamongList->isEmpty() && ! $siswa->isGraduated()) {
            $pamongList = User::whereHas('roles', function ($q) {
                $q->whereIn('slug', ['pamong', 'admin']);
            })->get();
        }

        // Get latest message for each pamong
        foreach ($pamongList as $pamong) {
            $pamong->lastMessage = Chat::where(function ($q) use ($siswa, $pamong) {
                $q->where('sender_siswa_id', $siswa->id)->where('receiver_user_id', $pamong->id);
            })->orWhere(function ($q) use ($siswa, $pamong) {
                $q->where('sender_user_id', $pamong->id)->where('receiver_siswa_id', $siswa->id);
            })->orderByDesc('created_at')->first();

            $pamong->unreadCount = Chat::where('sender_user_id', $pamong->id)
                ->where('receiver_siswa_id', $siswa->id)
                ->where('is_read', false)
                ->count();
        }

        return view('ortu.chat.index', compact('siswa', 'pamongList'));
    }

    public function getMessages(Request $request)
    {
        $siswa = Auth::guard('ortu')->user();
        $pamongId = $request->input('pamong_id');

        $messages = Chat::where(function ($q) use ($siswa, $pamongId) {
            $q->where('sender_siswa_id', $siswa->id)->where('receiver_user_id', $pamongId);
        })->orWhere(function ($q) use ($siswa, $pamongId) {
            $q->where('sender_user_id', $pamongId)->where('receiver_siswa_id', $siswa->id);
        })->orderBy('created_at', 'asc')
        ->take(100)
        ->get()
        ->map(function ($msg) use ($siswa) {
            return [
                'id' => $msg->id,
                'message' => $msg->message,
                'is_mine' => $msg->sender_siswa_id === $siswa->id,
                'sender_name' => $msg->sender_name,
                'created_at' => $msg->created_at->format('H:i'),
                'date' => $msg->created_at->format('d M Y'),
                'is_read' => $msg->is_read,
            ];
        });

        // Mark as read
        Chat::where('sender_user_id', $pamongId)
            ->where('receiver_siswa_id', $siswa->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $siswa = Auth::guard('ortu')->user();

        if ($siswa->isGraduated()) {
            return response()->json([
                'success' => false,
                'message' => 'Portal Orang Tua Alumni bersifat baca-saja.',
            ], 403);
        }

        $request->validate([
            'pamong_id' => 'required|exists:users,id',
            'message' => 'required|string|max:2000',
        ]);

        $chat = Chat::create([
            'sender_siswa_id' => $siswa->id,
            'receiver_user_id' => $request->pamong_id,
            'message' => '[Ortu] ' . $request->message,
            'message_type' => 'text',
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $chat->id,
                'message' => $chat->message,
                'is_mine' => true,
                'sender_name' => 'Ortu ' . $siswa->nama,
                'created_at' => $chat->created_at->format('H:i'),
                'date' => $chat->created_at->format('d M Y'),
            ],
        ]);
    }
}
