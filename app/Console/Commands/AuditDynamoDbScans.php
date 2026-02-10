<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;

class AuditDynamoDbScans extends Command
{
    protected $signature = 'dynamodb:audit-scans
                            {--path= : Specific path to audit (default: app/)}
                            {--model= : Audit specific model class}
                            {--fix : Show suggestions for fixing issues}
                            {--json : Output results as JSON}
                            {--strict : Exit with error code if issues found}';

    protected $description = 'Audit codebase for potential DynamoDB scan operations';

    /**
     * Patterns that typically indicate a scan operation.
     */
    protected array $scanPatterns = [
        // Direct model calls without where on primary key
        'all()' => [
            'pattern' => '/(\w+)::all\s*\(\s*\)/',
            'description' => 'Model::all() performs a full table scan',
            'severity' => 'high',
            'fix' => 'Use a query with where clause on primary key or GSI',
        ],
        'get() without where' => [
            'pattern' => '/(\w+)::get\s*\(\s*\)/',
            'description' => 'Model::get() without where clause performs a scan',
            'severity' => 'high',
            'fix' => 'Add where clause on primary key or GSI before get()',
        ],
        'first() without where' => [
            'pattern' => '/(\w+)::first\s*\(\s*\)/',
            'description' => 'Model::first() without where clause performs a scan',
            'severity' => 'medium',
            'fix' => 'Add where clause on primary key or GSI before first()',
        ],
        'count() without where' => [
            'pattern' => '/->count\s*\(\s*\)/',
            'description' => 'count() without key conditions performs a scan',
            'severity' => 'medium',
            'fix' => 'Add where clause on primary key or GSI before count()',
        ],
        'chunk() usage' => [
            'pattern' => '/->chunk\s*\(/',
            'description' => 'chunk() may perform multiple scans if no key conditions',
            'severity' => 'medium',
            'fix' => 'Ensure where clause on primary key or GSI is present',
        ],
        'paginate/cursor' => [
            'pattern' => '/->(paginate|cursor)\s*\(/',
            'description' => 'Pagination without key conditions performs scans',
            'severity' => 'medium',
            'fix' => 'DynamoDB pagination should use afterKey() with key conditions',
        ],
        'pluck without where' => [
            'pattern' => '/(\w+)::pluck\s*\(/',
            'description' => 'Model::pluck() without where clause performs a scan',
            'severity' => 'high',
            'fix' => 'Add where clause on primary key or GSI before pluck()',
        ],
        'each() iteration' => [
            'pattern' => '/->each\s*\(/',
            'description' => 'each() may iterate over entire table via scan',
            'severity' => 'medium',
            'fix' => 'Ensure where clause on primary key or GSI is present',
        ],
    ];

    /**
     * Safe patterns that indicate proper query usage.
     */
    protected array $safePatterns = [
        '/->find\s*\(/',           // find() uses GetItem
        '/->findOrFail\s*\(/',     // findOrFail() uses GetItem
        '/->where\s*\([\'"]/',     // Has where clause (potential Query)
        '/->withIndex\s*\(/',      // Explicitly using an index
    ];

    protected array $issues = [];
    protected array $dynamoDbModels = [];

    public function handle(): int
    {
        $jsonOutput = $this->option('json');

        if (!$jsonOutput) {
            $this->info('Auditing DynamoDB usage for potential scan operations...');
            $this->newLine();
        }

        // Discover DynamoDB models
        $this->discoverDynamoDbModels();

        if (empty($this->dynamoDbModels)) {
            if ($jsonOutput) {
                $this->outputJson();
            } else {
                $this->warn('No DynamoDB models found in the application.');
            }
            return self::SUCCESS;
        }

        if (!$jsonOutput) {
            $this->info('Found ' . count($this->dynamoDbModels) . ' DynamoDB model(s):');
            foreach ($this->dynamoDbModels as $model => $info) {
                $this->line("  - {$model}");
                $this->line("    Table: {$info['table']}, Primary Key: {$info['primary_key']}");
                if (!empty($info['indexes'])) {
                    $this->line("    GSIs: " . implode(', ', array_keys($info['indexes'])));
                }
            }
            $this->newLine();
        }

        // Audit the codebase
        $path = $this->option('path') ?: app_path();
        $this->auditPath($path, !$jsonOutput);

        // Output results
        if ($jsonOutput) {
            $this->outputJson();
        } else {
            $this->outputTable();
        }

        if ($this->option('strict') && !empty($this->issues)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function discoverDynamoDbModels(): void
    {
        $modelClass = $this->option('model');

        if ($modelClass) {
            $this->analyzeModel($modelClass);
            return;
        }

        // Scan Models directory
        $modelPath = app_path('Models');
        if (!File::isDirectory($modelPath)) {
            return;
        }

        $files = File::allFiles($modelPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->getClassNameFromFile($file->getPathname());
            if ($className) {
                $this->analyzeModel($className);
            }
        }
    }

    protected function analyzeModel(string $className): void
    {
        if (!class_exists($className)) {
            return;
        }

        try {
            $reflection = new ReflectionClass($className);

            // Check if it extends DynamoDbModel
            if (!$reflection->isSubclassOf(\BaoPham\DynamoDb\DynamoDbModel::class)) {
                return;
            }

            $instance = $reflection->newInstanceWithoutConstructor();

            // Get table name (with fallback)
            $table = $this->snakeCase($reflection->getShortName());
            if ($reflection->hasProperty('table')) {
                $tableProperty = $reflection->getProperty('table');
                $tableProperty->setAccessible(true);
                $table = $tableProperty->getValue($instance) ?? $table;
            }

            // Get primary key (with fallback)
            $primaryKey = 'id';
            if ($reflection->hasProperty('primaryKey')) {
                $keyProperty = $reflection->getProperty('primaryKey');
                $keyProperty->setAccessible(true);
                $primaryKey = $keyProperty->getValue($instance) ?? $primaryKey;
            }

            // Get GSI indexes
            $indexes = [];
            if ($reflection->hasProperty('dynamoDbIndexKeys')) {
                $indexProperty = $reflection->getProperty('dynamoDbIndexKeys');
                $indexProperty->setAccessible(true);
                $indexes = $indexProperty->getValue($instance) ?? [];
            }

            // Check if using PreventsDynamoDbScans trait (including inherited from parent)
            $usesScanPrevention = in_array(
                \App\DynamoDb\PreventsDynamoDbScans::class,
                array_keys(class_uses_recursive($className))
            );

            $this->dynamoDbModels[$className] = [
                'table' => $table,
                'primary_key' => $primaryKey,
                'indexes' => $indexes,
                'uses_scan_prevention' => $usesScanPrevention,
            ];
        } catch (\Throwable $e) {
            $this->warn("Could not analyze model {$className}: " . $e->getMessage());
        }
    }

    protected function auditPath(string $path, bool $showProgress = true): void
    {
        if (!File::isDirectory($path)) {
            if (File::isFile($path)) {
                $this->auditFile($path);
            }
            return;
        }

        $files = File::allFiles($path);

        if ($showProgress) {
            $this->output->progressStart(count($files));
        }

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $this->auditFile($file->getPathname());
            }
            if ($showProgress) {
                $this->output->progressAdvance();
            }
        }

        if ($showProgress) {
            $this->output->progressFinish();
        }
    }

    protected function auditFile(string $filePath): void
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);

        // Check if file uses any DynamoDB models
        $usesModels = [];
        foreach ($this->dynamoDbModels as $model => $info) {
            $shortName = class_basename($model);
            if (
                str_contains($content, "use {$model}") ||
                str_contains($content, "{$shortName}::") ||
                str_contains($content, "new {$shortName}")
            ) {
                $usesModels[$shortName] = $model;
            }
        }

        if (empty($usesModels)) {
            return;
        }

        foreach ($lines as $lineNumber => $line) {
            $this->auditLine($filePath, $lineNumber + 1, $line, $usesModels);
        }
    }

    protected function auditLine(string $file, int $lineNumber, string $line, array $usesModels): void
    {
        // Skip comments
        $trimmedLine = trim($line);
        if (str_starts_with($trimmedLine, '//') || str_starts_with($trimmedLine, '*') || str_starts_with($trimmedLine, '/*')) {
            return;
        }

        // Check for scan patterns
        foreach ($this->scanPatterns as $name => $config) {
            if (preg_match($config['pattern'], $line, $matches)) {
                // Check if this is actually a DynamoDB model
                $isDbModel = false;
                $modelName = $matches[1] ?? null;

                if ($modelName && isset($usesModels[$modelName])) {
                    $isDbModel = true;
                }

                // Also check for chained calls on DynamoDB models
                foreach ($usesModels as $shortName => $fullName) {
                    if (str_contains($line, $shortName)) {
                        $isDbModel = true;
                        $modelName = $shortName;
                        break;
                    }
                }

                if ($isDbModel || $this->lineReferencesModel($line, $usesModels)) {
                    // Check if there's a safe pattern (where clause, find, withIndex) on this line
                    if (!$this->hasSafePattern($line)) {
                        $this->issues[] = [
                            'file' => $file,
                            'line' => $lineNumber,
                            'code' => trim($line),
                            'issue' => $name,
                            'description' => $config['description'],
                            'severity' => $config['severity'],
                            'fix' => $config['fix'],
                            'model' => $modelName,
                        ];
                    }
                }
            }
        }
    }

    protected function lineReferencesModel(string $line, array $usesModels): bool
    {
        foreach ($usesModels as $shortName => $fullName) {
            if (str_contains($line, $shortName)) {
                return true;
            }
        }
        return false;
    }

    protected function hasSafePattern(string $line): bool
    {
        // Check if line contains patterns that indicate a Query (not Scan) operation
        foreach ($this->safePatterns as $safePattern) {
            if (preg_match($safePattern, $line)) {
                return true;
            }
        }

        // Also check for chained where before terminal methods
        if (preg_match('/->where\s*\(.*\).*->(get|first|all|count|chunk|each|pluck)\s*\(/', $line)) {
            return true;
        }

        return false;
    }

    protected function outputTable(): void
    {
        if (empty($this->issues)) {
            $this->newLine();
            $this->info('No potential scan issues found.');

            // Check for models without scan prevention
            $unprotected = array_filter($this->dynamoDbModels, fn($m) => !$m['uses_scan_prevention']);
            if (!empty($unprotected)) {
                $this->newLine();
                $this->warn('Models without PreventsDynamoDbScans trait:');
                foreach ($unprotected as $model => $info) {
                    $this->line("  - {$model}");
                }
                $this->line('  Consider adding: use App\DynamoDb\PreventsDynamoDbScans;');
            }

            return;
        }

        $this->newLine();
        $this->error('Found ' . count($this->issues) . ' potential scan issue(s):');
        $this->newLine();

        $grouped = collect($this->issues)->groupBy('severity');

        foreach (['high', 'medium', 'low'] as $severity) {
            if (!isset($grouped[$severity])) {
                continue;
            }

            $color = match ($severity) {
                'high' => 'red',
                'medium' => 'yellow',
                default => 'white',
            };

            $this->line("<fg={$color};options=bold>[{$severity}]</>");

            foreach ($grouped[$severity] as $issue) {
                $relativePath = str_replace(base_path() . '/', '', $issue['file']);
                $this->line("  <fg=cyan>{$relativePath}:{$issue['line']}</>");
                $this->line("    Issue: {$issue['issue']}");
                $this->line("    Code: <fg=gray>{$issue['code']}</>");

                if ($this->option('fix')) {
                    $this->line("    Fix: <fg=green>{$issue['fix']}</>");
                }

                $this->newLine();
            }
        }

        // Summary
        $this->table(
            ['Severity', 'Count'],
            [
                ['High', count($grouped['high'] ?? [])],
                ['Medium', count($grouped['medium'] ?? [])],
                ['Low', count($grouped['low'] ?? [])],
            ]
        );
    }

    protected function outputJson(): void
    {
        $output = [
            'models' => $this->dynamoDbModels,
            'issues' => $this->issues,
            'summary' => [
                'total_issues' => count($this->issues),
                'high' => count(array_filter($this->issues, fn($i) => $i['severity'] === 'high')),
                'medium' => count(array_filter($this->issues, fn($i) => $i['severity'] === 'medium')),
                'low' => count(array_filter($this->issues, fn($i) => $i['severity'] === 'low')),
            ],
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    protected function getClassNameFromFile(string $filePath): ?string
    {
        $content = File::get($filePath);

        // Extract namespace
        if (preg_match('/namespace\s+([^;]+);/', $content, $nsMatches)) {
            $namespace = $nsMatches[1];
        } else {
            return null;
        }

        // Extract class name
        if (preg_match('/class\s+(\w+)/', $content, $classMatches)) {
            return $namespace . '\\' . $classMatches[1];
        }

        return null;
    }

    protected function snakeCase(string $value): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }
}
