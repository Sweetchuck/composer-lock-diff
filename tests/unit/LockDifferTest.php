<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerLockDiff\Tests\Unit;

use Codeception\Attribute\DataProvider;
use Sweetchuck\ComposerLockDiff\LockDiffer;
use Symfony\Component\Yaml\Yaml;

/**
 * @covers \Sweetchuck\ComposerLockDiff\LockDiffer
 *
 * @phpstan-import-type ComposerLockDiffJson from \Sweetchuck\ComposerLockDiff\Phpstan
 * @phpstan-import-type ComposerLockDiffLock from \Sweetchuck\ComposerLockDiff\Phpstan
 */
class LockDifferTest extends TestBase
{

    /**
     * @phpstan-return array<string, mixed>
     */
    public static function casesDiff(): array
    {
        $dataDir = codecept_data_dir('LockDiffer');

        return Yaml::parseFile("$dataDir/casesDiff.yml");
    }

    /**
     * @phpstan-param array<string, \Sweetchuck\ComposerLockDiff\LockDiffEntry> $expected
     * @phpstan-param ComposerLockDiffLock $leftLock
     * @phpstan-param ComposerLockDiffLock $rightLock
     * @phpstan-param null|ComposerLockDiffJson $leftJson
     * @phpstan-param null|ComposerLockDiffJson $rightJson
     */
    #[DataProvider('casesDiff')]
    public function testDiff(
        array $expected,
        array $leftLock,
        array $rightLock,
        ?array $leftJson = null,
        ?array $rightJson = null,
    ): void {
        $lockDiffer = new LockDiffer();
        $actual = $lockDiffer->diff($leftLock, $rightLock, $leftJson, $rightJson);
        $this->tester->assertSame(
            array_keys($expected),
            array_keys($actual),
            'entries have same keys',
        );
        foreach ($expected as $key => $expectedEntry) {
            $actualEntry = json_decode(json_encode($actual[$key]) ?: '{}', true);
            unset(
                $actualEntry['leftVersion'],
                $actualEntry['rightVersion'],
            );
            $this->tester->assertSame(
                $expectedEntry,
                $actualEntry,
                "entries with key '$key' are the same",
            );
        }
    }
}
