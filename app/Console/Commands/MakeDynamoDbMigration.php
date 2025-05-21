<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeDynamoDbMigration extends GeneratorCommand
{
    protected $name = 'make:dynamodb-migration';
    protected $description = 'Create a new DynamoDB migration file';
    protected $type = 'DynamoDB Migration';

    protected function getStub()
    {
        return __DIR__ . '/stubs/dynamodb_migration.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        // This command doesn't generate a class in the app namespace directly
        // It generates a file in database/migrations_dynamodb
        return $rootNamespace;
    }

    protected function getPath($name)
    {
        // Name is not used for class name here, but for the filename
        // We use the argument for the filename directly.
        $fileName = Str::snake(trim($this->argument('name')));
        $timestamp = date('Y_m_d_His');
        return database_path('migrations_dynamodb') . '/' . $timestamp . '_' . $fileName . '.php';
    }

    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());
        $tableName = $this->option('table') ?: 'default_table_name'; // Default if not provided
        return str_replace(['{{tableName}}', '{{TableName}}'], [$tableName, Str::studly($tableName)], $stub);
    }

    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the migration (e.g., create_users_table).'],
        ];
    }

    protected function getOptions()
    {
        return [
            ['table', null, InputOption::VALUE_OPTIONAL, 'The table to be created/modified by the migration.'],
        ];
    }

    public function handle()
    {
        parent::handle();
        $this->info($this->type . ' created successfully.');
    }
} 