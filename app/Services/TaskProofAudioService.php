<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class TaskProofAudioService
{
    public const TARGET_AUDIO_BITRATE = '48k';
    public const TARGET_AUDIO_SAMPLE_RATE = '24000';

    /**
     * Store uploaded voice note proof and compress it with FFmpeg when available.
     *
     * @return array{path:string,size_kb:int}
     */
    public function storeVoiceNote(UploadedFile $file, int $siswaId, int $karakterId): array
    {
        $directory = 'tugas-bukti-audio/' . now()->format('Y/m');
        $timestamp = now()->format('YmdHis');
        $random = Str::lower(Str::random(6));
        $ffmpegBinary = $this->resolveFfmpegBinary();

        if ($ffmpegBinary) {
            $compressed = $this->compressVoiceNote($ffmpegBinary, $file);
            if ($compressed !== null) {
                $filename = "voice_s{$siswaId}_k{$karakterId}_{$timestamp}_{$random}.m4a";
                $path = $directory . '/' . $filename;
                Storage::disk('public')->put($path, file_get_contents($compressed));
                $sizeBytes = (int) (filesize($compressed) ?: 0);
                @unlink($compressed);

                return [
                    'path' => $path,
                    'size_kb' => (int) ceil($sizeBytes / 1024),
                ];
            }
        }

        $originalExtension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'm4a');
        $safeExtension = preg_replace('/[^a-z0-9]/', '', $originalExtension) ?: 'm4a';
        $filename = "voice_s{$siswaId}_k{$karakterId}_{$timestamp}_{$random}.{$safeExtension}";
        $path = $directory . '/' . $filename;

        Storage::disk('public')->putFileAs($directory, $file, $filename);

        return [
            'path' => $path,
            'size_kb' => (int) ceil(((int) ($file->getSize() ?? 0)) / 1024),
        ];
    }

    protected function compressVoiceNote(string $ffmpegBinary, UploadedFile $file): ?string
    {
        $tempOutputBase = tempnam(sys_get_temp_dir(), 'pkg_voice_');
        if ($tempOutputBase === false) {
            return null;
        }

        $tempOutput = $tempOutputBase . '.m4a';
        @unlink($tempOutputBase);

        $process = new Process([
            $ffmpegBinary,
            '-y',
            '-i',
            $file->getPathname(),
            '-vn',
            '-ac',
            '1',
            '-ar',
            self::TARGET_AUDIO_SAMPLE_RATE,
            '-c:a',
            'aac',
            '-b:a',
            self::TARGET_AUDIO_BITRATE,
            '-movflags',
            '+faststart',
            $tempOutput,
        ]);

        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($tempOutput) || (int) filesize($tempOutput) <= 0) {
            @unlink($tempOutput);
            return null;
        }

        return $tempOutput;
    }

    protected function resolveFfmpegBinary(): ?string
    {
        $candidates = array_filter([
            env('FFMPEG_PATH'),
            base_path('bin/ffmpeg.exe'),
            'C:\\ffmpeg\\bin\\ffmpeg.exe',
            'C:\\Program Files\\FFmpeg\\bin\\ffmpeg.exe',
            'C:\\Program Files (x86)\\FFmpeg\\bin\\ffmpeg.exe',
        ]);

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && is_file($candidate)) {
                return $candidate;
            }
        }

        try {
            $process = new Process(['where', 'ffmpeg']);
            $process->setTimeout(5);
            $process->run();

            if ($process->isSuccessful()) {
                $resolved = trim(strtok($process->getOutput(), PHP_EOL) ?: '');
                if ($resolved !== '' && is_file($resolved)) {
                    return $resolved;
                }
            }
        } catch (\Throwable) {
        }

        return null;
    }
}
