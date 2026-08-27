<?php

namespace Tests\Unit;

use App\Http\Controllers\PresensiController;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class AttendanceVerificationSafetyTest extends TestCase
{
    #[Test]
    public function verification_endpoints_are_guarded_by_verify_permission(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/PresensiController.php'));

        $this->assertStringContainsString(
            "pamong.permission:presensi,verify')->only(['verify', 'bulkVerify']",
            $source
        );
    }

    #[Test]
    public function bulk_verification_supports_selected_ids_and_filtered_scope(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/PresensiController.php'));
        $method = new ReflectionMethod(PresensiController::class, 'bulkVerify');
        $methodSource = implode('', array_slice(
            file($method->getFileName()),
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1
        ));

        $this->assertStringContainsString("'scope' => ['required', Rule::in(['selected', 'filtered'])]", $methodSource);
        $this->assertStringContainsString("'ids' => ['prohibited_unless:scope,selected', 'required_if:scope,selected', 'array', 'min:1'", $methodSource);
        $this->assertStringContainsString("whereIn('id', \$validated['ids'])", $methodSource);
        $this->assertStringContainsString('buildListingQuery($request', $methodSource);
        $this->assertStringContainsString("Cache::pull('presensi-verification-preview:'", $methodSource);
    }
}
