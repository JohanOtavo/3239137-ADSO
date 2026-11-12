<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * T-19 - Articles I, II and III, enforced.
 *
 * PHP has no equivalent of import-linter, so the contract is written as a test:
 * it reads the use statements of every file in a layer and fails when a
 * forbidden one appears. It passes vacuously while a layer is still empty, and
 * grows teeth as soon as the first file lands.
 */
final class LayerDependenciesTest extends TestCase
{
    /** Article I - the domain depends on nothing. */
    public function test_domain_imports_no_other_layer_and_no_framework(): void
    {
        $offenders = $this->importsMatching('app/Domain', [
            'App\Application',
            'App\Adapters',
            'App\Providers',
            'Illuminate',
            'Laravel',
            'Symfony',
        ]);

        $this->assertSame([], $offenders, "The domain imports something it must not:\n".implode("\n", $offenders));
    }

    /** Article II - the application declares ports and knows no adapter. */
    public function test_application_imports_no_adapter_and_no_framework(): void
    {
        $offenders = $this->importsMatching('app/Application', [
            'App\Adapters',
            'App\Providers',
            'Illuminate',
            'Laravel',
            'Symfony',
        ]);

        $this->assertSame([], $offenders, "The application imports something it must not:\n".implode("\n", $offenders));
    }

    /** Article III - infrastructure is only built in the composition root. */
    public function test_only_the_provider_instantiates_adapters(): void
    {
        $adapters = $this->classNamesIn('app/Adapters');
        $offenders = [];

        foreach ($this->filesIn('app') as $file) {
            $path = $this->relative($file);

            if (str_starts_with($path, 'app/Providers/') || str_starts_with($path, 'app/Adapters/')) {
                continue;
            }

            $contents = (string) file_get_contents($file->getPathname());

            foreach ($adapters as $adapter) {
                if (preg_match('/new\s+'.preg_quote($adapter, '/').'\s*\(/', $contents) === 1) {
                    $offenders[] = $path.' builds '.$adapter;
                }
            }
        }

        $this->assertSame([], $offenders, "Infrastructure is built outside the composition root:\n".implode("\n", $offenders));
    }

    /**
     * @param  list<string>  $forbiddenPrefixes
     * @return list<string>
     */
    private function importsMatching(string $directory, array $forbiddenPrefixes): array
    {
        $offenders = [];

        foreach ($this->filesIn($directory) as $file) {
            $contents = (string) file_get_contents($file->getPathname());

            preg_match_all('/^use\s+([^;]+);/m', $contents, $matches);

            foreach ($matches[1] as $import) {
                foreach ($forbiddenPrefixes as $prefix) {
                    if (str_starts_with(trim($import), $prefix)) {
                        $offenders[] = $this->relative($file).' imports '.trim($import);
                    }
                }
            }
        }

        return $offenders;
    }

    /** @return list<string> */
    private function classNamesIn(string $directory): array
    {
        $names = [];

        foreach ($this->filesIn($directory) as $file) {
            $names[] = $file->getBasename('.php');
        }

        return $names;
    }

    /** @return list<SplFileInfo> */
    private function filesIn(string $directory): array
    {
        $root = dirname(__DIR__, 2).'/'.$directory;

        if (! is_dir($root)) {
            return [];
        }

        $files = [];

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = clone $file;
            }
        }

        return $files;
    }

    private function relative(SplFileInfo $file): string
    {
        return str_replace([dirname(__DIR__, 2).DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], ['', '/'], $file->getPathname());
    }
}
