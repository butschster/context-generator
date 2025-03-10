<?php

declare(strict_types=1);

namespace Butschster\ContextGenerator\Fetcher;

use Butschster\ContextGenerator\Source\FileSource;
use Butschster\ContextGenerator\Source\SourceModifierRegistry;
use Butschster\ContextGenerator\SourceInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Fetcher for file sources
 * @implements SourceFetcherInterface<FileSource>
 */
readonly class FileSourceFetcher implements SourceFetcherInterface
{
    /**
     * @param string $basePath Base path for relative file references
     */
    public function __construct(
        private string $basePath,
        private SourceModifierRegistry $modifiers,
        private FileTreeBuilder $treeBuilder = new FileTreeBuilder(),
    ) {}

    public function supports(SourceInterface $source): bool
    {
        return $source instanceof FileSource;
    }

    public function fetch(SourceInterface $source): string
    {
        $content = '';
        if ($source->showTreeView) {
            $finder = $this->createFinder($source);
            $filePaths = [];
            foreach ($finder as $file) {
                $filePaths[] = $file->getPathname();
            }

            // Generate tree view
            $content .= '```' . PHP_EOL;
            $content .= $this->treeBuilder->buildTree($filePaths, $this->basePath);
            $content .= '```' . PHP_EOL . PHP_EOL;
        }

        $finder = $this->createFinder($source);

        foreach ($finder as $file) {
            $relativePath = \trim(\str_replace($this->basePath, '', $file->getPath()));
            $fileName = $file->getFilename();
            $filePath = empty($relativePath) ? $fileName : "$relativePath/$fileName";

            $content .= '```' . PHP_EOL;
            $content .= "// Path: {$filePath}" . PHP_EOL;
            $content .= $this->getContent($file, $source) . PHP_EOL . PHP_EOL;
            $content .= '```' . PHP_EOL;
        }

        return $content;
    }

    protected function getContent(SplFileInfo $file, SourceInterface $source): string
    {
        $content = $file->getContents();

        // Apply modifiers if available
        if (!empty($source->modifiers)) {
            foreach ($source->modifiers as $modifierId) {
                if ($this->modifiers->has($modifierId)) {
                    $modifier = $this->modifiers->get($modifierId);

                    if ($modifier->supports($file->getFilename())) {
                        $context = [
                            'file' => $file,
                            'source' => $source,
                        ];

                        $content = $modifier->modify($content, $context);
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Create a configured Finder instance for a file source
     */
    private function createFinder(FileSource $source): Finder
    {
        $files = [];
        $directories = [];

        // Separate files and directories
        foreach ((array) $source->sourcePaths as $path) {
            match (true) {
                \is_file($path) => $files[] = $path,
                \is_dir($path) => $directories[] = $path,
                default => null,
            };
        }

        // Create finder and configure it
        $finder = new Finder();
        if (!empty($directories)) {
            $finder->name($source->filePattern)->in($directories);
        }

        // Add individual files
        foreach ($files as $file) {
            $finder->append([new SplFileInfo($file, $file, $file)]);
        }

        // Apply exclusion filter if patterns exist
        if (!empty($source->excludePatterns)) {
            $finder->filter(
                fn(SplFileInfo $file): bool => !$this->shouldExcludeFile(
                    file: $file,
                    excludePatterns: $source->excludePatterns,
                ),
            );
        }

        return $finder;
    }

    /**
     * Determine if a file should be excluded based on patterns
     */
    private function shouldExcludeFile(SplFileInfo $file, array $excludePatterns): bool
    {
        foreach ($excludePatterns as $pattern) {
            if (\str_contains($file->getPathname(), (string) $pattern)) {
                return true;
            }
        }

        return false;
    }
}