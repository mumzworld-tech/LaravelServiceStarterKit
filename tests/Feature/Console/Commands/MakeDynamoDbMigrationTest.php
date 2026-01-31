<?php

namespace Tests\Feature\Console\Commands;

use Tests\TestCase;
use Illuminate\Support\Facades\File;

class MakeDynamoDbMigrationTest extends TestCase
{
    protected string $migrationsPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrationsPath = database_path('migrations_dynamodb');

        // Ensure migrations directory exists
        if (!File::exists($this->migrationsPath)) {
            File::makeDirectory($this->migrationsPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up any test migrations created
        $testFiles = File::glob($this->migrationsPath . '/*test_migration*.php');
        foreach ($testFiles as $file) {
            File::delete($file);
        }

        parent::tearDown();
    }

    /**
     * Test command exists and is registered.
     */
    public function test_command_is_registered(): void
    {
        $this->artisan('make:dynamodb-migration', ['--help' => true])
            ->assertSuccessful();
    }

    /**
     * Test command requires name argument.
     */
    public function test_command_requires_name_argument(): void
    {
        $this->artisan('make:dynamodb-migration', ['--help' => true])
            ->expectsOutputToContain('name');
    }

    /**
     * Test command has table option.
     */
    public function test_command_has_table_option(): void
    {
        $this->artisan('make:dynamodb-migration', ['--help' => true])
            ->expectsOutputToContain('--table');
    }

    /**
     * Test command creates migration file.
     */
    public function test_command_creates_migration_file(): void
    {
        $migrationName = 'test_migration_' . time();

        $this->artisan('make:dynamodb-migration', [
            'name' => $migrationName,
            '--table' => 'test_table',
        ])->assertSuccessful();

        // Check that file was created
        $files = File::glob($this->migrationsPath . '/*' . $migrationName . '.php');
        $this->assertNotEmpty($files, 'Migration file should be created');

        // Clean up
        foreach ($files as $file) {
            File::delete($file);
        }
    }

    /**
     * Test command outputs success message.
     */
    public function test_command_outputs_success_message(): void
    {
        $migrationName = 'test_migration_success_' . time();

        $this->artisan('make:dynamodb-migration', [
            'name' => $migrationName,
            '--table' => 'test_table',
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('created successfully');

        // Clean up
        $files = File::glob($this->migrationsPath . '/*' . $migrationName . '.php');
        foreach ($files as $file) {
            File::delete($file);
        }
    }
}
