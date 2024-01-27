<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerLockDiff\Reporter;

use Sweetchuck\ComposerLockDiff\LockDiffEntry;
use Sweetchuck\ComposerLockDiff\ReporterInterface;
use Sweetchuck\Utils\Comparer\ArrayValueComparer;
use Sweetchuck\Utils\Filter\EnabledFilter;

abstract class TableReporterBase implements ReporterInterface
{
    use StreamOutputTrait;

    /**
     * @phpstan-var array<string, \Sweetchuck\ComposerLockDiff\LockDiffEntry>
     */
    public array $entries;

    /**
     * @phpstan-var array<string, composer-lock-diff-console-table-reporter-column-def>
     */
    protected array $columns = [
        'name' => [
            'enabled' => true,
            'weight' => 0,
            'align' => 'left',
            'title' => 'Name',
        ],
        'leftVersionString' => [
            'enabled' => true,
            'weight' => 1,
            'align' => 'left',
            'title' => 'Before',
        ],
        'rightVersionString' => [
            'enabled' => true,
            'weight' => 2,
            'align' => 'left',
            'title' => 'After',
            'config' => [
                'showDirection' => true,
            ],
        ],
        'requiredAs' => [
            'enabled' => true,
            'weight' => 3,
            'align' => 'left',
            'title' => 'Required',
            'config' => [
                'showDirection' => true,
            ],
        ],
        'directDependency' => [
            'enabled' => true,
            'weight' => 4,
            'align' => 'left',
            'title' => 'Direct',
        ],
    ];

    /**
     * @phpstan-return array<string, composer-lock-diff-console-table-reporter-column-def>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @phpstan-param array<string, composer-lock-diff-console-table-reporter-column-def> $columns
     */
    public function setColumns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    protected function normalizeColumns(): static
    {
        $columns = array_filter(
            $this->getColumns(),
            new EnabledFilter(),
        );

        foreach ($columns as $id => &$column) {
            $column['id'] = $id;
            $column += [
                'title' => $column['id'],
                'align' => 'left',
            ];
        }

        uasort(
            $columns,
            (new ArrayValueComparer())
                ->setKeys([
                    'weight' => [
                        'default' => 0,
                    ],
                    'id' => [
                        'default' => '',
                    ],
                ]),
        );

        $this->setColumns($columns);

        return $this;
    }

    /**
     * @phpstan-var array<string, composer-lock-diff-console-table-reporter-group-def>
     */
    protected array $groups = [
        'other' => [
            'enabled' => true,
            'id' => 'other',
            'title' => 'Other',
            'weight' => 999,
            'showEmpty' => false,
            'emptyContent' => '-- empty --',
            'filter' => null,
            'comparer' => null,
        ],
    ];

    /**
     * @phpstan-return array<string, composer-lock-diff-console-table-reporter-group-def>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @phpstan-param array<string, composer-lock-diff-console-table-reporter-group-def> $groups
     */
    public function setGroups(array $groups): static
    {
        $this->groups = $groups;

        return $this;
    }

    protected function normalizeGroups(): static
    {
        $groups = array_filter(
            $this->getGroups(),
            new EnabledFilter(),
        );

        uasort(
            $groups,
            (new ArrayValueComparer())
                ->setKeys([
                    'weight' => [
                        'default' => 0,
                    ],
                ]),
        );

        foreach ($groups as $id => &$group) {
            $group['id'] = $id;
        }

        $this->setGroups($groups);

        return $this;
    }

    /**
     * @phpstan-return array<string, \Sweetchuck\ComposerLockDiff\LockDiffEntry[]>
     */
    protected function groupEntries(): array
    {
        $groups = [];
        $entries = $this->entries;
        foreach ($this->getGroups() as $id => $group) {
            if (is_bool($group['filter'])) {
                $result = $group['filter'];
                $filter = function () use ($result): bool {
                    return $result;
                };
            } else {
                $filter = $group['filter'];
            }

            $groups[$id] = !empty($filter) ?
                array_filter($entries, $filter)
                : $entries;

            if (!empty($group['comparer'])) {
                uasort($groups[$id], $group['comparer']);
            }

            $entries = array_diff_key($entries, $groups[$id]);
        }

        return $groups;
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options): static
    {
        if (array_key_exists('columns', $options)) {
            $this->setColumns($options['columns']);
        }

        if (array_key_exists('groups', $options)) {
            $this->setGroups($options['groups']);
        }

        if (array_key_exists('stream', $options)) {
            $this->setStream($options['stream']);
        }

        return $this;
    }

    /**
     * @phpstan-param array<string, composer-lock-diff-console-table-reporter-column-def> $columns
     *
     * @phpstan-return array<string, mixed>
     */
    protected function buildTableRow(array $columns, LockDiffEntry $entry): array
    {
        $row = [];
        foreach ($columns as $colId => $column) {
            switch ($colId) {
                case 'name':
                    $row[$colId] = $entry->name;
                    break;

                case 'leftVersionString':
                    $row[$colId] = (string) $entry->leftVersionString;
                    break;

                case 'rightVersionString':
                    $row[$colId] = (string) $entry->rightVersionString;
                    break;

                case 'requiredAs':
                    $row[$colId] = sprintf(
                        '%s : %s',
                        str_pad($entry->leftRequiredAs ?: '-', 4),
                        $entry->rightRequiredAs ?: '-',
                    );
                    break;

                case 'directDependency':
                    $left = $entry->isLeftDirectDependency === null ?
                        '?'
                        : ($entry->isLeftDirectDependency ? 'direct' : 'child');
                    $right = $entry->isRightDirectDependency === null ?
                        '?'
                        : ($entry->isRightDirectDependency ? 'direct' : 'child');

                    $row[$colId] = sprintf(
                        '% 6s : %s',
                        str_pad($left, 6),
                        $right,
                    );
                    break;
            }
        }

        return $row;
    }
}
