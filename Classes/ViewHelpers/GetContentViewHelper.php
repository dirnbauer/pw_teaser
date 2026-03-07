<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\ViewHelpers;

use PwTeaserTeam\PwTeaser\Domain\Model\Content;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/*  | This extension is made with love for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2011-2022 Armin Vieweg <armin@v.ieweg.de>
 *  |     2016 Tim Klein-Hitpass <tim.klein-hitpass@diemedialen.de>
 *  |     2016 Kai Ratzeburg <kai.ratzeburg@diemedialen.de>
 */

/**
 * This class creates links to social bookmark services, recommending the
 * current front-end page.
 *
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class GetContentViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    /**
     * @return void
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('contents', 'array', 'Content elements');
        $this->registerArgument('as', 'string', 'the name of the iteration variable', true);
        $this->registerArgument('colPos', 'integer', 'column position to get content elements from', false, 0);
        $this->registerArgument('cType', 'string', 'the cType to filter content elements for');
        $this->registerArgument('index', 'integer', 'limits the output to n-th element');
    }

    public function render(): string
    {
        $contents = $this->arguments['contents'];
        if ($contents === null || !is_array($contents)) {
            return '';
        }

        $as = $this->arguments['as'];
        if (!is_string($as)) {
            return '';
        }

        $output = '';
        $indexCount = 0;
        $breakNow = false;
        $asHasBeenSet = false;

        if ($this->renderingContext === null) {
            return '';
        }
        $variableProvider = $this->renderingContext->getVariableProvider();
        $colPos = is_int($this->arguments['colPos'] ?? null) ? $this->arguments['colPos'] : 0;
        $cType = $this->arguments['cType'];
        $index = $this->arguments['index'];

        /** @var Content $content */
        foreach ($contents as $content) {
            $contentCtype = $content->getCtype();
            $contentColPos = $content->getColPos();
            $matchesType = $cType === null || $contentCtype === $cType;
            $matchesColumn = $contentColPos === $colPos;

            if ($matchesColumn && $matchesType) {
                if ($index === null) {
                    $variableProvider->add($as, $content);
                    $asHasBeenSet = true;
                } elseif (is_int($index) && $indexCount === $index) {
                    $variableProvider->add($as, $content);
                    $asHasBeenSet = true;
                    $breakNow = true;
                }
            }

            if ($asHasBeenSet) {
                $children = $this->renderChildren();
                $output .= is_string($children) ? $children : (is_scalar($children) ? (string)$children : '');
                $variableProvider->remove($as);
                $asHasBeenSet = false;
            }

            if ($breakNow) {
                break;
            }
            if ($matchesColumn && $matchesType) {
                $indexCount++;
            }
        }
        return $output;
    }
}
