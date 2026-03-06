<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Updates;

use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate;

#[UpgradeWizard('pwTeaserCTypeMigration')]
final class PwTeaserCTypeMigration extends AbstractListTypeToCTypeUpdate
{
    public function getTitle(): string
    {
        return 'Migrate "pw_teaser" plugins to content elements';
    }

    public function getDescription(): string
    {
        return 'Migrates existing pw_teaser records from the legacy list_type "pwteaser_pi1" to the dedicated CType "pwteaser_pi1" and updates related backend permissions.';
    }

    /**
     * @return array<string, string>
     */
    protected function getListTypeToCTypeMapping(): array
    {
        return [
            'pwteaser_pi1' => 'pwteaser_pi1',
        ];
    }
}
