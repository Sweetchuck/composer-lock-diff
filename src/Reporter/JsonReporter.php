<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerLockDiff\Reporter;

use Sweetchuck\ComposerLockDiff\ReporterInterface;

class JsonReporter implements ReporterInterface
{

    use StreamOutputTrait;

    protected int $jsonEncodeFlags = \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE;

    public function getJsonEncodeFlags(): int
    {
        return $this->jsonEncodeFlags;
    }

    protected function setJsonEncodeFlags(int $flags): static
    {
        $this->jsonEncodeFlags = $flags;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options): static
    {
        if (array_key_exists('stream', $options)) {
            $this->setStream($options['stream']);
        }

        if (array_key_exists('jsonEncodeFlags', $options)) {
            $this->setJsonEncodeFlags($options['jsonEncodeFlags']);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $entries): static
    {
        if (!$entries) {
            fwrite(
                $this->getStream(),
                '{}' . \PHP_EOL,
            );

            return $this;
        }

        fwrite(
            $this->getStream(),
            (json_encode($entries, $this->getJsonEncodeFlags()) ?: '{}') . \PHP_EOL,
        );

        return $this;
    }
}
