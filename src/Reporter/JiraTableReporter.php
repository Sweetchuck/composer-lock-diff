<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerLockDiff\Reporter;

class JiraTableReporter extends MarkdownTableReporter
{

    protected function getRenderedTable(): string
    {
        $lines = explode(\PHP_EOL, parent::getRenderedTable());
        if (isset($lines[0])) {
            $lines[0] = str_replace('|', '||', $lines[0]);
        }

        unset($lines[1]);

        return implode(\PHP_EOL, $lines);
    }
}
