<?php

namespace App\Http\Controllers;

use Exception;
use ErrorException;
use LogicException;
use RuntimeException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TypeError;

/**
 * Custom exception for demonstration purposes
 */
class CustomException extends Exception
{
    public function __construct($message = "Custom exception", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class DebugController extends Controller
{
    /**
     * Trigger all error types at once
     */
    public function index()
    {
        // This will execute all error types at once as a combined chaos error
        try {
            // Start with a division by zero, which will never reach the next errors
            $a = 1 / 0;
            
            // The code below is unreachable, but shows what would happen in sequence
            $varName = 'undefinedVar';
            $b = ${$varName};
            
            $array = [];
            $c = $array[999];
            
            // Create a composite exception with multiple nested exceptions
            $divisionError = new ErrorException("Division by zero");
            $typeError = new TypeError("Type error", 0, $divisionError);
            $logicError = new LogicException("Logic exception", 0, $typeError);
            $runtimeError = new RuntimeException("Runtime exception", 0, $logicError);
            $queryError = new QueryException("SQL", "SELECT * FROM non_existent_table", [], new \Exception("Database error"));
            $httpError = new HttpException(500, "HTTP exception", $queryError);
            $customError = new CustomException("Custom exception", 0, $httpError);
            
            throw $customError;
        } catch (\Throwable $e) {
            // This will never be reached for division by zero, but would show the exception chain
            throw $e;
        }
        
        // This code is never reached
        return "This should never be reached";
    }

    /**
     * Trigger division by zero error
     */
    public function divisionByZero()
    {
        $numerator = 10;
        $denominator = 0;
        $result = $numerator / $denominator;

        return "This won't be reached: $result";
    }

    /**
     * Trigger undefined variable error
     */
    public function undefinedVariable()
    {
        // We're intentionally causing an error here
        // Bypass linter with variable variable to still cause an error at runtime
        $varName = 'undefinedVar';
        $a = ${$varName};
        
        return "This won't be reached: $a";
    }

    /**
     * Trigger a TypeError
     */
    public function typeError()
    {
        $expectsString = function(string $input): string {
            return "Got string: $input";
        };
        
        return $expectsString(123);
    }

    /**
     * Trigger an array out of bounds error
     */
    public function outOfBounds()
    {
        $array = [1, 2, 3];
        $value = $array[999];
        
        return "This won't be reached: $value";
    }

    /**
     * Throw a LogicException
     */
    public function logicException()
    {
        throw new LogicException('This is a logic exception');
    }

    /**
     * Throw a RuntimeException
     */
    public function runtimeException()
    {
        throw new RuntimeException('This is a runtime exception');
    }

    /**
     * Trigger a database query exception
     */
    public function queryException()
    {
        DB::select('SELECT * FROM non_existent_table');
        
        return "This won't be reached";
    }

    /**
     * Throw an HTTP exception
     */
    public function httpException()
    {
        throw new HttpException(500, 'This is a HTTP exception');
    }

    /**
     * Simulate hitting memory limit
     * (Note: Actually hitting the limit would crash the server)
     */
    public function memoryLimit()
    {
        // Instead of actually consuming all memory, we'll throw an error that simulates it
        throw new ErrorException('Allowed memory size of 134217728 bytes exhausted (tried to allocate 262144 bytes)');
    }

    /**
     * Example of code that would cause a parse error
     * (Note: This won't actually be executed since parse errors happen at compile time)
     */
    public function parseErrorExample()
    {
        return "This is what would cause a parse error if uncommented: <pre>
        <?php
        echo 'This will cause a parse error because of the missing semicolon'
        echo 'This is the next line';
        ?>
        </pre>";
    }

    /**
     * Trigger a fatal error
     */
    public function fatalError()
    {
        // Call to undefined function - using eval to bypass linter but still cause the error at runtime
        eval('non_existent_function();');
        
        return "This won't be reached";
    }

    /**
     * Throw a custom exception
     */
    public function customException()
    {
        throw new CustomException('This is a custom exception');
    }

    /**
     * Randomly trigger one of the error types
     */
    public function randomError()
    {
        $methods = [
            'divisionByZero',
            'undefinedVariable',
            'typeError',
            'outOfBounds',
            'logicException',
            'runtimeException',
            'queryException',
            'httpException',
            'memoryLimit',
            'fatalError',
            'customException'
        ];
        
        
        $randomMethod = $methods[array_rand($methods)];
        return $this->$randomMethod();
    }
} 