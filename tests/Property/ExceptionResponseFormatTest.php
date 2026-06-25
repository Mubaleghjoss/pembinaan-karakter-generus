<?php

namespace Tests\Property;

use App\Exceptions\BusinessException;
use App\Exceptions\ClassFullException;
use App\Exceptions\DuplicateAttendanceException;
use App\Exceptions\QrTokenExpiredException;
use App\Models\Siswa;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

/**
 * Property-based tests for exception response format consistency.
 *
 * **Feature: clean-code-refactoring, Property 3: Exception Response Format Consistency**
 * **Validates: Requirements 4.1, 4.4**
 */
class ExceptionResponseFormatTest extends TestCase
{
    use TestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
        $this->withExceptionHandling();
        $this->setupTestRoutes();
    }

    /**
     * Setup test routes that throw various exceptions.
     */
    private function setupTestRoutes(): void
    {
        Route::prefix('api/test-exceptions')->group(function () {
            Route::get('/validation', function () {
                throw ValidationException::withMessages([
                    'field' => ['Field is required'],
                ]);
            });

            Route::get('/not-found', function () {
                throw (new ModelNotFoundException)->setModel(Siswa::class);
            });

            Route::get('/unauthorized', function () {
                throw new AuthenticationException('Unauthenticated');
            });

            Route::get('/forbidden', function () {
                throw new AccessDeniedHttpException('Access denied');
            });

            Route::get('/qr-expired', function () {
                throw new QrTokenExpiredException;
            });

            Route::get('/duplicate-attendance', function () {
                throw new DuplicateAttendanceException;
            });

            Route::get('/class-full', function () {
                throw new ClassFullException;
            });

            Route::get('/server-error', function () {
                throw new \RuntimeException('Internal error');
            });
        });
    }

    /**
     * **Feature: clean-code-refactoring, Property 3: Exception Response Format Consistency**
     * **Validates: Requirements 4.1, 4.4**
     *
     * Property: For any validation exception, the response must contain
     * 'success' = false, 'error' = 'Validation failed', and 'errors' object.
     */
    public function test_validation_exception_response_format(): void
    {
        $this->forAll(
            Generator\elements(['field1', 'email', 'name', 'siswa_id', 'tanggal'])
        )
            ->withMaxSize(100)
            ->then(function (string $fieldName) {
                // Setup route with dynamic field
                Route::get("api/test-exceptions/validation-{$fieldName}", function () use ($fieldName) {
                    throw ValidationException::withMessages([
                        $fieldName => ["{$fieldName} is required"],
                    ]);
                });

                $response = $this->getJson("api/test-exceptions/validation-{$fieldName}");

                $response->assertStatus(422);

                $json = $response->json();

                // Must have 'success' = false
                $this->assertArrayHasKey('success', $json);
                $this->assertFalse($json['success']);

                // Must have 'error' = 'Validation failed'
                $this->assertArrayHasKey('error', $json);
                $this->assertEquals('Validation failed', $json['error']);

                // Must have 'errors' object with field-specific messages
                $this->assertArrayHasKey('errors', $json);
                $this->assertIsArray($json['errors']);
                $this->assertArrayHasKey($fieldName, $json['errors']);
            });
    }

    /**
     * **Feature: clean-code-refactoring, Property 3: Exception Response Format Consistency**
     * **Validates: Requirements 4.1, 4.4**
     *
     * Property: For any BusinessException, the response must contain
     * 'success' = false, 'error', and 'code' fields.
     */
    public function test_business_exception_response_format(): void
    {
        $businessExceptions = [
            ['route' => 'qr-expired', 'code' => 'QR_EXPIRED', 'status' => 400],
            ['route' => 'duplicate-attendance', 'code' => 'DUPLICATE_ATTENDANCE', 'status' => 400],
            ['route' => 'class-full', 'code' => 'CLASS_FULL', 'status' => 400],
        ];

        foreach ($businessExceptions as $exception) {
            $response = $this->getJson("api/test-exceptions/{$exception['route']}");

            $response->assertStatus($exception['status']);

            $json = $response->json();

            // Must have 'success' = false
            $this->assertArrayHasKey('success', $json);
            $this->assertFalse($json['success']);

            // Must have 'error' field
            $this->assertArrayHasKey('error', $json);
            $this->assertIsString($json['error']);

            // Must have 'code' field matching expected code
            $this->assertArrayHasKey('code', $json);
            $this->assertEquals($exception['code'], $json['code']);
        }
    }

    /**
     * **Feature: clean-code-refactoring, Property 3: Exception Response Format Consistency**
     * **Validates: Requirements 4.1, 4.4**
     *
     * Property: For any ModelNotFoundException, the response must return 404
     * with standardized format.
     */
    public function test_not_found_exception_response_format(): void
    {
        $response = $this->getJson('api/test-exceptions/not-found');

        $response->assertStatus(404);

        $json = $response->json();

        // Must have 'success' = false
        $this->assertArrayHasKey('success', $json);
        $this->assertFalse($json['success']);

        // Must have 'error' field
        $this->assertArrayHasKey('error', $json);
        $this->assertEquals('Not found', $json['error']);

        // Must have 'code' field
        $this->assertArrayHasKey('code', $json);
        $this->assertEquals('NOT_FOUND', $json['code']);
    }

    /**
     * **Feature: clean-code-refactoring, Property 3: Exception Response Format Consistency**
     * **Validates: Requirements 4.1, 4.4**
     *
     * Property: For any AuthenticationException, the response must return 401
     * with standardized format.
     */
    public function test_authentication_exception_response_format(): void
    {
        $response = $this->getJson('api/test-exceptions/unauthorized');

        $response->assertStatus(401);

        $json = $response->json();

        // Must have 'success' = false
        $this->assertArrayHasKey('success', $json);
        $this->assertFalse($json['success']);

        // Must have 'error' field
        $this->assertArrayHasKey('error', $json);
        $this->assertEquals('Unauthorized', $json['error']);

        // Must have 'code' field
        $this->assertArrayHasKey('code', $json);
        $this->assertEquals('UNAUTHORIZED', $json['code']);
    }

    /**
     * **Feature: clean-code-refactoring, Property 3: Exception Response Format Consistency**
     * **Validates: Requirements 4.1, 4.4**
     *
     * Property: For any AccessDeniedHttpException, the response must return 403
     * with standardized format.
     */
    public function test_forbidden_exception_response_format(): void
    {
        $response = $this->getJson('api/test-exceptions/forbidden');

        $response->assertStatus(403);

        $json = $response->json();

        // Must have 'success' = false
        $this->assertArrayHasKey('success', $json);
        $this->assertFalse($json['success']);

        // Must have 'error' field
        $this->assertArrayHasKey('error', $json);
        $this->assertEquals('Forbidden', $json['error']);

        // Must have 'code' field
        $this->assertArrayHasKey('code', $json);
        $this->assertEquals('FORBIDDEN', $json['code']);
    }

    /**
     * **Feature: clean-code-refactoring, Property 3: Exception Response Format Consistency**
     * **Validates: Requirements 4.1, 4.4**
     *
     * Property: For any generic exception in production mode, the response must
     * return 500 without exposing stack traces.
     */
    public function test_server_error_does_not_expose_stack_trace_in_production(): void
    {
        // Simulate production environment by mocking the environment check
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->getJson('api/test-exceptions/server-error');

        $response->assertStatus(500);

        $json = $response->json();

        // Must have 'success' = false
        $this->assertArrayHasKey('success', $json);
        $this->assertFalse($json['success']);

        // Must have 'error' field
        $this->assertArrayHasKey('error', $json);
        $this->assertEquals('Server error', $json['error']);

        // Must have 'code' field
        $this->assertArrayHasKey('code', $json);
        $this->assertEquals('SERVER_ERROR', $json['code']);

        // Must NOT expose stack trace in production
        $this->assertArrayNotHasKey('trace', $json);

        // Message should be generic, not the actual exception message
        $this->assertArrayHasKey('message', $json);
        $this->assertStringNotContainsString('Internal error', $json['message']);
    }

    /**
     * **Feature: clean-code-refactoring, Property 3: Exception Response Format Consistency**
     * **Validates: Requirements 4.1, 4.4**
     *
     * Property: For any exception response, the 'success' field must always be false.
     */
    public function test_all_exception_responses_have_success_false(): void
    {
        $routes = [
            'api/test-exceptions/validation',
            'api/test-exceptions/not-found',
            'api/test-exceptions/unauthorized',
            'api/test-exceptions/forbidden',
            'api/test-exceptions/qr-expired',
            'api/test-exceptions/duplicate-attendance',
            'api/test-exceptions/class-full',
        ];

        foreach ($routes as $route) {
            $response = $this->getJson($route);
            $json = $response->json();

            $this->assertArrayHasKey('success', $json, "Route {$route} missing 'success' field");
            $this->assertFalse($json['success'], "Route {$route} should have success=false");
        }
    }
}
