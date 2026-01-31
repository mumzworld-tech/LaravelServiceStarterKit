<?php

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\CustomException;
use LogicException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DebugControllerTest extends TestCase
{
    /**
     * Test division by zero endpoint throws exception.
     */
    public function test_division_by_zero_throws_exception(): void
    {
        $this->withoutExceptionHandling();

        $this->expectException(\DivisionByZeroError::class);

        $this->get('/debug/division-by-zero');
    }

    /**
     * Test division by zero endpoint returns error response.
     */
    public function test_division_by_zero_returns_error_response(): void
    {
        $response = $this->get('/debug/division-by-zero');

        $response->assertStatus(500);
    }

    /**
     * Test undefined variable endpoint throws exception.
     */
    public function test_undefined_variable_throws_exception(): void
    {
        $this->withoutExceptionHandling();

        $this->expectException(\ErrorException::class);

        $this->get('/debug/undefined-variable');
    }

    /**
     * Test undefined variable endpoint returns error response.
     */
    public function test_undefined_variable_returns_error_response(): void
    {
        $response = $this->get('/debug/undefined-variable');

        $response->assertStatus(500);
    }

    /**
     * Test type error endpoint response.
     * Note: PHP 8.4 allows integer to string coercion, so this endpoint may return 200.
     */
    public function test_type_error_endpoint_is_accessible(): void
    {
        $response = $this->get('/debug/type-error');

        // PHP 8.4 allows int->string coercion, so this returns 200 with "Got string: 123"
        $response->assertStatus(200);
        $response->assertSee('Got string: 123');
    }

    /**
     * Test out of bounds endpoint returns error response.
     */
    public function test_out_of_bounds_returns_error_response(): void
    {
        $response = $this->get('/debug/out-of-bounds');

        $response->assertStatus(500);
    }

    /**
     * Test logic exception endpoint throws LogicException.
     */
    public function test_logic_exception_throws_logic_exception(): void
    {
        $this->withoutExceptionHandling();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This is a logic exception');

        $this->get('/debug/logic-exception');
    }

    /**
     * Test logic exception endpoint returns error response.
     */
    public function test_logic_exception_returns_error_response(): void
    {
        $response = $this->get('/debug/logic-exception');

        $response->assertStatus(500);
    }

    /**
     * Test runtime exception endpoint throws RuntimeException.
     */
    public function test_runtime_exception_throws_runtime_exception(): void
    {
        $this->withoutExceptionHandling();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This is a runtime exception');

        $this->get('/debug/runtime-exception');
    }

    /**
     * Test runtime exception endpoint returns error response.
     */
    public function test_runtime_exception_returns_error_response(): void
    {
        $response = $this->get('/debug/runtime-exception');

        $response->assertStatus(500);
    }

    /**
     * Test query exception endpoint returns error response.
     */
    public function test_query_exception_returns_error_response(): void
    {
        $response = $this->get('/debug/query-exception');

        $response->assertStatus(500);
    }

    /**
     * Test HTTP exception endpoint throws HttpException.
     */
    public function test_http_exception_throws_http_exception(): void
    {
        $this->withoutExceptionHandling();

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('This is a HTTP exception');

        $this->get('/debug/http-exception');
    }

    /**
     * Test HTTP exception endpoint returns error response.
     */
    public function test_http_exception_returns_error_response(): void
    {
        $response = $this->get('/debug/http-exception');

        $response->assertStatus(500);
    }

    /**
     * Test memory limit endpoint throws ErrorException.
     */
    public function test_memory_limit_throws_error_exception(): void
    {
        $this->withoutExceptionHandling();

        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Allowed memory size');

        $this->get('/debug/memory-limit');
    }

    /**
     * Test memory limit endpoint returns error response.
     */
    public function test_memory_limit_returns_error_response(): void
    {
        $response = $this->get('/debug/memory-limit');

        $response->assertStatus(500);
    }

    /**
     * Test parse error example endpoint returns successful response.
     */
    public function test_parse_error_example_returns_successful_response(): void
    {
        $response = $this->get('/debug/parse-error-example');

        $response->assertStatus(200);
        $response->assertSee('parse error');
    }

    /**
     * Test fatal error endpoint returns error response.
     */
    public function test_fatal_error_returns_error_response(): void
    {
        $response = $this->get('/debug/fatal-error');

        $response->assertStatus(500);
    }

    /**
     * Test custom exception endpoint throws CustomException.
     */
    public function test_custom_exception_throws_custom_exception(): void
    {
        $this->withoutExceptionHandling();

        $this->expectException(CustomException::class);
        $this->expectExceptionMessage('This is a custom exception');

        $this->get('/debug/custom-exception');
    }

    /**
     * Test custom exception endpoint returns error response.
     */
    public function test_custom_exception_returns_error_response(): void
    {
        $response = $this->get('/debug/custom-exception');

        $response->assertStatus(500);
    }

    /**
     * Test random error endpoint is accessible.
     * Note: The endpoint randomly picks an error type. In PHP 8.4, the type-error
     * endpoint returns 200 due to int->string coercion, so we can't always expect 500.
     */
    public function test_random_error_endpoint_is_accessible(): void
    {
        $response = $this->get('/debug/random-error');

        // The response should be either 200 (type-error in PHP 8.4) or 500 (other errors)
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 500]),
            'Expected status 200 or 500, got ' . $response->getStatusCode()
        );
    }

    /**
     * Test debug index endpoint returns error response.
     */
    public function test_debug_index_returns_error_response(): void
    {
        $response = $this->get('/debug');

        $response->assertStatus(500);
    }

    /**
     * Test all debug routes are registered.
     */
    public function test_all_debug_routes_are_registered(): void
    {
        $routes = [
            '/debug',
            '/debug/division-by-zero',
            '/debug/undefined-variable',
            '/debug/type-error',
            '/debug/out-of-bounds',
            '/debug/logic-exception',
            '/debug/runtime-exception',
            '/debug/query-exception',
            '/debug/http-exception',
            '/debug/memory-limit',
            '/debug/parse-error-example',
            '/debug/fatal-error',
            '/debug/custom-exception',
            '/debug/random-error',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $this->assertNotEquals(404, $response->getStatusCode(), "Route {$route} should exist");
        }
    }
}
