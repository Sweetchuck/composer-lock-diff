<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerLockDiff\Reporter;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * @todo Configurable TableStyle.
 *
 * @todo
 *   Configurable column content.
 *   - text vs UTF-8 chars or for boolean values.
 *   - Upgrade/Downgrade indicator arrows.
 */
class ConsoleTableReporter extends TableReporterBase
{

    use StreamOutputTrait;

    protected ?Table $table = null;

    public function getTable(): ?Table
    {
        return $this->table;
    }

    public function setTable(?Table $table): static
    {
        $this->table = $table;

        return $this;
    }

    public function getDefaultTable(): Table
    {
        return new Table(new StreamOutput($this->getStream()));
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options): static
    {
        parent::setOptions($options);

        if (array_key_exists('table', $options)) {
            $this->setTable($options['table']);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $entries): static
    {
        $this->entries = $entries;
        $this
            ->normalizeColumns()
            ->normalizeGroups();

        if (!$this->table) {
            $this->table = $this->getDefaultTable();
        }

        $this
            ->setTableHeaders()
            ->setTableRows();
        $this->table->render();

        return $this;
    }

    protected function setTableHeaders(): static
    {
        $this->table->setHeaders(array_column(
            $this->getColumns(),
            'title',
            'id',
        ));

        return $this;
    }

    protected function setTableRows(): static
    {
        $columns = $this->getColumns();
        $groupDefs = $this->getGroups();
        $groups = $this->groupEntries();
        $numOfGroups = count($groupDefs);

        foreach ($groups as $groupId => $entries) {
            $groupDef = $groupDefs[$groupId];
            if ($numOfGroups > 1) {
                if (!$entries && $groupDef['showEmpty']) {
                    $this->table->addRow([
                        new TableCell(
                            $groupDef['title'],
                            [
                                'colspan' => count($columns),
                                // @todo Use different color.
                            ],
                        ),
                    ]);
                    $this->table->addRow([
                        new TableCell(
                            $groupDef['emptyContent'],
                            [
                                'colspan' => count($columns),
                                // @todo Use different color.
                            ],
                        ),
                    ]);

                    continue;
                }

                $this->table->addRow([
                    new TableCell(
                        $groupDef['title'],
                        [
                            'colspan' => count($columns),
                            // @todo Use different color.
                        ],
                    ),
                ]);
            }

            foreach ($entries as $entry) {
                $this->table->addRow($this->buildTableRow($columns, $entry));
            }
        }

        return $this;
    }
}
