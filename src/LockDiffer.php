<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerLockDiff;

/**
 * @todo Support for {"require": {"ext-FOO": "*"}}.
 *
 * @phpstan-import-type ComposerLockDiffJson     from \Sweetchuck\ComposerLockDiff\Phpstan
 * @phpstan-import-type ComposerLockDiffJsonFull from \Sweetchuck\ComposerLockDiff\Phpstan
 * @phpstan-import-type ComposerLockDiffLock     from \Sweetchuck\ComposerLockDiff\Phpstan
 */
class LockDiffer
{

    /**
     * @phpstan-param ComposerLockDiffLock $leftLock
     * @phpstan-param ComposerLockDiffLock $rightLock
     * @phpstan-param null|ComposerLockDiffJson $leftJson
     * @phpstan-param null|ComposerLockDiffJson $rightJson
     *
     * @phpstan-return array<string, LockDiffEntry>
     *
     * @todo Maybe both $leftLock and $rightLock are also optional.
     */
    public function diff(
        ?array $leftLock = null,
        ?array $rightLock = null,
        ?array $leftJson = null,
        ?array $rightJson = null,
    ): array {
        assert($leftLock || $rightLock, 'One of the $leftLock or $rightLock is required.');

        $leftPackages = $this->normalizePackages($leftLock, $leftJson);
        $rightPackages = $this->normalizePackages($rightLock, $rightJson);

        $packageNames = array_unique(array_merge(
            array_keys($leftPackages),
            array_keys($rightPackages),
        ));
        sort($packageNames);

        $entries = [];
        foreach ($packageNames as $name) {
            $left = $leftPackages[$name] ?? null;
            $right = $rightPackages[$name] ?? null;
            if (!$this->isChanged($left, $right)) {
                continue;
            }

            $entries[$name] = new LockDiffEntry($left, $right);
        }

        return $entries;
    }

    /**
     * @phpstan-param ComposerLockDiffJsonFull $left
     * @phpstan-param ComposerLockDiffJsonFull $right
     */
    public function isChanged(?array $left, ?array $right): bool
    {
        assert($left || $right, 'One of the $left or $right is required.');

        if (!$left || !$right) {
            return true;
        }

        // @todo The repository which the package is downloaded from also can be
        // changed based on the composer.json#/repositories.
        return $left['version'] !== $right['version']
            || $left['_requiredAs'] !== $right['_requiredAs']
            || $left['_isDirectDependency'] !== $right['_isDirectDependency'];
    }

    /**
     * @phpstan-param ComposerLockDiffLock $lock
     * @phpstan-param null|ComposerLockDiffJson $json
     *
     * @phpstan-return array<string, ComposerLockDiffJsonFull>
     */
    public function normalizePackages(?array $lock, ?array $json): array
    {
        $packages = [];
        foreach (['packages' => 'prod', 'packages-dev' => 'dev'] as $key => $requiredAs) {
            $jsonKey = str_replace('packages', 'require', $key);
            foreach ($lock[$key] ?? [] as $package) {
                $packages[$package['name']] = $package;
                $packages[$package['name']]['_requiredAs'] = $requiredAs;
                $packages[$package['name']]['_isDirectDependency'] = $json ?
                    isset($json[$jsonKey][$package['name']])
                    : null;
            }
        }

        ksort($packages);

        return $packages;
    }
}
