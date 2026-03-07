<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/*  | This extension is made with love for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2011-2022 Armin Vieweg <armin@v.ieweg.de>
 */

/**
 * This view helper removes whitespaces which are annoying the HTML output,
 * cause some browsers are interpreting tabs and new lines.
 *
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class RemoveWhitespacesViewHelper extends AbstractViewHelper
{

    /**
     * Returns the content without dispensable whitespaces
     *
     * @return string Rendered string, may be empty.
     */
    public function render(): string
    {
        return str_replace(["\t", "\r", "\n"], '', (string)$this->renderChildren());
    }
}
