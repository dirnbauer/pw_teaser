<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\Settings;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\Domain\Repository\CategoryMode;
use PwTeaserTeam\PwTeaser\Settings\TeaserSettings;
use PwTeaserTeam\PwTeaser\Settings\TeaserSource;

final class TeaserSettingsTest extends TestCase
{
    #[Test]
    public function defaultsDescribeTheDirectChildrenOfTheCurrentPage(): void
    {
        $settings = TeaserSettings::fromArray(TeaserSettings::DEFAULTS, 42);

        self::assertSame(TeaserSource::ThisChildren, $settings->source);
        self::assertSame([], $settings->customPageUids);
        self::assertSame(0, $settings->recursionDepthFrom);
        self::assertSame(255, $settings->recursionDepth);
        self::assertFalse($settings->orderByPlugin);
        self::assertFalse($settings->loadContents);
        self::assertFalse($settings->nested);
        self::assertTrue($settings->enablePagination);
        self::assertSame(10, $settings->itemsPerPage);
        self::assertSame('', $settings->paginationClass);
        self::assertSame('', $settings->orderBy);
        self::assertFalse($settings->descending);
        self::assertSame(0, $settings->limit);

        self::assertSame('uid', $settings->filter->orderBy);
        self::assertFalse($settings->filter->descending);
        self::assertSame(0, $settings->filter->limit);
        self::assertFalse($settings->filter->showNavHiddenItems);
        self::assertSame([], $settings->filter->doktypes);
        self::assertSame([], $settings->filter->ignoredUids);
        self::assertSame([], $settings->filter->categoryUids);
        self::assertNull($settings->filter->categoryMode);
    }

    #[Test]
    public function unknownSourceFallsBackToDirectChildren(): void
    {
        $settings = TeaserSettings::fromArray(['source' => 'somethingElse'], 1);

        self::assertSame(TeaserSource::ThisChildren, $settings->source);
    }

    #[Test]
    public function flexFormValuesAreParsedIntoTypedProperties(): void
    {
        $settings = TeaserSettings::fromArray([
            'source' => 'customChildrenRecursively',
            'customPages' => '3, 5,abc,7',
            'recursionDepthFrom' => '1',
            'recursionDepth' => '2',
            'orderByPlugin' => '1',
            'loadContents' => '1',
            'enablePagination' => '0',
            'itemsPerPage' => '25',
            'orderBy' => 'title',
            'orderDirection' => 'DESC',
            'limit' => '4',
            'showNavHiddenItems' => '1',
            'hideCurrentPage' => '1',
            'showDoktypes' => '1,2',
            'ignoreUids' => '9,10',
            'categoriesList' => '11,12',
            'categoryMode' => '4',
            'paginationClass' => 'Vendor\\Pagination',
        ], 42);

        self::assertSame(TeaserSource::CustomChildrenRecursively, $settings->source);
        self::assertSame([3, 5, 7], $settings->customPageUids);
        self::assertSame(1, $settings->recursionDepthFrom);
        self::assertSame(2, $settings->recursionDepth);
        self::assertTrue($settings->orderByPlugin);
        self::assertTrue($settings->loadContents);
        self::assertFalse($settings->enablePagination);
        self::assertSame(25, $settings->itemsPerPage);
        self::assertSame('title', $settings->orderBy);
        self::assertTrue($settings->descending);
        self::assertSame(4, $settings->limit);
        self::assertSame('Vendor\\Pagination', $settings->paginationClass);

        self::assertSame('title', $settings->filter->orderBy);
        self::assertTrue($settings->filter->descending);
        self::assertSame(4, $settings->filter->limit);
        self::assertTrue($settings->filter->showNavHiddenItems);
        self::assertSame([1, 2], $settings->filter->doktypes);
        self::assertSame([9, 10, 42], $settings->filter->ignoredUids);
        self::assertSame([11, 12], $settings->filter->categoryUids);
        self::assertSame(CategoryMode::AndNot, $settings->filter->categoryMode);
    }

    #[Test]
    public function customSourceIsAlwaysFlat(): void
    {
        $settings = TeaserSettings::fromArray(['source' => 'custom', 'pageMode' => 'nested', 'limit' => '3'], 1);

        self::assertFalse($settings->nested);
        self::assertSame(3, $settings->limit);
    }

    #[Test]
    public function nestedModeIgnoresRecursionStartOrderingAndLimit(): void
    {
        $settings = TeaserSettings::fromArray([
            'source' => 'thisChildrenRecursively',
            'pageMode' => 'nested',
            'recursionDepthFrom' => '2',
            'orderBy' => 'title',
            'limit' => '3',
        ], 1);

        self::assertTrue($settings->nested);
        self::assertSame(0, $settings->recursionDepthFrom);
        self::assertSame('', $settings->orderBy);
        self::assertSame(0, $settings->limit);
        self::assertSame('uid', $settings->filter->orderBy);
        self::assertSame(0, $settings->filter->limit);
    }

    #[Test]
    public function randomOrderingIsAppliedInPhpNotInTheDatabase(): void
    {
        $settings = TeaserSettings::fromArray(['orderBy' => 'random', 'limit' => '5'], 1);

        self::assertSame('random', $settings->orderBy);
        self::assertSame(5, $settings->limit);
        self::assertSame('uid', $settings->filter->orderBy);
        self::assertSame(0, $settings->filter->limit);
    }

    #[Test]
    public function customFieldOrderingUsesTheConfiguredColumn(): void
    {
        $settings = TeaserSettings::fromArray(['orderBy' => 'customField', 'orderByCustomField' => 'tx_myext_rank'], 1);

        self::assertSame('tx_myext_rank', $settings->filter->orderBy);
    }

    #[Test]
    public function customFieldOrderingWithoutColumnFallsBackToUid(): void
    {
        $settings = TeaserSettings::fromArray(['orderBy' => 'customField', 'orderByCustomField' => ''], 1);

        self::assertSame('uid', $settings->filter->orderBy);
    }

    #[Test]
    public function categoryModeRequiresCategories(): void
    {
        $settings = TeaserSettings::fromArray(['categoriesList' => '', 'categoryMode' => '2'], 1);

        self::assertNull($settings->filter->categoryMode);
    }

    #[Test]
    public function invalidCategoryModeDisablesTheCategoryFilter(): void
    {
        $settings = TeaserSettings::fromArray(['categoriesList' => '1', 'categoryMode' => '9'], 1);

        self::assertSame([1], $settings->filter->categoryUids);
        self::assertNull($settings->filter->categoryMode);
    }

    #[Test]
    public function rootPageUidsAreTheCustomPagesOrTheCurrentPage(): void
    {
        self::assertSame([7, 8], TeaserSettings::fromArray(['source' => 'customChildren', 'customPages' => '7,8'], 1)->rootPageUids(1));
        self::assertSame([1], TeaserSettings::fromArray(['source' => 'thisChildrenRecursively'], 1)->rootPageUids(1));
    }

    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function booleanValues(): array
    {
        return [
            'string one' => ['1', true],
            'int one' => [1, true],
            'true' => [true, true],
            'string zero' => ['0', false],
            'empty string' => ['', false],
            'missing' => [null, false],
            'other string' => ['yes', false],
        ];
    }

    #[Test]
    #[DataProvider('booleanValues')]
    public function booleanSettingsAcceptFlexFormAndTypoScriptNotation(mixed $value, bool $expected): void
    {
        $settings = TeaserSettings::fromArray(['loadContents' => $value], 1);

        self::assertSame($expected, $settings->loadContents);
    }

    #[Test]
    public function nonNumericIntegersFallBackToTheirDefaults(): void
    {
        $settings = TeaserSettings::fromArray(['limit' => 'many', 'itemsPerPage' => [], 'recursionDepth' => ''], 1);

        self::assertSame(0, $settings->limit);
        self::assertSame(10, $settings->itemsPerPage);
        self::assertSame(255, $settings->recursionDepth);
    }
}
