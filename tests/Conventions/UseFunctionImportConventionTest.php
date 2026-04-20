<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Conventions;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function count;
use function explode;
use function file_get_contents;
use function implode;
use function in_array;
use function is_array;
use function ltrim;
use function preg_match;
use function sort;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function token_get_all;

/**
 * Enforces explicit `use function` imports for global/native function calls.
 */
final class UseFunctionImportConventionTest extends TestCase
{
    /**
     * @var array<int, string>
     */
    private const DIRECTORIES = [
        __DIR__ . '/../../src',
        __DIR__ . '/../../tests',
    ];

    private const BLOCKED_PREVIOUS_TOKEN_IDS = [
        T_FUNCTION => true,
        T_FN => true,
        T_NEW => true,
        T_INSTANCEOF => true,
        T_USE => true,
        T_NAMESPACE => true,
        T_CLASS => true,
        T_INTERFACE => true,
        T_TRAIT => true,
        T_EXTENDS => true,
        T_IMPLEMENTS => true,
        T_PUBLIC => true,
        T_PROTECTED => true,
        T_PRIVATE => true,
        T_STATIC => true,
        T_ABSTRACT => true,
        T_FINAL => true,
        T_RETURN => true,
        T_THROW => true,
        T_CASE => true,
        T_MATCH => true,
        T_CLONE => true,
        T_ECHO => true,
        T_PRINT => true,
        T_INCLUDE => true,
        T_INCLUDE_ONCE => true,
        T_REQUIRE => true,
        T_REQUIRE_ONCE => true,
        T_ARRAY => true,
        T_CALLABLE => true,
        T_CONST => true,
        T_VARIABLE => true,
        T_AS => true,
        T_DOUBLE_ARROW => true,
        T_IF => true,
        T_ELSEIF => true,
        T_FOR => true,
        T_FOREACH => true,
        T_WHILE => true,
        T_SWITCH => true,
        T_CATCH => true,
        T_ATTRIBUTE => true,
        T_OBJECT_OPERATOR => true,
        T_DOUBLE_COLON => true,
        T_NULLSAFE_OBJECT_OPERATOR => true,
    ];

    /**
     * @var array<string, true>
     */
    private const SKIPPED_NAMES = [
        'isset' => true,
        'empty' => true,
        'unset' => true,
        'eval' => true,
        'echo' => true,
        'print' => true,
        'die' => true,
        'exit' => true,
        'list' => true,
        'clone' => true,
    ];

    public function testNamespacedFilesMustImportGlobalFunctionsViaUseFunction(): void
    {
        $violations = [];

        foreach ($this->projectPhpFiles() as $filePath) {
            foreach ($this->findViolations($filePath) as $violation) {
                $violations[] = $violation;
            }
        }

        sort($violations);

        self::assertSame(
            [],
            $violations,
            "Missing required `use function` imports:\n" . implode("\n", $violations)
        );
    }

    /**
     * @return string[]
     */
    private function projectPhpFiles(): array
    {
        $files = [];

        foreach (self::DIRECTORIES as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $fileInfo) {
                if (!$fileInfo instanceof SplFileInfo) {
                    continue;
                }

                if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                    continue;
                }

                $files[] = $fileInfo->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * @param array<int, string|array{int, string, int}> $tokens
     */
    private function hasNamespace(array $tokens): bool
    {
        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function findViolations(string $filePath): array
    {
        $contents = file_get_contents($filePath);
        self::assertNotFalse($contents, sprintf('Failed to read file %s.', $filePath));

        $tokens = token_get_all($contents);
        if (!$this->hasNamespace($tokens)) {
            return [];
        }

        $importedFunctions = $this->collectImportedFunctions($tokens);
        $declaredFunctions = $this->collectDeclaredFunctions($tokens);
        $violations = [];
        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if (is_array($token) && $token[0] === T_STRING && $this->isUnqualifiedFunctionCallCandidate($tokens, $index)) {
                $functionName = $token[1];
                if (isset($declaredFunctions[$functionName]) || isset($importedFunctions[$functionName])) {
                    continue;
                }

                $violations[] = sprintf(
                    '%s:%d uses `%s()` without `use function %s;`',
                    $filePath,
                    $token[2],
                    $functionName,
                    $functionName
                );
            }

            if (!is_array($token) || !str_starts_with($token[1], '\\')) {
                continue;
            }

            $nextIndex = $this->nextMeaningfulTokenIndex($tokens, $index);
            if ($nextIndex === null || $tokens[$nextIndex] !== '(') {
                continue;
            }

            if (!preg_match('/^\\\\[A-Za-z_][A-Za-z0-9_]*$/', $token[1])) {
                continue;
            }

            $functionName = ltrim($token[1], '\\');
            $violations[] = sprintf(
                '%s:%d uses fully qualified `%s()`; import it via `use function %s;` instead.',
                $filePath,
                $token[2],
                $token[1],
                $functionName
            );
        }

        return $violations;
    }

    /**
     * @param array<int, string|array{int, string, int}> $tokens
     * @return array<string, true>
     */
    private function collectImportedFunctions(array $tokens): array
    {
        $imports = [];
        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; $index++) {
            $token = $tokens[$index];
            if (!is_array($token) || $token[0] !== T_USE) {
                continue;
            }

            $previousIndex = $this->previousMeaningfulTokenIndex($tokens, $index);
            if ($previousIndex !== null && $tokens[$previousIndex] === ')') {
                continue;
            }

            $nextIndex = $this->nextMeaningfulTokenIndex($tokens, $index);
            if ($nextIndex === null || !is_array($tokens[$nextIndex]) || $tokens[$nextIndex][0] !== T_FUNCTION) {
                continue;
            }

            $currentImport = '';
            for ($cursor = $nextIndex + 1; $cursor < $tokenCount; $cursor++) {
                $part = $tokens[$cursor];

                if (is_array($part) && in_array($part[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                    $currentImport .= ltrim($part[1], '\\');
                    continue;
                }

                if ($part === '\\') {
                    $currentImport .= '\\';
                    continue;
                }

                if (is_array($part) && $part[0] === T_AS) {
                    $aliasIndex = $this->nextMeaningfulTokenIndex($tokens, $cursor);
                    if ($aliasIndex !== null && is_array($tokens[$aliasIndex]) && $tokens[$aliasIndex][0] === T_STRING) {
                        $imports[$tokens[$aliasIndex][1]] = true;
                    }
                    $currentImport = '';
                    continue;
                }

                if ($part === ',' || $part === ';') {
                    if ($currentImport !== '') {
                        $segments = explode('\\', $currentImport);
                        $imports[$segments[count($segments) - 1]] = true;
                        $currentImport = '';
                    }

                    if ($part === ';') {
                        break;
                    }
                }
            }
        }

        return $imports;
    }

    /**
     * @param array<int, string|array{int, string, int}> $tokens
     * @return array<string, true>
     */
    private function collectDeclaredFunctions(array $tokens): array
    {
        $declaredFunctions = [];
        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; $index++) {
            $token = $tokens[$index];
            if (!is_array($token) || $token[0] !== T_FUNCTION) {
                continue;
            }

            $nameIndex = $this->nextMeaningfulTokenIndex($tokens, $index);
            if ($nameIndex === null) {
                continue;
            }

            while ($nameIndex < $tokenCount && $tokens[$nameIndex] === '&') {
                $nextNameIndex = $this->nextMeaningfulTokenIndex($tokens, $nameIndex);
                if ($nextNameIndex === null) {
                    continue 2;
                }
                $nameIndex = $nextNameIndex;
            }

            $nameToken = $tokens[$nameIndex];
            if (!is_array($nameToken) || $nameToken[0] !== T_STRING) {
                continue;
            }

            $declaredFunctions[$nameToken[1]] = true;
        }

        return $declaredFunctions;
    }

    /**
     * @param array<int, string|array{int, string, int}> $tokens
     */
    private function isUnqualifiedFunctionCallCandidate(array $tokens, int $index): bool
    {
        $token = $tokens[$index];
        if (!is_array($token) || $token[0] !== T_STRING) {
            return false;
        }

        if (isset(self::SKIPPED_NAMES[strtolower($token[1])])) {
            return false;
        }

        $nextIndex = $this->nextMeaningfulTokenIndex($tokens, $index);
        if ($nextIndex === null || $tokens[$nextIndex] !== '(') {
            return false;
        }

        $previousIndex = $this->previousMeaningfulTokenIndex($tokens, $index);
        if ($previousIndex === null) {
            return true;
        }

        $previousToken = $tokens[$previousIndex];
        if ($previousToken === '->' || $previousToken === '::' || $previousToken === '\\') {
            return false;
        }

        if (is_array($previousToken) && isset(self::BLOCKED_PREVIOUS_TOKEN_IDS[$previousToken[0]])) {
            return false;
        }

        return true;
    }

    /**
     * @param array<int, string|array{int, string, int}> $tokens
     */
    private function nextMeaningfulTokenIndex(array $tokens, int $index): ?int
    {
        $tokenCount = count($tokens);
        for ($cursor = $index + 1; $cursor < $tokenCount; $cursor++) {
            $token = $tokens[$cursor];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $cursor;
        }

        return null;
    }

    /**
     * @param array<int, string|array{int, string, int}> $tokens
     */
    private function previousMeaningfulTokenIndex(array $tokens, int $index): ?int
    {
        for ($cursor = $index - 1; $cursor >= 0; $cursor--) {
            $token = $tokens[$cursor];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $cursor;
        }

        return null;
    }
}
