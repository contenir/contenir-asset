<?php

declare(strict_types=1);

namespace Contenir\Asset\Command;

use Contenir\Asset\Service\ImageCache;
use Contenir\Asset\Service\ImageProcessor;
use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_filter;
use function array_key_exists;
use function count;
use function explode;
use function in_array;
use function is_array;
use function is_dir;
use function sprintf;
use function str_contains;
use function str_replace;
use function strtolower;
use function trim;

/**
 * Generate Asset Variations Command
 *
 * Generates all image variations based on configured presets.
 * This pre-generates variations that would normally be created on-demand.
 *
 * Usage:
 *   vendor/bin/laminas asset:generate-variations           # Generate all missing variations
 *   vendor/bin/laminas asset:generate-variations --force   # Regenerate all variations
 *   vendor/bin/laminas asset:generate-variations --preset=hero,thumbnail
 *   vendor/bin/laminas asset:generate-variations --path=library/project/axton
 */
class GenerateVariationsCommand extends Command
{
    private const SOURCE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    private SymfonyStyle $io;
    private InputInterface $input;
    private array $stats = [
        'generated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    public function __construct(
        private readonly ImageProcessor $processor,
        private readonly ImageCache $cache,
        private readonly array $config,
        private readonly string $publicPath
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('asset:generate-variations')
            ->setAliases(['asset:variations'])
            ->setDescription('Generate image variations from configured presets')
            ->setHelp(<<<'HELP'
                The <info>asset:generate-variations</info> command generates all image variations
                based on presets defined in your configuration.

                By default, it only generates missing variations. Use <comment>--force</comment> to regenerate all.

                Examples:
                  <info>php vendor/bin/laminas asset:generate-variations</info>
                  <info>php vendor/bin/laminas asset:generate-variations --force</info>
                  <info>php vendor/bin/laminas asset:generate-variations --preset=hero,thumbnail</info>
                  <info>php vendor/bin/laminas asset:generate-variations --path=library/project</info>
                HELP)
            ->addOption(
                'preset',
                'p',
                InputOption::VALUE_REQUIRED,
                'Filter by preset names (comma-separated)'
            )
            ->addOption(
                'dimension',
                'd',
                InputOption::VALUE_REQUIRED,
                'Filter by specific dimensions (comma-separated, e.g., "800x450,1920x1080")'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Filter by formats (comma-separated, e.g., "avif,webp")'
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_REQUIRED,
                'Filter by source path pattern (e.g., "library/project/axton")'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Regenerate existing variations (overwrite)'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show what would be generated without actually generating'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->io = new SymfonyStyle($input, $output);

        $this->io->title('Contenir Asset - Generate Variations');

        // Validate configuration
        if (! isset($this->config['presets']) || empty($this->config['presets'])) {
            $this->io->error('No presets configured in contenir_asset.presets');
            return Command::FAILURE;
        }

        $dryRun = (bool) $input->getOption('dry-run');
        $force = (bool) $input->getOption('force');

        if ($dryRun) {
            $this->io->note('DRY RUN MODE - No files will be generated');
        }

        if ($force && ! $dryRun) {
            $this->io->warning('Force mode enabled - existing variations will be overwritten');
        }

        // Step 1: Build variation matrix from presets
        $this->io->section('Step 1: Analyzing Presets');
        $presets = $this->filterPresets($input);
        $variations = $this->buildVariationMatrix($presets);

        $this->io->writeln(sprintf(
            'Found <info>%d presets</info> with <info>%d total variations</info>',
            count($presets),
            count($variations)
        ));

        // Step 2: Discover source images
        $this->io->section('Step 2: Discovering Source Images');
        $sourceImages = $this->discoverSourceImages($input->getOption('path'));

        if (empty($sourceImages)) {
            $this->io->warning('No source images found');
            return Command::SUCCESS;
        }

        $this->io->writeln(sprintf('Found <info>%d source images</info>', count($sourceImages)));

        // Step 3: Generate variations
        $this->io->section('Step 3: Generating Variations');
        $totalTasks = count($variations) * count($sourceImages);

        $this->io->writeln(sprintf(
            'Total tasks: <comment>%d</comment> (variations × images)',
            $totalTasks
        ));

        $this->io->newLine();

        $this->generateVariations($variations, $sourceImages, $force, $dryRun, $output);

        // Step 4: Summary
        $this->io->newLine(2);
        $this->io->section('Summary');

        $this->io->definitionList(
            ['Generated' => sprintf('<info>%d</info>', $this->stats['generated'])],
            ['Skipped (already exist)' => sprintf('<comment>%d</comment>', $this->stats['skipped'])],
            ['Errors' => sprintf('<error>%d</error>', $this->stats['errors'])]
        );

        if ($this->stats['errors'] > 0) {
            $this->io->warning(sprintf('%d variations failed to generate', $this->stats['errors']));
            return Command::FAILURE;
        }

        if ($dryRun) {
            $this->io->success('Dry run completed - no files were modified');
        } else {
            $this->io->success('Variation generation completed successfully');
        }

        return Command::SUCCESS;
    }

    /**
     * Filter presets based on command options
     */
    private function filterPresets(InputInterface $input): array
    {
        $presets = $this->config['presets'];
        $presetFilter = $input->getOption('preset');

        if ($presetFilter) {
            $allowedPresets = array_map('trim', explode(',', $presetFilter));
            $presets = array_filter(
                $presets,
                fn($name) => in_array($name, $allowedPresets, true),
                ARRAY_FILTER_USE_KEY
            );

            if (empty($presets)) {
                $this->io->warning(sprintf(
                    'No presets matched filter: %s',
                    $presetFilter
                ));
            }
        }

        return $presets;
    }

    /**
     * Build variation matrix from presets
     *
     * @return array<array{preset: string, dimension: string, format: string, crop: string, focal: array}>
     */
    private function buildVariationMatrix(array $presets): array
    {
        $defaultFormats = $this->config['default_formats'] ?? ['avif', 'webp', 'jpg'];
        $dimensionFilter = $this->getDimensionFilter();
        $formatFilter = $this->getFormatFilter();

        $matrix = [];

        foreach ($presets as $presetName => $preset) {
            if (! is_array($preset) || ! isset($preset['dimensions'])) {
                continue;
            }

            $dimensions = $preset['dimensions'];
            $formats = $preset['formats'] ?? $defaultFormats;
            $crop = $preset['crop'] ?? 'cover';

            foreach ($dimensions as $dimension => $descriptor) {
                // Apply dimension filter
                if ($dimensionFilter && ! in_array($dimension, $dimensionFilter, true)) {
                    continue;
                }

                foreach ($formats as $format) {
                    // Apply format filter
                    if ($formatFilter && ! in_array($format, $formatFilter, true)) {
                        continue;
                    }

                    $matrix[] = [
                        'preset' => $presetName,
                        'dimension' => $dimension,
                        'format' => $format,
                        'crop' => $crop,
                        'focal' => [0.5, 0.5], // Default focal point (center)
                    ];
                }
            }
        }

        return $matrix;
    }

    /**
     * Get dimension filter from input
     */
    private function getDimensionFilter(): ?array
    {
        $dimensionOption = $this->input->getOption('dimension');
        if (! $dimensionOption) {
            return null;
        }

        return array_map('trim', explode(',', $dimensionOption));
    }

    /**
     * Get format filter from input
     */
    private function getFormatFilter(): ?array
    {
        $formatOption = $this->input->getOption('format');
        if (! $formatOption) {
            return null;
        }

        return array_map('trim', explode(',', $formatOption));
    }

    /**
     * Discover source images from filesystem
     *
     * @return array<string> Array of relative paths to source images
     */
    private function discoverSourceImages(?string $pathFilter = null): array
    {
        $baseDir = $this->publicPath . '/library';

        if (! is_dir($baseDir)) {
            $this->io->error(sprintf('Source directory not found: %s', $baseDir));
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $images = [];

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $extension = strtolower($file->getExtension());
            if (! in_array($extension, self::SOURCE_EXTENSIONS, true)) {
                continue;
            }

            $relativePath = str_replace($this->publicPath . '/', '', $file->getPathname());

            // Apply path filter
            if ($pathFilter && ! str_contains($relativePath, $pathFilter)) {
                continue;
            }

            $images[] = $relativePath;
        }

        return $images;
    }

    /**
     * Generate variations
     */
    private function generateVariations(
        array $variations,
        array $sourceImages,
        bool $force,
        bool $dryRun,
        OutputInterface $output
    ): void {
        $totalTasks = count($variations) * count($sourceImages);
        $progress = new ProgressBar($output, $totalTasks);
        $progress->setFormat('verbose');
        $progress->start();

        foreach ($sourceImages as $sourcePath) {
            $sourceFullPath = $this->publicPath . '/' . $sourcePath;

            foreach ($variations as $variation) {
                $cachePath = $this->cache->getCachePath(
                    $sourcePath,
                    $variation['dimension'],
                    $variation['format']
                );

                $filesystemPath = $this->cache->getFilesystemPath($cachePath);

                // Skip if exists and not forcing
                if (! $force && $this->cache->exists($cachePath)) {
                    $this->stats['skipped']++;
                    $progress->advance();
                    continue;
                }

                if ($dryRun) {
                    $this->stats['generated']++;
                    $progress->advance();
                    continue;
                }

                // Generate variation
                try {
                    $this->cache->ensureDirectory($cachePath);
                    $this->processor->generate(
                        $sourceFullPath,
                        $filesystemPath,
                        $variation['dimension'],
                        [
                            'format' => $variation['format'],
                            'crop' => $variation['crop'],
                            'focal' => $variation['focal'],
                        ]
                    );
                    $this->stats['generated']++;
                } catch (Exception $e) {
                    $this->stats['errors']++;

                    // Log error in verbose mode
                    if ($output->isVerbose()) {
                        $output->writeln('');
                        $output->writeln(sprintf(
                            '<error>Error:</error> %s → %s: %s',
                            $sourcePath,
                            $variation['dimension'] . '.' . $variation['format'],
                            $e->getMessage()
                        ));
                    }
                }

                $progress->advance();
            }
        }

        $progress->finish();
    }
}