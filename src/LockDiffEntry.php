<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerLockDiff;

use Sweetchuck\Utils\VersionNumber;

/**
 * @phpstan-import-type ComposerLockDiffJson            from \Sweetchuck\ComposerLockDiff\Phpstan
 * @phpstan-import-type ComposerLockDiffJsonFull        from \Sweetchuck\ComposerLockDiff\Phpstan
 * @phpstan-import-type ComposerLockDiffLock            from \Sweetchuck\ComposerLockDiff\Phpstan
 * @phpstan-import-type ComposerLockDiffEntryExport     from \Sweetchuck\ComposerLockDiff\Phpstan
 * @phpstan-import-type ComposerLockDiffVersionFragment from \Sweetchuck\ComposerLockDiff\Phpstan
 *
 * @property-read string $requireAction
 *   Values: none, add, remove, move.
 * @property-read string $requiredAsAction
 *   Values: none, prod, dev.
 * @property-read string $versionAction
 *   Values: none, upgrade, downgrade.
 * @property-read string $name
 * @property-read string $type
 * @property-read ?string $leftType
 * @property-read ?string $rightType
 * @property-read ?string $leftVersionString
 * @property-read ?string $rightVersionString
 * @property-read ?\Sweetchuck\Utils\VersionNumber $leftVersion
 * @property-read ?\Sweetchuck\Utils\VersionNumber $rightVersion
 * @property-read ?string $leftRequiredAs
 * @property-read ?string $rightRequiredAs
 * @property-read ?bool $isLeftDirectDependency
 * @property-read ?bool $isRightDirectDependency
 */
class LockDiffEntry implements \JsonSerializable
{

    readonly public ?VersionNumber $leftVersion;

    readonly public ?VersionNumber $rightVersion;

    readonly public ?string $versionFragmentChanged;

    readonly public bool $isVersionChanged;

    readonly public bool $isVersionMajorChanged;

    readonly public bool $isVersionMinorChanged;

    readonly public bool $isVersionPatchChanged;

    readonly public bool $isVersionPreReleaseChanged;

    readonly public bool $isVersionMetadataChanged;

    /**
     * @phpstan-var null|ComposerLockDiffJsonFull
     */
    protected ?array $left;

    /**
     * @phpstan-return null|ComposerLockDiffJsonFull
     */
    public function getLeft(): ?array
    {
        return $this->left;
    }

    /**
     * @phpstan-var null|ComposerLockDiffJsonFull
     */
    protected ?array $right;

    /**
     * @phpstan-return null|ComposerLockDiffJsonFull
     */
    public function getRight(): ?array
    {
        return $this->right;
    }

    protected string $defaultType = 'library';

    /**
     * @phpstan-param null|ComposerLockDiffJsonFull $left
     * @phpstan-param null|ComposerLockDiffJsonFull $right
     */
    public function __construct(
        ?array $left = null,
        ?array $right = null,
    ) {
        assert($left || $right, 'At least one of the package has to be provided');

        $this->left = $left;
        $this->right = $right;

        $this
            ->initLeft()
            ->initRight();

        if (!$left) {
            $this->initAdded();
        } elseif (!$right) {
            $this->initRemoved();
        } else {
            $this->initChanged();
        }
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'name' => $this->getRightProperty('name') ?: $this->getLeftProperty('name'),
            'type' => $this->getRightProperty('type') ?: $this->getLeftProperty('type') ?: $this->defaultType,
            'leftType' => $this->getLeftProperty('type') ?: $this->defaultType,
            'rightType' => $this->getRightProperty('type') ?: $this->defaultType,
            'leftVersionString' => $this->getLeftProperty('version'),
            'rightVersionString' => $this->getRightProperty('version'),
            'leftRequiredAs' => $this->getLeftProperty('_requiredAs'),
            'rightRequiredAs' => $this->getRightProperty('_requiredAs'),
            'isLeftDirectDependency' => $this->getLeftProperty('_isDirectDependency'),
            'isRightDirectDependency' => $this->getRightProperty('_isDirectDependency'),
            'requireAction' => $this->getRequireAction(),
            'requiredAsAction' => $this->getRequiredAsAction(),
            'versionAction' => $this->getVersionAction(),
            default => throw new \InvalidArgumentException("property $name is not exists."),
        };
    }

    /**
     * @phpstan-return ComposerLockDiffEntryExport
     */
    public function jsonSerialize(): array
    {
        return [
            'requireAction' => $this->requireAction,
            'requiredAsAction' => $this->requiredAsAction,
            'versionAction' => $this->versionAction,
            'name' => $this->name,
            'type' => $this->type,
            'leftType' => $this->leftType,
            'rightType' => $this->rightType,
            'leftVersionString' => $this->leftVersionString,
            'rightVersionString' => $this->rightVersionString,
            'leftRequiredAs' => $this->leftRequiredAs,
            'rightRequiredAs' => $this->rightRequiredAs,
            'isLeftDirectDependency' => $this->isLeftDirectDependency,
            'isRightDirectDependency' => $this->isRightDirectDependency,
            'versionFragmentChanged' => $this->versionFragmentChanged,
            'isVersionChanged' => $this->isVersionChanged,
            'isVersionMajorChanged' => $this->isVersionMajorChanged,
            'isVersionMinorChanged' => $this->isVersionMinorChanged,
            'isVersionPatchChanged' => $this->isVersionPatchChanged,
            'isVersionPreReleaseChanged' => $this->isVersionPreReleaseChanged,
            'isVersionMetadataChanged' => $this->isVersionMetadataChanged,
        ];
    }

    protected function getLeftProperty(string $key): mixed
    {
        return $this->left[$key] ?? null;
    }

    protected function getRightProperty(string $key): mixed
    {
        return $this->right[$key] ?? null;
    }

    protected function getRequireAction(): string
    {
        if (!$this->left) {
            return 'add';
        }

        if (!$this->right) {
            return 'remove';
        }

        return $this->left['_requiredAs'] === $this->right['_requiredAs'] ?
            'none'
            : 'move';
    }

    protected function getRequiredAsAction(): string
    {
        return match ($this->getRequireAction()) {
            'remove' => $this->left['_requiredAs'],
            // In case of: none, add, move.
            default => $this->right['_requiredAs'],
        };
    }

    protected function getVersionAction(): string
    {
        if (!$this->left) {
            return 'upgrade';
        }

        if (!$this->right) {
            return 'downgrade';
        }

        if ($this->leftVersion && $this->rightVersion) {
            $pattern = '/^\d+(\.\d+)?\.x-dev$/';
            $isLeftBranch = preg_match($pattern, $this->leftVersionString) === 1;
            $isRightBranch = preg_match($pattern, $this->rightVersionString) === 1;
            if (($isLeftBranch && $isRightBranch)
                || (!$isLeftBranch && !$isRightBranch)
            ) {
                return match (version_compare($this->leftVersionString, $this->rightVersionString)) {
                    -1 => 'upgrade',
                    0 => 'none',
                    default => 'downgrade',
                };
            }

            $diff = $this->leftVersion->diff($this->rightVersion);
            $diffMajor = $diff['major'] ?? null;
            $diffMinor = $diff['minor'] ?? null;
            $diffPatch = $diff['patch'] ?? null;
            if (!$diffMajor && !$diffMinor && !$diffPatch) {
                return $isLeftBranch ?
                    'downgrade'
                    : 'upgrade';
            }
        }

        return match (version_compare($this->leftVersionString, $this->rightVersionString)) {
            -1 => 'upgrade',
            0 => 'none',
            default => 'downgrade',
        };
    }

    protected function initAdded(): static
    {
        $this->isVersionChanged = true;
        $this->versionFragmentChanged = 'major';
        $this->initVersionChangedFragments(0);

        return $this;
    }

    protected function initRemoved(): static
    {
        $this->isVersionChanged = true;
        $this->versionFragmentChanged = 'major';
        $this->initVersionChangedFragments(0);

        return $this;
    }

    protected function initChanged(): static
    {
        $this->initVersionChanged();

        return $this;
    }

    protected function initLeft(): static
    {
        $this->leftVersion = $this->createVersionNumber($this->leftVersionString);

        return $this;
    }

    protected function initRight(): static
    {
        $this->rightVersion = $this->createVersionNumber($this->rightVersionString);

        return $this;
    }

    protected function initVersionChanged(): static
    {
        if ($this->leftVersionString === $this->rightVersionString) {
            $this->versionFragmentChanged = null;
            $this->isVersionChanged = false;
            $this->initVersionChangedFragments(99);

            return $this;
        }

        $this->isVersionChanged = true;
        if (!$this->leftVersion || !$this->rightVersion) {
            // Version strings can not be compared.
            $this->versionFragmentChanged = null;
            $this->initVersionChangedFragments(99);

            return $this;
        }

        $trueFrom = 99;
        if ($this->leftVersion->major !== $this->rightVersion->major) {
            $this->versionFragmentChanged = 'major';
            $trueFrom = 0;
        } elseif ($this->leftVersion->minor !== $this->rightVersion->minor) {
            $this->versionFragmentChanged = 'minor';
            $trueFrom = 1;
        } elseif ($this->leftVersion->patch !== $this->rightVersion->patch) {
            $this->versionFragmentChanged = 'patch';
            $trueFrom = 2;
        } elseif ($this->leftVersion->preRelease !== $this->rightVersion->preRelease) {
            $this->versionFragmentChanged = 'preRelease';
            $trueFrom = 3;
        } elseif ($this->leftVersion->metadata !== $this->rightVersion->metadata) {
            $this->versionFragmentChanged = 'metadata';
            $trueFrom = 4;
        }

        $this->initVersionChangedFragments($trueFrom);

        return $this;
    }

    protected function initVersionChangedFragments(int $trueFrom): static
    {
        $this->isVersionMajorChanged = $trueFrom <= 0;
        $this->isVersionMinorChanged = $trueFrom <= 1;
        $this->isVersionPatchChanged = $trueFrom <= 2;
        $this->isVersionPreReleaseChanged = $trueFrom <= 3;
        $this->isVersionMetadataChanged = $trueFrom <= 4;

        return $this;
    }

    protected function createVersionNumber(?string $string): ?VersionNumber
    {
        if ($string === null) {
            return null;
        }

        $string = preg_replace('/^v(\d+\.)/u', '$1', $string);

        return VersionNumber::isValid($string) ?
            VersionNumber::createFromString($string)
            : null;
    }
}
