<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerLockDiff\Reporter;

use MaddHatter\MarkdownTable\Builder as MarkdownTable;

class MarkdownTableReporter extends TableReporterBase
{

    protected MarkdownTable $table;

    /**
     * {@inheritdoc}
     */
    public function generate(array $entries): static
    {
        $this->table = new MarkdownTable();
        $this->entries = $entries;
        $this
            ->normalizeColumns()
            ->normalizeGroups()
            ->setTableHeaders()
            ->setTableAlignments()
            ->setTableRows();

        fwrite(
            $this->getStream(),
            $this->getRenderedTable(),
        );

        return $this;
    }

    protected function setTableHeaders(): static
    {
        $this->table->headers(array_column(
            $this->getColumns(),
            'title',
        ));

        return $this;
    }

    protected function setTableAlignments(): static
    {
        $alignments = [];
        foreach ($this->getColumns() as $column) {
            $alignments[] = match ($column['align']) {
                'center' => 'C',
                'right' => 'R',
                default => 'L',
            };
        }
        $this->table->align($alignments);

        return $this;
    }

    protected function setTableRows(): static
    {
        $columns = $this->getColumns();
        $groups = $this->groupEntries();
        foreach ($groups as $entries) {
            // @todo Group support.
            foreach ($entries as $entry) {
                $this->table->row(array_values($this->buildTableRow($columns, $entry)));
            }
        }

        return $this;
    }

    protected function getRenderedTable(): string
    {
        return $this->table->render();
    }
}
