<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AuditDynamoDbScansTest extends TestCase
{
    protected string $testFilesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testFilesPath = storage_path('app/test_audit_files');

        // Create test directory
        if (!File::isDirectory($this->testFilesPath)) {
            File::makeDirectory($this->testFilesPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::isDirectory($this->testFilesPath)) {
            File::deleteDirectory($this->testFilesPath);
        }

        parent::tearDown();
    }

    /**
     * Test command is registered.
     */
    public function test_command_is_registered(): void
    {
        $this->artisan('dynamodb:audit-scans', ['--help' => true])
            ->assertSuccessful();
    }

    /**
     * Test command has path option.
     */
    public function test_command_has_path_option(): void
    {
        $this->artisan('dynamodb:audit-scans', ['--help' => true])
            ->expectsOutputToContain('--path');
    }

    /**
     * Test command has fix option.
     */
    public function test_command_has_fix_option(): void
    {
        $this->artisan('dynamodb:audit-scans', ['--help' => true])
            ->expectsOutputToContain('--fix');
    }

    /**
     * Test command has json option.
     */
    public function test_command_has_json_option(): void
    {
        $this->artisan('dynamodb:audit-scans', ['--help' => true])
            ->expectsOutputToContain('--json');
    }

    /**
     * Test command has strict option.
     */
    public function test_command_has_strict_option(): void
    {
        $this->artisan('dynamodb:audit-scans', ['--help' => true])
            ->expectsOutputToContain('--strict');
    }

    /**
     * Test command runs successfully on app directory.
     */
    public function test_command_runs_successfully(): void
    {
        $this->artisan('dynamodb:audit-scans')
            ->assertSuccessful();
    }

    /**
     * Test command discovers DynamoDB models.
     */
    public function test_command_discovers_dynamodb_models(): void
    {
        $this->artisan('dynamodb:audit-scans')
            ->expectsOutputToContain('ExampleModelDynamoDB')
            ->assertSuccessful();
    }

    /**
     * Test command detects Model::all() pattern.
     */
    public function test_command_detects_model_all_pattern(): void
    {
        // Create a test file with scan pattern
        $testFile = $this->testFilesPath . '/TestService.php';
        File::put($testFile, <<<'PHP'
<?php

namespace App\Services;

use App\Models\ExampleModelDynamoDB;

class TestService
{
    public function getAllItems()
    {
        return ExampleModelDynamoDB::all();
    }
}
PHP
        );

        $this->artisan('dynamodb:audit-scans', ['--path' => $this->testFilesPath])
            ->expectsOutputToContain('all()')
            ->assertSuccessful();
    }

    /**
     * Test command detects get() without where pattern.
     */
    public function test_command_detects_get_without_where_pattern(): void
    {
        $testFile = $this->testFilesPath . '/TestService.php';
        File::put($testFile, <<<'PHP'
<?php

namespace App\Services;

use App\Models\ExampleModelDynamoDB;

class TestService
{
    public function getItems()
    {
        return ExampleModelDynamoDB::get();
    }
}
PHP
        );

        $this->artisan('dynamodb:audit-scans', ['--path' => $this->testFilesPath])
            ->expectsOutputToContain('get()')
            ->assertSuccessful();
    }

    /**
     * Test command shows fix suggestions with --fix option.
     */
    public function test_command_shows_fix_suggestions_with_fix_option(): void
    {
        $testFile = $this->testFilesPath . '/TestService.php';
        File::put($testFile, <<<'PHP'
<?php

namespace App\Services;

use App\Models\ExampleModelDynamoDB;

class TestService
{
    public function getAllItems()
    {
        return ExampleModelDynamoDB::all();
    }
}
PHP
        );

        $this->artisan('dynamodb:audit-scans', [
            '--path' => $this->testFilesPath,
            '--fix' => true,
        ])
            ->expectsOutputToContain('Fix:')
            ->assertSuccessful();
    }

    /**
     * Test command outputs JSON with --json option.
     */
    public function test_command_outputs_json_with_json_option(): void
    {
        // Use Artisan facade to capture output properly
        \Illuminate\Support\Facades\Artisan::call('dynamodb:audit-scans', ['--json' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();

        $this->assertStringContainsString('"models"', $output);
        $this->assertStringContainsString('total_issues', $output);

        // Verify it's valid JSON
        $decoded = json_decode($output, true);
        $this->assertNotNull($decoded, 'Output should be valid JSON');
        $this->assertArrayHasKey('models', $decoded);
        $this->assertArrayHasKey('summary', $decoded);
    }

    /**
     * Test command fails with strict option when issues found.
     */
    public function test_command_fails_with_strict_option_when_issues_found(): void
    {
        $testFile = $this->testFilesPath . '/TestService.php';
        File::put($testFile, <<<'PHP'
<?php

namespace App\Services;

use App\Models\ExampleModelDynamoDB;

class TestService
{
    public function getAllItems()
    {
        return ExampleModelDynamoDB::all();
    }
}
PHP
        );

        $this->artisan('dynamodb:audit-scans', [
            '--path' => $this->testFilesPath,
            '--strict' => true,
        ])->assertFailed();
    }

    /**
     * Test command succeeds with strict option when no issues found.
     */
    public function test_command_succeeds_with_strict_option_when_no_issues(): void
    {
        // Create a clean file with no scan patterns
        $testFile = $this->testFilesPath . '/CleanService.php';
        File::put($testFile, <<<'PHP'
<?php

namespace App\Services;

class CleanService
{
    public function doSomething()
    {
        return 'hello';
    }
}
PHP
        );

        $this->artisan('dynamodb:audit-scans', [
            '--path' => $this->testFilesPath,
            '--strict' => true,
        ])->assertSuccessful();
    }

    /**
     * Test command ignores comments.
     */
    public function test_command_ignores_comments(): void
    {
        $testFile = $this->testFilesPath . '/CommentedService.php';
        File::put($testFile, <<<'PHP'
<?php

namespace App\Services;

use App\Models\ExampleModelDynamoDB;

class CommentedService
{
    // This is a comment: ExampleModelDynamoDB::all()
    /* Another comment: ExampleModelDynamoDB::all() */

    /**
     * ExampleModelDynamoDB::all()
     */
    public function doSomething()
    {
        return 'hello';
    }
}
PHP
        );

        $this->artisan('dynamodb:audit-scans', [
            '--path' => $this->testFilesPath,
            '--strict' => true,
        ])->assertSuccessful();
    }

    /**
     * Test command reports scan prevention status in JSON output.
     */
    public function test_command_reports_scan_prevention_status_in_json(): void
    {
        $this->artisan('dynamodb:audit-scans', ['--json' => true])
            ->expectsOutputToContain('"uses_scan_prevention"')
            ->assertSuccessful();
    }

    /**
     * Test command reports correct severity levels.
     */
    public function test_command_reports_severity_levels(): void
    {
        $testFile = $this->testFilesPath . '/TestService.php';
        File::put($testFile, <<<'PHP'
<?php

namespace App\Services;

use App\Models\ExampleModelDynamoDB;

class TestService
{
    public function getAllItems()
    {
        return ExampleModelDynamoDB::all();
    }
}
PHP
        );

        // Run without --json to check table output which shows severity
        $this->artisan('dynamodb:audit-scans', [
            '--path' => $this->testFilesPath,
        ])
            ->expectsOutputToContain('high')
            ->assertSuccessful();
    }

    /**
     * Test command handles non-existent path gracefully.
     */
    public function test_command_handles_nonexistent_path_gracefully(): void
    {
        $this->artisan('dynamodb:audit-scans', [
            '--path' => '/nonexistent/path/that/does/not/exist',
        ])->assertSuccessful();
    }

    /**
     * Test command detects chunk() pattern.
     */
    public function test_command_detects_chunk_pattern(): void
    {
        $testFile = $this->testFilesPath . '/TestService.php';
        File::put($testFile, <<<'PHP'
<?php

namespace App\Services;

use App\Models\ExampleModelDynamoDB;

class TestService
{
    public function processItems()
    {
        ExampleModelDynamoDB::query()->chunk(100, function ($items) {
            // process
        });
    }
}
PHP
        );

        $this->artisan('dynamodb:audit-scans', ['--path' => $this->testFilesPath])
            ->expectsOutputToContain('chunk()')
            ->assertSuccessful();
    }

    /**
     * Test command detects count() pattern.
     */
    public function test_command_detects_count_pattern(): void
    {
        $testFile = $this->testFilesPath . '/TestService.php';
        File::put($testFile, <<<'PHP'
<?php

namespace App\Services;

use App\Models\ExampleModelDynamoDB;

class TestService
{
    public function countItems()
    {
        return ExampleModelDynamoDB::query()->count();
    }
}
PHP
        );

        $this->artisan('dynamodb:audit-scans', ['--path' => $this->testFilesPath])
            ->expectsOutputToContain('count()')
            ->assertSuccessful();
    }
}
