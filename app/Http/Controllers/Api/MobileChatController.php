<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\MobileDeviceToken;
use App\Models\PamongSiswa;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class MobileChatController extends Controller
{
    public function __construct(private readonly FcmService $fcm)
    {
    }

    public function contacts(Request $request): JsonResponse
    {
        $this->assertMobileChatAbility($request);
        $actor = $request->user();

        if ($actor instanceof Siswa) {
            $pamongIds = PamongSiswa::query()
                ->where('siswa_id', $actor->id)
                ->pluck('pamong_id');

            $contacts = User::query()
                ->whereIn('id', $pamongIds)
                ->orderBy('username')
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'type' => 'pamong',
                    'name' => $user->name ?: $user->username,
                    'subtitle' => 'Pamong pembimbing',
                ]);

            return response()->json(['data' => $contacts->values()]);
        }

        if ($actor instanceof User) {
            $students = Siswa::query()
                ->whereIn('id', $actor->getAssignedSiswaIds() ?: [0])
                ->orderBy('nama')
                ->get()
                ->map(fn (Siswa $siswa) => [
                    'id' => $siswa->id,
                    'type' => 'siswa',
                    'name' => $siswa->nama,
                    'subtitle' => $siswa->school_grade_label,
                ]);

            return response()->json(['data' => $students->values()]);
        }

        return response()->json(['message' => 'Akun tidak didukung.'], 403);
    }

    public function messages(Request $request): JsonResponse
    {
        $this->assertMobileChatAbility($request);
        $actor = $request->user();
        $type = $request->string('type')->toString();
        $targetId = $request->integer('target_id');

        if (! in_array($type, ['pamong', 'siswa'], true) || $targetId < 1) {
            return response()->json(['message' => 'Kontak chat tidak valid.'], 422);
        }

        $query = Chat::query();
        if ($actor instanceof Siswa && $type === 'pamong') {
            $this->assertSiswaMayChatWithPamong($actor, $targetId);
            $query->where(function ($q) use ($actor, $targetId) {
                $q->where(fn ($x) => $x->where('sender_siswa_id', $actor->id)->where('receiver_user_id', $targetId))
                    ->orWhere(fn ($x) => $x->where('sender_user_id', $targetId)->where('receiver_siswa_id', $actor->id));
            });

        } elseif ($actor instanceof User && $type === 'siswa') {
            $this->assertPamongMayChatWithSiswa($actor, $targetId);
            $query->where(function ($q) use ($actor, $targetId) {
                $q->where(fn ($x) => $x->where('sender_user_id', $actor->id)->where('receiver_siswa_id', $targetId))
                    ->orWhere(fn ($x) => $x->where('sender_siswa_id', $targetId)->where('receiver_user_id', $actor->id));
            });

        } else {
            return response()->json(['message' => 'Arah chat tidak sesuai akun.'], 403);
        }

        $messages = $query->with(['senderSiswa', 'senderUser'])->orderBy('created_at')->limit(200)->get();
        return response()->json(['data' => $messages->map(fn (Chat $message) => $this->formatMessage($message, $actor))->values()]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $this->assertMobileChatAbility($request);
        $validated = $request->validate([
            'type' => ['required', 'in:pamong,siswa'],
            'target_id' => ['required', 'integer', 'min:1'],
        ]);
        $actor = $request->user();
        $type = $validated['type'];
        $targetId = (int) $validated['target_id'];

        if ($actor instanceof Siswa && $type === 'pamong') {
            $this->assertSiswaMayChatWithPamong($actor, $targetId);
            $updated = Chat::query()->where('sender_user_id', $targetId)
                ->where('receiver_siswa_id', $actor->id)->where('is_read', false)
                ->update(['is_read' => true]);
        } elseif ($actor instanceof User && $type === 'siswa') {
            $this->assertPamongMayChatWithSiswa($actor, $targetId);
            $updated = Chat::query()->where('sender_siswa_id', $targetId)
                ->where('receiver_user_id', $actor->id)->where('is_read', false)
                ->update(['is_read' => true]);
        } else {
            return response()->json(['message' => 'Arah chat tidak sesuai akun.'], 403);
        }

        return response()->json(['data' => ['updated' => $updated]]);
    }

    public function send(Request $request): JsonResponse
    {
        $this->assertMobileChatAbility($request);
        $validated = $request->validate([
            'type' => ['required', 'in:pamong,siswa'],
            'target_id' => ['required', 'integer', 'min:1'],
            'message' => ['required', 'string', 'max:1000'],
        ], [
            'message.required' => 'Pesan tidak boleh kosong.',
        ]);
        $actor = $request->user();
        $type = $validated['type'];
        $targetId = (int) $validated['target_id'];
        $message = trim($validated['message']);

        if ($message === '') {
            return response()->json(['message' => 'Pesan tidak boleh kosong.'], 422);
        }

        if ($actor instanceof Siswa && $type === 'pamong') {
            $this->assertSiswaMayChatWithPamong($actor, $targetId);
            $chat = Chat::create(['sender_siswa_id' => $actor->id, 'receiver_user_id' => $targetId, 'message' => $message, 'message_type' => Chat::TYPE_TEXT, 'is_read' => false]);
        } elseif ($actor instanceof User && $type === 'siswa') {
            $this->assertPamongMayChatWithSiswa($actor, $targetId);
            $chat = Chat::create(['sender_user_id' => $actor->id, 'receiver_siswa_id' => $targetId, 'message' => $message, 'message_type' => Chat::TYPE_TEXT, 'is_read' => false]);
        } else {
            return response()->json(['message' => 'Arah chat tidak sesuai akun.'], 403);
        }

        $recipientType = $actor instanceof Siswa ? User::class : Siswa::class;
        $recipientId = $actor instanceof Siswa ? $targetId : $targetId;
        $this->notifyRecipient($recipientType, $recipientId, $chat, $actor);

        return response()->json(['data' => $this->formatMessage($chat->load(['senderSiswa', 'senderUser']), $actor)], 201);
    }

    private function notifyRecipient(string $ownerType, int $ownerId, Chat $chat, object $sender): void
    {
        if (! config('fcm.enabled')) {
            return;
        }

        $senderName = $sender instanceof Siswa ? $sender->nama : ($sender->name ?: $sender->username);
        $devices = MobileDeviceToken::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->whereNull('revoked_at')
            ->get();

        foreach ($devices as $device) {
            try {
                $sent = $this->fcm->send(
                    $device->token,
                    $senderName,
                    $chat->message,
                    ['route' => '/chat', 'chat_id' => (string) $chat->id],
                );
                if (! $sent) {
                    $device->forceFill(['revoked_at' => now()])->save();
                }
            } catch (\Throwable $e) {
                Log::warning('FCM delivery failed.', [
                    'device_id' => $device->id,
                    'exception' => $e::class,
                ]);
            }
        }
    }

    private function assertSiswaMayChatWithPamong(Siswa $siswa, int $userId): void
    {
        if (! PamongSiswa::query()->where('siswa_id', $siswa->id)->where('pamong_id', $userId)->exists()) {
            throw new AccessDeniedHttpException('Kontak bukan pamong siswa ini.');
        }
    }

    private function assertPamongMayChatWithSiswa(User $user, int $siswaId): void
    {
        abort_unless(Siswa::query()->whereKey($siswaId)->whereIn('id', $user->getAssignedSiswaIds() ?: [0])->exists(), 403, 'Siswa bukan binaan akun ini.');
    }

    private function assertMobileChatAbility(Request $request): void
    {
        $actor = $request->user();
        $token = $actor?->currentAccessToken();

        abort_unless($token && $token->can('mobile-chat'), 403, 'Token tidak memiliki akses chat mobile.');

        if ($actor instanceof Siswa && $token->can('ortu')) {
            abort(403, 'Akun orang tua belum mendukung chat mobile.');
        }
    }

    private function formatMessage(Chat $message, object $actor): array
    {
        return [
            'id' => $message->id,
            'message' => $message->message,
            'message_type' => $message->message_type,
            'sender_name' => $message->sender_name,
            'is_mine' => $actor instanceof Siswa
                ? (int) $message->sender_siswa_id === (int) $actor->id
                : (int) $message->sender_user_id === (int) $actor->id,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
