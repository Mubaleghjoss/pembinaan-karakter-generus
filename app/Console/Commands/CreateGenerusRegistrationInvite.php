<?php

namespace App\Console\Commands;

use App\Models\GenerusRegistrationInvite;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateGenerusRegistrationInvite extends Command
{
    protected $signature = 'registration:invite
        {--label=Pendaftaran Generus Baru : Nama undangan}
        {--uses=1 : Jumlah maksimal pendaftaran}
        {--days=30 : Masa berlaku dalam hari}
        {--short-code : Buat kode akses 8 karakter yang mudah dibagikan}';

    protected $description = 'Membuat tautan privat untuk pendaftaran Generus dan akun orang tua';

    public function handle(): int
    {
        $uses = max(1, (int) $this->option('uses'));
        $days = max(1, (int) $this->option('days'));
        $token = $this->option('short-code') ? $this->shortCode() : Str::random(48);

        $invite = GenerusRegistrationInvite::query()->create([
            'label' => trim((string) $this->option('label')) ?: 'Pendaftaran Generus Baru',
            'token_hash' => hash('sha256', $token),
            'max_uses' => $uses,
            'used_count' => 0,
            'expires_at' => now()->addDays($days),
            'is_active' => true,
        ]);

        $this->info("Undangan #{$invite->id} berhasil dibuat.");
        if ($this->option('short-code')) {
            $this->line(route('public.generus-registration.short.index'));
            $this->line("Kode akses: {$token}");
        } else {
            $this->line(route('public.generus-registration.show', ['token' => $token]));
        }
        $this->line("Berlaku {$days} hari, maksimal {$uses} pendaftaran.");

        return self::SUCCESS;
    }

    private function shortCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';

        for ($index = 0; $index < 8; $index++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }
}
