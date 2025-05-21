<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeDynamoDbModel extends GeneratorCommand
{
    protected $name = 'make:dynamodb-model';
    protected $description = 'Create a new DynamoDB model class';
    protected $type = 'DynamoDB Model';

    protected function getStub()
    {
        return __DIR__ . '/stubs/dynamodb_model.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\Models';
    }

    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);
        $table = $this->option('table') ?: Str::snake(Str::pluralStudly(class_basename($name)));
        $primaryKey = $this->option('key') ?: 'id';

        $stub = str_replace('{{table}}', $table, $stub);
        $stub = str_replace('{{primaryKey}}', $primaryKey, $stub);
        // Add a placeholder for GSI if needed, or make it more interactive later
        $stub = str_replace('{{dynamoDbIndexKeys}}', '[]', $stub); 

        return $stub;
    }

    protected function getOptions()
    {
        return [
            ['table', 't', InputOption::VALUE_OPTIONAL, 'The DynamoDB table name for the model.'],
            ['key', 'k', InputOption::VALUE_OPTIONAL, 'The primary key (hash key) for the model.'],
        ];
    }

    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the model (e.g., ProductReview).'],
        ];
    }

    public function handle()
    {
        if (parent::handle() === false && ! $this->option('force')) {
            return false;
        }
        $this->info($this->type . ' created successfully.');
    }
} 