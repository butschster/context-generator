<?php

declare(strict_types=1);

namespace Butschster\ContextGenerator\Loader;

use Butschster\ContextGenerator\DocumentsLoaderInterface;
use Butschster\ContextGenerator\Loader\ConfigRegistry\DocumentRegistry;

final class CompositeDocumentsLoader implements DocumentsLoaderInterface
{
    /** @var array<DocumentsLoaderInterface> */
    private array $loaders = [];

    public function __construct(DocumentsLoaderInterface ...$loaders)
    {
        $this->loaders = $loaders;
    }

    public function load(): DocumentRegistry
    {
        $registry = new DocumentRegistry();

        foreach ($this->loaders as $loader) {
            if (!$loader->isSupported()) {
                continue;
            }

            $loadedRegistry = $loader->load();

            foreach ($loadedRegistry->getItems() as $document) {
                $registry->register($document);
            }
        }

        return $registry;
    }

    public function isSupported(): bool
    {
        foreach ($this->loaders as $loader) {
            if ($loader->isSupported()) {
                return true;
            }
        }

        return false;
    }
}
