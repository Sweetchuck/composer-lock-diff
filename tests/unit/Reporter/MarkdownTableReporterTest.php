<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerLockDiff\Tests\Unit\Reporter;

use Codeception\Attribute\DataProvider;
use Sweetchuck\ComposerLockDiff\LockDiffEntry;
use Sweetchuck\ComposerLockDiff\LockDiffer;
use Sweetchuck\ComposerLockDiff\Reporter\MarkdownTableReporter;
use Sweetchuck\Utils\Filter\CustomFilter;

/**
 * @covers \Sweetchuck\ComposerLockDiff\Reporter\TableReporterBase
 * @covers \Sweetchuck\ComposerLockDiff\Reporter\MarkdownTableReporter
 *
 * @phpstan-import-type ComposerLockDiffJson     from \Sweetchuck\ComposerLockDiff\Phpstan
 * @phpstan-import-type ComposerLockDiffJsonFull from \Sweetchuck\ComposerLockDiff\Phpstan
 * @phpstan-import-type ComposerLockDiffLock     from \Sweetchuck\ComposerLockDiff\Phpstan
 */
class MarkdownTableReporterTest extends ReporterTestBase
{

    /**
     * @phpstan-return array<string, mixed[]>
     */
    public static function casesGenerate(): array
    {
        $optionSets = [
            'all-in-one' => [
                'default' => [],
                'group-by-right-direct' => [
                    'groups' => [
                        'direct-prod' => [
                            'enabled' => true,
                            'id' => 'direct-prod',
                            'title' => 'Direct prod',
                            'weight' => 0,
                            'showEmpty' => false,
                            'emptyContent' => '-- empty --',
                            'filter' => (new CustomFilter())
                                ->setOperator(function (LockDiffEntry $entry): bool {
                                    return $entry->isRightDirectDependency && $entry->rightRequiredAs === 'prod';
                                }),
                            'comparer' => null,
                        ],
                        'direct-dev' => [
                            'enabled' => true,
                            'id' => 'direct-dev',
                            'title' => 'Direct dev',
                            'weight' => 1,
                            'showEmpty' => false,
                            'emptyContent' => '-- empty --',
                            'filter' => (new CustomFilter())
                                ->setOperator(function (LockDiffEntry $entry): bool {
                                    return $entry->isRightDirectDependency && $entry->rightRequiredAs === 'dev';
                                }),
                            'comparer' => null,
                        ],
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
                    ],
                ],
            ],
            'basic' => [
                'default' => [],
            ],
        ];

        return static::collectGenerateCases(
            MarkdownTableReporter::class,
            'txt',
            $optionSets,
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
            $options['stream'] = static::createStream();
        }

        $differ = new LockDiffer();
        $entries = $differ->diff($leftLock, $rightLock, $leftJson, $rightJson);
        (new MarkdownTableReporter())
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
