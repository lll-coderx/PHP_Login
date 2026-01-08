#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * DoomStack VM Runner
 * 
 * CLI tool to execute DoomStack programs
 * 
 * Usage: php doomstack.php <program_file> [input]
 */

require_once __DIR__ . '/src/DoomStack/Interpreter.php';

use App\DoomStack\Interpreter;

if ($argc < 2) {
    echo "DoomStack VM - Stack Machine of Doom\n";
    echo "Usage: php doomstack.php <program_file> [input]\n";
    echo "\n";
    echo "Arguments:\n";
    echo "  program_file  Path to a .doomstack program file\n";
    echo "  input         Optional input string for READC operations\n";
    echo "\n";
    echo "Example:\n";
    echo "  php doomstack.php programs/flag_program.doomstack\n";
    exit(1);
}

$programFile = $argv[1];
$input = $argv[2] ?? '';

if (!file_exists($programFile)) {
    echo "Error: File not found: {$programFile}\n";
    exit(1);
}

try {
    $interpreter = new Interpreter();
    $interpreter->loadFromFile($programFile);
    $interpreter->setInput($input);
    
    echo "Running DoomStack program: {$programFile}\n";
    echo "---\n";
    
    $output = $interpreter->run();
    
    echo $output;
    echo "\n---\n";
    echo "Program completed successfully.\n";
} catch (\RuntimeException $e) {
    echo "Runtime Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
