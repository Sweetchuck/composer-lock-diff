<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerLockDiff\Tests\Unit;

use Codeception\Attribute\DataProvider;
use Sweetchuck\ComposerLockDiff\LockDiffEntry;
use Symfony\Component\Yaml\Yaml;

/**
 * @covers \Sweetchuck\ComposerLockDiff\LockDiffEntry
 *
 * @phpstan-import-type ComposerLockDiffJsonFull from \Sweetchuck\ComposerLockDiff\Phpstan
 */
class LockDiffEntryTest extends TestBase
{

    /**
     * @phpstan-return array<string, mixed>
     */
    public static function casesConstructor(): array
    {
        $dataDir = codecept_data_dir('LockDiffEntry');

        return Yaml::parseFile("$dataDir/casesConstruct.yml");
    }

    /**
     * @phpstan-param array<string, mixed> $expected
     * @phpstan-param null|ComposerLockDiffJsonFull $left
     * @phpstan-param null|ComposerLockDiffJsonFull $right
     */
    #[DataProvider('casesConstructor')]
    public function testConstructor(
        array $expected,
        ?array $left = null,
        ?array $right = null,
    ): void {
        $actual = new LockDiffEntry($left, $right);

        foreach ($expected as $property => $expectedValue) {
            static::assertSame(
                $expectedValue,
                $actual->{$property},
                "\$actual->$property is good",
            );
        }
    }
}
