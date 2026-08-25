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

        // 1) PAMONG ananda: hanya user yang benar-benar ditugaskan untuk anak ini.
        $pamongIds = PamongSiswa::query()
            ->when(! $siswa->isGraduated(), fn ($query) => $query->active())
            ->where('siswa_id', $siswa->id)
            ->pluck('pamong_id');

        $pamongList = User::query()
            ->with('role')
            ->whereIn('id', $pamongIds)
            ->orderBy('username')
            ->get();

        // 2) PENGURUS PKG + ADMIN: kontak umum, selalu tersedia agar ortu tetap
        //    bisa bertanya walau pamong belum ditugaskan.
        //    Catatan: model User memakai relasi tunggal role() (kolom roles.name),
        //    bukan roles()/slug — query lama memakai roles() dan menyebabkan 500.
        $pengurusList = $siswa->isGraduated()
            ? User::query()->whereRaw('1 = 0')->get()
            : User::query()
                ->with('role')
                ->where('status', 'active')
                ->whereNotIn('id', $pamongIds)
                ->whereHas('role', fn ($roleQuery) => $roleQuery->whereIn('name', ['admin', 'pkg_manager']))
                ->orderBy('username')
                ->get();

        // Lengkapi tiap kontak dengan pesan terakhir, jumlah belum dibaca, dan label peran.
        $this->decorateContacts($pamongList, $siswa, 'pamong');
        $this->decorateContacts($pengurusList, $siswa, 'pengurus');

        $contacts = $pamongList->concat($pengurusList);

        return view('ortu.chat.index', compact('siswa', 'pamongList', 'pengurusList', 'contacts'));
    }

    /**
     * Tempelkan pesan terakhir, jumlah pesan belum dibaca, dan label peran
     * (Pamong / Pengurus PKG / Admin) pada setiap kontak.
     */
    private function decorateContacts($contacts, $siswa, string $group): void
    {
        foreach ($contacts as $contact) {
            $contact->lastMessage = Chat::where(function ($q) use ($siswa, $contact) {
                $q->where('sender_siswa_id', $siswa->id)->where('receiver_user_id', $contact->id);
            })->orWhere(function ($q) use ($siswa, $contact) {
                $q->where('sender_user_id', $contact->id)->where('receiver_siswa_id', $siswa->id);
            })->orderByDesc('created_at')->first();

            $contact->unreadCount = Chat::where('sender_user_id', $contact->id)
                ->where('receiver_siswa_id', $siswa->id)
                ->where('is_read', false)
                ->count();

            $roleName = $contact->role->name ?? '';
            $contact->isAdmin = $roleName === 'admin';
            $contact->contactGroup = $group;
            $contact->roleLabel = match (true) {
                $roleName === 'admin' => 'Admin',
                $roleName === 'pkg_manager' => 'Pengurus PKG',
                $group === 'pamong' => 'Pamong Pembimbing',
                default => 'Pengurus PKG',
            };
        }
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
