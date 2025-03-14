<?php

declare(strict_types=1);

namespace Butschster\ContextGenerator\Console;

use Butschster\ContextGenerator\Document\Document;
use Butschster\ContextGenerator\Loader\ConfigRegistry\DocumentRegistry;
use Butschster\ContextGenerator\Source\Text\TextSource;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'init',
    description: 'Initialize a new context configuration file',
)]
final class InitCommand extends Command
{
    private const DEFAULT_CONFIG_NAME = 'context.json';

    public function __construct(public string $baseDir)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                name: 'filename',
                mode: InputArgument::OPTIONAL,
                description: 'The name of the file to create',
                default: self::DEFAULT_CONFIG_NAME,
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $outputStyle = new SymfonyStyle($input, $output);
        $filename = $input->getArgument('filename') ?: self::DEFAULT_CONFIG_NAME;

        $filePath = \sprintf('%s/%s', $this->baseDir, $filename);

        if (\file_exists($filePath)) {
            $outputStyle->error(\sprintf('Config %s already exists', $filePath));

            return Command::FAILURE;
        }

        $content = \json_encode(new DocumentRegistry([
            new Document(
                description: 'Your description here',
                outputPath: 'context.md',
                firstSource: new TextSource(
                    content: 'My first context',
                    description: 'First context',
                ),
            ),
        ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        \file_put_contents($filePath, $content);

        $outputStyle->success(\sprintf('Config %s created', $filePath));

        return Command::SUCCESS;
    }
}
