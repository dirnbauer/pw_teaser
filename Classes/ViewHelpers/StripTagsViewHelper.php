<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/*  | This extension is made with love for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2011-2022 Armin Vieweg <armin@v.ieweg.de>
 */

final class StripTagsViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('string', 'string', 'The string to strip tags from');
    }

    public function render(): string
    {
        $string = $this->arguments['string'] ?? null;
        if ($string === null) {
            $children = $this->renderChildren();
            $string = html_entity_decode(is_string($children) ? $children : '');
        } else {
            $string = is_string($string) ? $string : '';
        }
        return strip_tags($string);
    }
}
