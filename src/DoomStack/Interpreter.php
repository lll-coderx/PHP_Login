<?php

declare(strict_types=1);

namespace App\DoomStack;

/**
 * DoomStack VM Interpreter
 * 
 * A stack-based virtual machine interpreter for the DoomStack language.
 * 
 * Instruction Set:
 * - PUSH X   : Push integer X onto the stack
 * - POP      : Pop top value from stack
 * - ADD      : Pop two values (a=second, b=top), push (a + b)
 * - SUB      : Pop two values (a=second, b=top), push (a - b)
 * - MUL      : Pop two values (a=second, b=top), push (a * b)
 * - DIV      : Pop two values (a=second, b=top), push integer division (a / b)
 * - MOD      : Pop two values (a=second, b=top), push (a % b)
 * - DUP      : Duplicate the top value on the stack
 * - SWAP     : Swap the top two values on the stack
 * - READC    : Read 1 byte from input stream, push as integer (or -1 on EOF)
 * - WRITEC   : Pop 1 value, write as ASCII character (values outside 0-255 are silently ignored)
 * - JMP L    : Jump to label L
 * - JZ L     : Pop 1 value, if zero jump to label L
 * - LABEL L  : Define a jump target label L
 */
class Interpreter
{
    /** @var int[] Stack for VM operations */
    private array $stack = [];

    /** @var array<string, int> Label positions (label name => instruction index) */
    private array $labels = [];

    /** @var array<array{op: string, arg?: int|string}> Parsed instructions */
    private array $instructions = [];

    /** @var int Current instruction pointer */
    private int $ip = 0;

    /** @var string Input buffer for READC operations */
    private string $input = '';

    /** @var int Input position pointer */
    private int $inputPos = 0;

    /** @var string Output buffer for WRITEC operations */
    private string $output = '';

    /** @var int Maximum execution steps to prevent infinite loops */
    private int $maxSteps = 1000000;

    /**
     * Load and parse a DoomStack program from a file
     *
     * @param string $filename Path to the DoomStack program file
     * @return void
     * @throws \RuntimeException If file cannot be read
     */
    public function loadFromFile(string $filename): void
    {
        if (!file_exists($filename)) {
            throw new \RuntimeException("File not found: {$filename}");
        }

        $content = file_get_contents($filename);
        if ($content === false) {
            throw new \RuntimeException("Cannot read file: {$filename}");
        }

        $this->parse($content);
    }

    /**
     * Parse a DoomStack program from string
     *
     * @param string $program The program source code
     * @return void
     */
    public function parse(string $program): void
    {
        $this->instructions = [];
        $this->labels = [];

        $lines = explode("\n", $program);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '//')) {
                continue;
            }

            $parts = preg_split('/\s+/', $line, 2);
            $op = strtoupper($parts[0]);
            $arg = isset($parts[1]) ? trim($parts[1]) : null;

            // Handle LABEL instruction - store position
            if ($op === 'LABEL' && $arg !== null) {
                $this->labels[$arg] = count($this->instructions);
                continue;
            }

            // Store instruction
            $instruction = ['op' => $op];
            if ($arg !== null) {
                // Determine if arg is numeric or a label name
                if (is_numeric($arg)) {
                    $instruction['arg'] = (int)$arg;
                } else {
                    $instruction['arg'] = $arg;
                }
            }

            $this->instructions[] = $instruction;
        }
    }

    /**
     * Set input for READC operations
     *
     * @param string $input The input string
     * @return void
     */
    public function setInput(string $input): void
    {
        $this->input = $input;
        $this->inputPos = 0;
    }

    /**
     * Set maximum execution steps
     *
     * @param int $maxSteps Maximum number of instructions to execute
     * @return void
     */
    public function setMaxSteps(int $maxSteps): void
    {
        $this->maxSteps = $maxSteps;
    }

    /**
     * Reset the VM state
     *
     * @return void
     */
    public function reset(): void
    {
        $this->stack = [];
        $this->ip = 0;
        $this->inputPos = 0;
        $this->output = '';
    }

    /**
     * Get the output buffer
     *
     * @return string The output produced by WRITEC instructions
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * Get the current stack state
     *
     * @return int[] Current stack contents
     */
    public function getStack(): array
    {
        return $this->stack;
    }

    /**
     * Run the loaded program
     *
     * @return string The output produced by the program
     * @throws \RuntimeException On execution errors
     */
    public function run(): string
    {
        $this->reset();
        $steps = 0;

        while ($this->ip < count($this->instructions)) {
            if ($steps++ > $this->maxSteps) {
                throw new \RuntimeException("Maximum execution steps exceeded (possible infinite loop)");
            }

            $instruction = $this->instructions[$this->ip];
            $this->execute($instruction);
        }

        return $this->output;
    }

    /**
     * Execute a single instruction
     *
     * @param array{op: string, arg?: int|string} $instruction The instruction to execute
     * @return void
     * @throws \RuntimeException On execution errors
     */
    private function execute(array $instruction): void
    {
        $op = $instruction['op'];
        $arg = $instruction['arg'] ?? null;

        switch ($op) {
            case 'PUSH':
                if ($arg === null || !is_int($arg)) {
                    throw new \RuntimeException("PUSH requires an integer argument at IP {$this->ip}");
                }
                $this->stack[] = $arg;
                $this->ip++;
                break;

            case 'POP':
                if (empty($this->stack)) {
                    throw new \RuntimeException("Stack underflow on POP at IP {$this->ip}");
                }
                array_pop($this->stack);
                $this->ip++;
                break;

            case 'ADD':
                $this->binaryOp(fn($a, $b) => $a + $b, 'ADD');
                break;

            case 'SUB':
                $this->binaryOp(fn($a, $b) => $a - $b, 'SUB');
                break;

            case 'MUL':
                $this->binaryOp(fn($a, $b) => $a * $b, 'MUL');
                break;

            case 'DIV':
                $this->binaryOp(function ($a, $b) {
                    if ($b === 0) {
                        throw new \RuntimeException("Division by zero");
                    }
                    return intdiv($a, $b);
                }, 'DIV');
                break;

            case 'MOD':
                $this->binaryOp(function ($a, $b) {
                    if ($b === 0) {
                        throw new \RuntimeException("Modulo by zero");
                    }
                    return $a % $b;
                }, 'MOD');
                break;

            case 'DUP':
                if (empty($this->stack)) {
                    throw new \RuntimeException("Stack underflow on DUP at IP {$this->ip}");
                }
                $this->stack[] = $this->stack[count($this->stack) - 1];
                $this->ip++;
                break;

            case 'SWAP':
                if (count($this->stack) < 2) {
                    throw new \RuntimeException("Stack underflow on SWAP at IP {$this->ip}");
                }
                $top = array_pop($this->stack);
                $second = array_pop($this->stack);
                $this->stack[] = $top;
                $this->stack[] = $second;
                $this->ip++;
                break;

            case 'READC':
                if ($this->inputPos >= strlen($this->input)) {
                    // End of input, push -1 (EOF indicator)
                    $this->stack[] = -1;
                } else {
                    $this->stack[] = ord($this->input[$this->inputPos++]);
                }
                $this->ip++;
                break;

            case 'WRITEC':
                if (empty($this->stack)) {
                    throw new \RuntimeException("Stack underflow on WRITEC at IP {$this->ip}");
                }
                $value = array_pop($this->stack);
                // Values outside 0-255 range are silently ignored (no output produced)
                if ($value >= 0 && $value <= 255) {
                    $this->output .= chr($value);
                }
                $this->ip++;
                break;

            case 'JMP':
                if ($arg === null) {
                    throw new \RuntimeException("JMP requires a label argument at IP {$this->ip}");
                }
                if (!isset($this->labels[$arg])) {
                    throw new \RuntimeException("Unknown label '{$arg}' at IP {$this->ip}");
                }
                $this->ip = $this->labels[$arg];
                break;

            case 'JZ':
                if ($arg === null) {
                    throw new \RuntimeException("JZ requires a label argument at IP {$this->ip}");
                }
                if (!isset($this->labels[$arg])) {
                    throw new \RuntimeException("Unknown label '{$arg}' at IP {$this->ip}");
                }
                if (empty($this->stack)) {
                    throw new \RuntimeException("Stack underflow on JZ at IP {$this->ip}");
                }
                $value = array_pop($this->stack);
                if ($value === 0) {
                    $this->ip = $this->labels[$arg];
                } else {
                    $this->ip++;
                }
                break;

            default:
                throw new \RuntimeException("Unknown instruction '{$op}' at IP {$this->ip}");
        }
    }

    /**
     * Execute a binary operation (pops two values, pushes result)
     *
     * @param callable(int, int): int $operation The operation to perform
     * @param string $opName Name of the operation (for error messages)
     * @return void
     * @throws \RuntimeException On stack underflow
     */
    private function binaryOp(callable $operation, string $opName): void
    {
        if (count($this->stack) < 2) {
            throw new \RuntimeException("Stack underflow on {$opName} at IP {$this->ip}");
        }
        $b = array_pop($this->stack);  // top
        $a = array_pop($this->stack);  // second
        $this->stack[] = $operation($a, $b);
        $this->ip++;
    }
}
