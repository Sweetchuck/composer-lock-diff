<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerLockDiff\Tests\Unit\Reporter;

use Codeception\Attribute\DataProvider;
use Sweetchuck\ComposerLockDiff\LockDiffer;
use Sweetchuck\ComposerLockDiff\Reporter\JsonReporter;

/**
 * @covers \Sweetchuck\ComposerLockDiff\Reporter\JsonReporter
 * @covers \Sweetchuck\ComposerLockDiff\LockDiffEntry
 *
 * @phpstan-import-type ComposerLockDiffJson     from \Sweetchuck\ComposerLockDiff\Phpstan
 * @phpstan-import-type ComposerLockDiffJsonFull from \Sweetchuck\ComposerLockDiff\Phpstan
 * @phpstan-import-type ComposerLockDiffLock     from \Sweetchuck\ComposerLockDiff\Phpstan
 */
class JsonReporterTest extends ReporterTestBase
{

    /**
     * @phpstan-return array<string, mixed[]>
     */
    public static function casesGenerate(): array
    {
        return static::collectGenerateCases(
            JsonReporter::class,
            'json',
            [
                'basic' => [
                    'default' => [],
                    'flags' => [
                        'jsonEncodeFlags' => \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE,
                    ],
                ],
            ],
        );
    }

    /**
     * @phpstan-param null|ComposerLockDiffLock $leftLock
     * @phpstan-param null|ComposerLockDiffLock $rightLock
     * @phpstan-param null|ComposerLockDiffJson $leftJson
     * @phpstan-param null|ComposerLockDiffJson $rightJson
     * @phpstan-param array<string, mixed> $options
     */
    #[DataProvider('casesGenerate')]
    public function testGenerate(
        string $expected,
        ?array $leftLock = null,
        ?array $rightLock = null,
        ?array $leftJson = null,
        ?array $rightJson = null,
        array $options = [],
    ): void {
        if (!isset($options['stream'])) {
            $options['stream'] = $this->createStream();
        }

        $differ = new LockDiffer();
        $entries = $differ->diff($leftLock, $rightLock, $leftJson, $rightJson);
        (new JsonReporter())
            ->setOptions($options)
            ->generate($entries);
        rewind($options['stream']);
        $this->tester->assertSame(
            $expected,
            stream_get_contents($options['stream']),
        );
        fclose($options['stream']);
    }
}
