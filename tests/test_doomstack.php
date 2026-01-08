<?php

declare(strict_types=1);

/**
 * DoomStack VM Interpreter Test Suite
 * 
 * Tests the DoomStack stack-based virtual machine interpreter
 */

echo "=== DoomStack VM Test Suite ===\n\n";

require_once __DIR__ . '/../src/DoomStack/Interpreter.php';

use App\DoomStack\Interpreter;

$testsPassed = 0;
$testsFailed = 0;

function runTest(string $name, callable $test): void
{
    global $testsPassed, $testsFailed;
    try {
        $test();
        echo "✓ {$name}\n";
        $testsPassed++;
    } catch (\Throwable $e) {
        echo "✗ {$name}: " . $e->getMessage() . "\n";
        $testsFailed++;
    }
}

// Test 1: Basic PUSH and WRITEC
runTest("PUSH and WRITEC", function () {
    $vm = new Interpreter();
    $vm->parse("PUSH 65\nWRITEC");
    $output = $vm->run();
    assert($output === 'A', "Expected 'A', got '{$output}'");
});

// Test 2: Multiple PUSH and WRITEC
runTest("Multiple PUSH and WRITEC", function () {
    $vm = new Interpreter();
    $vm->parse("PUSH 72\nWRITEC\nPUSH 105\nWRITEC");
    $output = $vm->run();
    assert($output === 'Hi', "Expected 'Hi', got '{$output}'");
});

// Test 3: ADD operation
runTest("ADD operation", function () {
    $vm = new Interpreter();
    $vm->parse("PUSH 30\nPUSH 35\nADD\nWRITEC");
    $output = $vm->run();
    assert($output === 'A', "Expected 'A' (65), got '{$output}'");
});

// Test 4: SUB operation
runTest("SUB operation", function () {
    $vm = new Interpreter();
    $vm->parse("PUSH 70\nPUSH 5\nSUB\nWRITEC");
    $output = $vm->run();
    assert($output === 'A', "Expected 'A' (65), got '{$output}'");
});

// Test 5: MUL operation
runTest("MUL operation", function () {
    $vm = new Interpreter();
    $vm->parse("PUSH 13\nPUSH 5\nMUL\nWRITEC");
    $output = $vm->run();
    assert($output === 'A', "Expected 'A' (65), got '{$output}'");
});

// Test 6: DIV operation
runTest("DIV operation", function () {
    $vm = new Interpreter();
    $vm->parse("PUSH 130\nPUSH 2\nDIV\nWRITEC");
    $output = $vm->run();
    assert($output === 'A', "Expected 'A' (65), got '{$output}'");
});

// Test 7: MOD operation
runTest("MOD operation", function () {
    $vm = new Interpreter();
    $vm->parse("PUSH 165\nPUSH 100\nMOD\nWRITEC");
    $output = $vm->run();
    assert($output === 'A', "Expected 'A' (65), got '{$output}'");
});

// Test 8: DUP operation
runTest("DUP operation", function () {
    $vm = new Interpreter();
    $vm->parse("PUSH 65\nDUP\nWRITEC\nWRITEC");
    $output = $vm->run();
    assert($output === 'AA', "Expected 'AA', got '{$output}'");
});

// Test 9: SWAP operation
runTest("SWAP operation", function () {
    $vm = new Interpreter();
    $vm->parse("PUSH 65\nPUSH 66\nSWAP\nWRITEC\nWRITEC");
    $output = $vm->run();
    assert($output === 'AB', "Expected 'AB', got '{$output}'");
});

// Test 10: POP operation
runTest("POP operation", function () {
    $vm = new Interpreter();
    $vm->parse("PUSH 99\nPUSH 65\nPOP\nWRITEC");
    $output = $vm->run();
    assert($output === 'c', "Expected 'c' (99), got '{$output}'");
});

// Test 11: READC operation
runTest("READC operation", function () {
    $vm = new Interpreter();
    $vm->parse("READC\nWRITEC");
    $vm->setInput("X");
    $output = $vm->run();
    assert($output === 'X', "Expected 'X', got '{$output}'");
});

// Test 12: JMP operation
runTest("JMP operation", function () {
    $vm = new Interpreter();
    $program = "PUSH 66\nJMP skip\nPUSH 65\nWRITEC\nLABEL skip\nWRITEC";
    $vm->parse($program);
    $output = $vm->run();
    assert($output === 'B', "Expected 'B', got '{$output}'");
});

// Test 13: JZ operation (zero condition)
runTest("JZ operation (zero)", function () {
    $vm = new Interpreter();
    $program = "PUSH 65\nPUSH 0\nJZ print_a\nPUSH 66\nWRITEC\nJMP end\nLABEL print_a\nWRITEC\nLABEL end";
    $vm->parse($program);
    $output = $vm->run();
    assert($output === 'A', "Expected 'A', got '{$output}'");
});

// Test 14: JZ operation (non-zero condition)
runTest("JZ operation (non-zero)", function () {
    $vm = new Interpreter();
    $program = "PUSH 65\nPUSH 1\nJZ print_a\nPUSH 66\nWRITEC\nJMP end\nLABEL print_a\nWRITEC\nLABEL end";
    $vm->parse($program);
    $output = $vm->run();
    assert($output === 'B', "Expected 'B', got '{$output}'");
});

// Test 15: Comments are ignored
runTest("Comments ignored", function () {
    $vm = new Interpreter();
    $program = "# This is a comment\nPUSH 65\n// Another comment\nWRITEC";
    $vm->parse($program);
    $output = $vm->run();
    assert($output === 'A', "Expected 'A', got '{$output}'");
});

// Test 16: Flag program output
runTest("Flag program output", function () {
    $vm = new Interpreter();
    $vm->loadFromFile(__DIR__ . '/../programs/flag_program.doomstack');
    $output = $vm->run();
    assert($output === 'WTCTT2025{stack_vm_challenge_solved}', "Flag mismatch: got '{$output}'");
});

// Test 17: Challenge program output
runTest("Challenge program output", function () {
    $vm = new Interpreter();
    $vm->loadFromFile(__DIR__ . '/../programs/challenge.doomstack');
    $output = $vm->run();
    assert($output === 'WTCTT2025{d00m_st4ck_m4ch1n3_pwn3d}', "Challenge flag mismatch: got '{$output}'");
});

// Test 18: Input challenge with correct input
runTest("Input challenge (correct input)", function () {
    $vm = new Interpreter();
    $vm->loadFromFile(__DIR__ . '/../programs/input_challenge.doomstack');
    $vm->setInput("doom");
    $output = $vm->run();
    assert($output === 'FLAG_UNLOCKED', "Expected 'FLAG_UNLOCKED', got '{$output}'");
});

// Test 19: Input challenge with wrong input
runTest("Input challenge (wrong input)", function () {
    $vm = new Interpreter();
    $vm->loadFromFile(__DIR__ . '/../programs/input_challenge.doomstack');
    $vm->setInput("fail");
    $output = $vm->run();
    assert($output === 'ACCESS_DENIED', "Expected 'ACCESS_DENIED', got '{$output}'");
});

// Test 20: Stack underflow error
runTest("Stack underflow detection", function () {
    $vm = new Interpreter();
    $vm->parse("POP");
    try {
        $vm->run();
        throw new \AssertionError("Expected RuntimeException for stack underflow");
    } catch (\RuntimeException $e) {
        assert(str_contains($e->getMessage(), 'underflow'), "Expected underflow message");
    }
});

// Test 21: Division by zero error
runTest("Division by zero detection", function () {
    $vm = new Interpreter();
    $vm->parse("PUSH 10\nPUSH 0\nDIV");
    try {
        $vm->run();
        throw new \AssertionError("Expected RuntimeException for division by zero");
    } catch (\RuntimeException $e) {
        assert(str_contains($e->getMessage(), 'zero'), "Expected division by zero message");
    }
});

// Test 22: Unknown label error
runTest("Unknown label detection", function () {
    $vm = new Interpreter();
    $vm->parse("JMP nonexistent");
    try {
        $vm->run();
        throw new \AssertionError("Expected RuntimeException for unknown label");
    } catch (\RuntimeException $e) {
        assert(str_contains($e->getMessage(), 'Unknown label'), "Expected unknown label message");
    }
});

// Test 23: Reset functionality
runTest("VM reset functionality", function () {
    $vm = new Interpreter();
    $vm->parse("PUSH 65\nWRITEC");
    $output1 = $vm->run();
    assert($output1 === 'A', "First run should output 'A'");
    
    $output2 = $vm->run(); // Should reset automatically
    assert($output2 === 'A', "Second run should also output 'A'");
});

// Test 24: READC EOF handling
runTest("READC EOF handling", function () {
    $vm = new Interpreter();
    $vm->parse("READC\nPUSH 1\nADD\nWRITEC");
    $vm->setInput(""); // Empty input
    $output = $vm->run();
    // EOF returns -1, so -1 + 1 = 0, which is out of printable range
    assert($output === '', "Expected empty output for EOF, got '{$output}'");
});

// Summary
echo "\n=== Test Summary ===\n";
echo "Passed: {$testsPassed}\n";
echo "Failed: {$testsFailed}\n";

if ($testsFailed === 0) {
    echo "\n✅ All tests passed!\n";
    exit(0);
} else {
    echo "\n❌ Some tests failed!\n";
    exit(1);
}
