<?php
/**
 * ONGR_Sniffs_CSS_DuplicateClassDefinitionSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

namespace ONGR\Sniffs\CSS;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Sniff;

/**
 * ONGR_Sniffs_CSS_DuplicateClassDefinitionSniff.
 *
 * Check for duplicate class definitions that can be merged into one.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class DuplicateClassDefinitionSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * @var array A list of tokenizers this sniff supports.
     */
    public $supportedTokenizers = ['CSS'];

    /**
     * Returns the token types that this sniff is interested in.
     *
     * @return int[]
     */
    public function register()
    {
        return [T_OPEN_TAG];
    }

    /**
     * Processes the tokens that this sniff is interested in.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file where the token was found.
     * @param int                  $stackPtr  The position in the stack where
     *                                        the token was found.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Find the content of each class definition name.
        $classNames = [];
        $next = $phpcsFile->findNext(T_OPEN_CURLY_BRACKET, ($stackPtr + 1));
        if ($next === false) {
            // No class definitions in the file.
            return;
        }

        $find = [
            T_CLOSE_CURLY_BRACKET,
            T_COMMENT,
            T_OPEN_TAG,
        ];

        while ($next !== false) {
            $prev = $phpcsFile->findPrevious($find, ($next - 1));

            // Create a sorted name for the class so we can compare classes
            // even when the individual names are all over the place.
            $name = '';
            for ($i = ($prev + 1); $i < $next; $i++) {
                $name .= $tokens[$i]['content'];
            }

            $name = trim($name);
            $name = str_replace("\n", ' ', $name);
            $name = preg_replace('|[\s]+|', ' ', $name);
            $name = str_replace(', ', ',', $name);

            $names = explode(',', $name);
            sort($names);
            $name = implode(',', $names);

            if (isset($classNames[$name]) === true) {
                $first = $classNames[$name];
                $error = 'Duplicate class definition found; first defined on line %s';
                $data = [$tokens[$first]['line']];
                $phpcsFile->addError($error, $next, 'Found', $data);
            } else {
                $classNames[$name] = $next;
            }

            $next = $phpcsFile->findNext(T_OPEN_CURLY_BRACKET, ($next + 1));
        }
    }
}
