<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Database;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Loads raw database rows and exposes their columns with lowerCamelCase keys,
 * so Fluid templates can read any column via {page.get.columnName}.
 */
final readonly class RecordRowLoader
{
    public function __construct(private ConnectionPool $connectionPool) {}

    /**
     * @return array<string, mixed> The row with lowerCamelCase keys, empty if the record does not exist
     */
    public function loadByUid(string $table, int $uid): array
    {
        return $this->loadByUids($table, [$uid])[$uid] ?? [];
    }

    /**
     * @param list<int> $uids
     * @return array<int, array<string, mixed>> Rows with lowerCamelCase keys, indexed by uid
     */
    public function loadByUids(string $table, array $uids): array
    {
        if ($uids === []) {
            return [];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $rows = $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT_ARRAY)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $rowsByUid = [];
        foreach ($rows as $row) {
            $uid = $row['uid'] ?? null;
            if (is_numeric($uid)) {
                $rowsByUid[(int)$uid] = self::camelCaseKeys($row);
            }
        }
        return $rowsByUid;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function camelCaseKeys(array $row): array
    {
        $result = [];
        foreach ($row as $column => $value) {
            $result[GeneralUtility::underscoredToLowerCamelCase($column)] = $value;
        }
        return $result;
    }
}
