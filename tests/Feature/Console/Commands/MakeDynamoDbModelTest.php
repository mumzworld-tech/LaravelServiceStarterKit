<?php

namespace Tests\Feature\Console\Commands;

use Tests\TestCase;
use Illuminate\Support\Facades\File;

class MakeDynamoDbModelTest extends TestCase
{
    protected string $modelsPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modelsPath = app_path('Models');
    }

    protected function tearDown(): void
    {
        // Clean up any test models created
        $testFiles = File::glob($this->modelsPath . '/TestDynamoDb*.php');
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
        $this->artisan('make:dynamodb-model', ['--help' => true])
            ->assertSuccessful();
    }

    /**
     * Test command requires name argument.
     */
    public function test_command_requires_name_argument(): void
    {
        $this->artisan('make:dynamodb-model', ['--help' => true])
            ->expectsOutputToContain('name');
    }

    /**
     * Test command has table option.
     */
    public function test_command_has_table_option(): void
    {
        $this->artisan('make:dynamodb-model', ['--help' => true])
            ->expectsOutputToContain('--table');
    }

    /**
     * Test command has key option.
     */
    public function test_command_has_key_option(): void
    {
        $this->artisan('make:dynamodb-model', ['--help' => true])
            ->expectsOutputToContain('--key');
    }

    /**
     * Test command creates model file.
     */
    public function test_command_creates_model_file(): void
    {
        $modelName = 'TestDynamoDbModel' . time();

        $this->artisan('make:dynamodb-model', [
            'name' => $modelName,
            '--table' => 'test_table',
            '--key' => 'test_id',
        ])->assertSuccessful();

        $modelPath = $this->modelsPath . '/' . $modelName . '.php';
        $this->assertFileExists($modelPath, 'Model file should be created');

        // Clean up
        File::delete($modelPath);
    }

    /**
     * Test command outputs success message.
     */
    public function test_command_outputs_success_message(): void
    {
        $modelName = 'TestDynamoDbModelSuccess' . time();

        $this->artisan('make:dynamodb-model', [
            'name' => $modelName,
            '--table' => 'test_table',
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('created successfully');

        // Clean up
        $modelPath = $this->modelsPath . '/' . $modelName . '.php';
        if (File::exists($modelPath)) {
            File::delete($modelPath);
        }
    }

    /**
     * Test created model contains correct namespace.
     */
    public function test_created_model_contains_correct_namespace(): void
    {
        $modelName = 'TestDynamoDbModelNamespace' . time();

        $this->artisan('make:dynamodb-model', [
            'name' => $modelName,
            '--table' => 'test_table',
        ])->assertSuccessful();

        $modelPath = $this->modelsPath . '/' . $modelName . '.php';
        $content = File::get($modelPath);

        $this->assertStringContainsString('namespace App\\Models;', $content);

        // Clean up
        File::delete($modelPath);
    }
}
