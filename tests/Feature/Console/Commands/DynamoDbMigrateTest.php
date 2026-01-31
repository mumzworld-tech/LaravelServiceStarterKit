<?php

namespace Tests\Feature\Console\Commands;

use Tests\TestCase;
use Illuminate\Support\Facades\File;

class DynamoDbMigrateTest extends TestCase
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

    /**
     * Test command exists and is registered.
     */
    public function test_command_is_registered(): void
    {
        $this->artisan('migrate:dynamodb', ['--help' => true])
            ->assertSuccessful();
    }

    /**
     * Test command with non-existent specific file.
     */
    public function test_command_fails_with_non_existent_file(): void
    {
        $this->artisan('migrate:dynamodb', ['--file' => 'non_existent_migration.php'])
            ->assertFailed()
            ->expectsOutput('Migration file not found: non_existent_migration.php');
    }

    /**
     * Test command runs successfully with no migrations.
     */
    public function test_command_runs_successfully_with_no_migrations(): void
    {
        // Temporarily move existing migrations if any
        $existingFiles = File::glob($this->migrationsPath . '/*_*.php');
        $tempDir = sys_get_temp_dir() . '/dynamodb_migrations_backup_' . time();

        if (count($existingFiles) > 0) {
            File::makeDirectory($tempDir, 0755, true);
            foreach ($existingFiles as $file) {
                File::move($file, $tempDir . '/' . basename($file));
            }
        }

        try {
            $this->artisan('migrate:dynamodb')
                ->assertSuccessful()
                ->expectsOutput('DynamoDB migration completed successfully.');
        } finally {
            // Restore migrations
            if (isset($tempDir) && File::exists($tempDir)) {
                $backupFiles = File::glob($tempDir . '/*_*.php');
                foreach ($backupFiles as $file) {
                    File::move($file, $this->migrationsPath . '/' . basename($file));
                }
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Test command has fresh option.
     */
    public function test_command_has_fresh_option(): void
    {
        $this->artisan('migrate:dynamodb', ['--help' => true])
            ->expectsOutputToContain('--fresh');
    }

    /**
     * Test command has file option.
     */
    public function test_command_has_file_option(): void
    {
        $this->artisan('migrate:dynamodb', ['--help' => true])
            ->expectsOutputToContain('--file');
    }
}
