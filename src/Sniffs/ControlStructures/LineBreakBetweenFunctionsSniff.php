<?php
declare(strict_types=1);

namespace PcComponentesCodingStandard\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Fixer;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\TokenHelper;

final class LineBreakBetweenFunctionsSniff implements Sniff
{
    public const CODE_LINE_BREAK_BETWEEN_FUNCTION = 'LineBreakBetweenFunctions';

    /**
     * @return array
     */
    public function register()
    {
        return [
            T_CLOSE_CURLY_BRACKET,
        ];
    }

    /**
     * @param File $phpcsFile
     * @param int $stackPtr
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $line = $tokens[$stackPtr]['line'];

        $nextPointer = TokenHelper::findNextEffective($phpcsFile, $stackPtr + 1);
        if (null === $nextPointer) {
            return;
        }

        $next = $tokens[$nextPointer];

        if ($next['code'] === T_COMMA && $next['line'] === $line) {
            return;
        }

        if ($next['code'] !== T_CLOSE_CURLY_BRACKET && $next['line'] === ($line + 2)) {
            return;
        }

        if ($next['code'] === T_CLOSE_CURLY_BRACKET && $next['line'] === ($line + 1)) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            sprintf('There must be exactly %d line break after a function.', 1),
            $stackPtr,
            self::CODE_LINE_BREAK_BETWEEN_FUNCTION
        );

        if (!$fix) {
            return;
        }

        $lineBreaks = $next['line'] - $line;

        if ($next['code'] === T_CLOSE_CURLY_BRACKET) {
            $this->setNumberOfLineBreaks($phpcsFile->fixer, $stackPtr, $lineBreaks, 1);
        } else {
            $this->setNumberOfLineBreaks($phpcsFile->fixer, $stackPtr, $lineBreaks, 2);
        }
    }

    private function setNumberOfLineBreaks(Fixer $fixer, int $stackPtr, int $currentLines, int $expectedLines): void
    {
        $lineBreaksToDelete = $currentLines - $expectedLines;

        $fixer->beginChangeset();
        for ($i = 1; $i <= $lineBreaksToDelete; $i++) {
            $fixer->replaceToken($stackPtr + $i, '');
        }
        for ($i = 0; $i > $lineBreaksToDelete; $i--) {
            $fixer->addNewline($stackPtr);
        }
        $fixer->endChangeset();
    }
}
