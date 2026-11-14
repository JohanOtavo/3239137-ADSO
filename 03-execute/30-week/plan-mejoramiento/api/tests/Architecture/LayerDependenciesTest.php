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
        $offenders = $this->adaptersBuiltIn('app', $this->classNamesIn('app/Adapters'));

        $this->assertSame([], $offenders, "Infrastructure is built outside the composition root:\n".implode("\n", $offenders));
    }

    /**
     * The check has to be seen failing.
     *
     * Everything above passes, and a check that has only ever passed proves
     * that it runs, not that it looks at anything. The two fixtures are a
     * domain class importing the framework and a use case building its own
     * adapter: the exact mistakes articles I and III exist to stop.
     */
    public function test_a_forbidden_import_is_caught(): void
    {
        $offenders = $this->importsMatching('tests/Architecture/Fixtures/ForbiddenImport', [
            'Illuminate',
        ]);

        $this->assertCount(1, $offenders);
        $this->assertStringContainsString('DomainWithFramework.php', $offenders[0]);
        $this->assertStringContainsString('Illuminate\Support\Collection', $offenders[0]);
    }

    public function test_an_adapter_built_outside_the_composition_root_is_caught(): void
    {
        $offenders = $this->adaptersBuiltIn(
            'tests/Architecture/Fixtures/AdapterOutsideRoot',
            ['DatabaseEquipmentRepository'],
        );

        $this->assertCount(1, $offenders);
        $this->assertStringContainsString('ServiceBuildingAnAdapter.php', $offenders[0]);
        $this->assertStringContainsString('builds DatabaseEquipmentRepository', $offenders[0]);
    }

    /**
     * Files under the directory that build one of the given adapters.
     *
     * @param  list<string>  $adapters
     * @return list<string>
     */
    private function adaptersBuiltIn(string $directory, array $adapters): array
    {
        $offenders = [];

        foreach ($this->filesIn($directory) as $file) {
            $path = $this->relative($file);

            // The root may build adapters; that is what it is for. So may an
            // adapter, which often wraps another.
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

        return $offenders;
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
