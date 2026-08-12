<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CspReportController extends Controller
{
    public function __invoke(Request $request)
    {
        if (strlen((string) $request->getContent()) > 32768) {
            return response()->noContent(413);
        }

        $payload = $request->json()->all();
        $report = $payload['csp-report'] ?? $payload['body'] ?? [];

        if (is_array($report)) {
            Log::channel(config('logging.default'))->notice('CSP report-only violation', [
                'document_uri' => $this->safeValue($report['document-uri'] ?? $report['documentURL'] ?? null),
                'blocked_uri' => $this->safeValue($report['blocked-uri'] ?? $report['blockedURL'] ?? null),
                'effective_directive' => $this->safeValue($report['effective-directive'] ?? $report['effectiveDirective'] ?? null),
                'source_file' => $this->safeValue($report['source-file'] ?? $report['sourceFile'] ?? null),
                'line_number' => (int) ($report['line-number'] ?? $report['lineNumber'] ?? 0),
                'ip_hash' => hash('sha256', (string) $request->ip()),
            ]);
        }

        return response()->noContent();
    }

    private function safeValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        return mb_substr((string) $value, 0, 500);
    }
}
