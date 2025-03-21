<?php

declare(strict_types=1);

namespace Butschster\ContextGenerator\Fetcher;

use Butschster\ContextGenerator\Modifier\ModifiersApplierInterface;
use Butschster\ContextGenerator\SourceInterface;
use Butschster\ContextGenerator\SourceParserInterface;

/**
 * Registry for source fetchers
 */
final class SourceFetcherRegistry implements SourceParserInterface
{
    public function __construct(
        /**
         * @var array<SourceFetcherInterface>
         */
        private array $fetchers = [],
    ) {}

    /**
     * Register a source fetcher
     */
    public function register(SourceFetcherInterface $fetcher): self
    {
        $this->fetchers[] = $fetcher;

        return $this;
    }

    /**
     * Find a fetcher that supports the given source
     *
     * @throws \RuntimeException If no fetcher supports the source
     */
    public function findFetcher(SourceInterface $source): SourceFetcherInterface
    {
        foreach ($this->fetchers as $fetcher) {
            if ($fetcher->supports($source)) {
                return $fetcher;
            }
        }

        throw new \RuntimeException(
            \sprintf(
                'No fetcher found for source of type %s',
                $source::class,
            ),
        );
    }

    public function parse(
        SourceInterface $source,
        ModifiersApplierInterface $modifiersApplier,
    ): string {
        return $this->findFetcher($source)->fetch($source, $modifiersApplier);
    }
}
