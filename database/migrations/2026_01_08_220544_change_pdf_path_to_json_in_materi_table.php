<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert existing pdf_path to JSON array format
        $materis = DB::table('materi')->whereNotNull('pdf_path')->get();
        
        foreach ($materis as $materi) {
            // Convert single path to array
            $pdfFiles = json_encode([
                [
                    'path' => $materi->pdf_path,
                    'name' => basename($materi->pdf_path),
                    'uploaded_at' => now()->toDateTimeString()
                ]
            ]);
            
            DB::table('materi')
                ->where('id', $materi->id)
                ->update(['pdf_path' => $pdfFiles]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert JSON array back to single path
        $materis = DB::table('materi')->whereNotNull('pdf_path')->get();
        
        foreach ($materis as $materi) {
            $pdfFiles = json_decode($materi->pdf_path, true);
            $singlePath = null;
            
            if (is_array($pdfFiles) && count($pdfFiles) > 0) {
                $singlePath = $pdfFiles[0]['path'] ?? null;
            }
            
            DB::table('materi')
                ->where('id', $materi->id)
                ->update(['pdf_path' => $singlePath]);
        }
    }
};
