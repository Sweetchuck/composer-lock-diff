<?php

declare(strict_types=1);

namespace Sweetchuck\ComposerLockDiff;

interface ReporterInterface
{

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function setOptions(array $options): static;

    /**
     * @phpstan-param array<string, LockDiffEntry> $entries
     */
    public function generate(array $entries): static;
}
