<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\ViewHelpers;

/*  | This extension is made with love for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2011-2022 Armin Vieweg <armin@v.ieweg.de>
 *  |     2016 Tim Klein-Hitpass <tim.klein-hitpass@diemedialen.de>
 *  |     2016 Kai Ratzeburg <kai.ratzeburg@diemedialen.de>
 */
use PwTeaserTeam\PwTeaser\Domain\Model\Content;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Renders its children once for every content element of {page.contents}
 * that matches the given column and content type:
 *
 *   <pw:getContent contents="{page.contents}" as="content" colPos="0" cType="image" index="0">
 *       <f:image src="{content.imageFiles.0.url}" />
 *   </pw:getContent>
 */
final class GetContentViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('contents', 'array', 'Content elements of a page ({page.contents})');
        $this->registerArgument('as', 'string', 'Variable name of the current content element', true);
        $this->registerArgument('colPos', 'integer', 'Column position to take content elements from', false, 0);
        $this->registerArgument('cType', 'string', 'Only content elements of this CType; empty for all');
        $this->registerArgument('index', 'integer', 'Only the n-th matching content element (0 = first)');
    }

    public function render(): string
    {
        $contents = $this->arguments['contents'];
        $as = $this->arguments['as'];
        if (!is_array($contents) || !is_string($as) || $as === '' || $this->renderingContext === null) {
            return '';
        }
        $colPos = is_numeric($this->arguments['colPos']) ? (int)$this->arguments['colPos'] : 0;
        $cType = is_string($this->arguments['cType']) && $this->arguments['cType'] !== '' ? $this->arguments['cType'] : null;
        $index = is_numeric($this->arguments['index']) ? (int)$this->arguments['index'] : null;

        $matches = array_values(array_filter(
            $contents,
            static fn(mixed $content): bool => $content instanceof Content
                && $content->getColPos() === $colPos
                && ($cType === null || $content->getCtype() === $cType)
        ));
        if ($index !== null) {
            $matches = isset($matches[$index]) ? [$matches[$index]] : [];
        }

        $variableProvider = $this->renderingContext->getVariableProvider();
        $output = '';
        foreach ($matches as $content) {
            $variableProvider->add($as, $content);
            $children = $this->renderChildren();
            $output .= is_scalar($children) ? (string)$children : '';
            $variableProvider->remove($as);
        }
        return $output;
    }
}
