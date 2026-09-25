<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * A classic, non-Composer installation resolves the dependencies of this extension from
 * the `depends` section of `ext_emconf.php`, whose keys are extension keys; a Composer
 * installation resolves them from the `require` section of `composer.json`. Both have to
 * name the same packages - a key that is no extension key is a dependency on a package
 * that does not exist.
 */
final class ExtEmConfDependenciesTest extends UnitTestCase
{
    #[Test]
    public function extEmConfDependsOnTheExtensionsComposerJsonRequires(): void
    {
        $extensionPath = dirname(__DIR__, 2) . '/';
        $_EXTKEY = 'academic_jobs';
        $EM_CONF = [];
        require $extensionPath . 'ext_emconf.php';
        /** @var array<string, array{constraints: array{depends: array<string, string>}}> $EM_CONF */
        /** @var array{require: array<string, string>} $composerManifest */
        $composerManifest = json_decode((string)file_get_contents($extensionPath . 'composer.json'), true, 512, JSON_THROW_ON_ERROR);

        $expected = [];
        foreach (array_keys($composerManifest['require']) as $packageName) {
            if ($packageName === 'php') {
                continue;
            }
            if ($packageName === 'typo3/cms-core') {
                $expected[] = 'typo3';
                continue;
            }
            // "typo3/cms-rte-ckeditor" is "rte_ckeditor", "fgtclb/academic-base" is "academic_base".
            // A require that is no TYPO3 extension needs a skip above, like "php".
            $expected[] = str_replace('-', '_', (string)preg_replace('#^(typo3/cms-|[^/]+/)#', '', $packageName));
        }
        $actual = array_keys($EM_CONF[$_EXTKEY]['constraints']['depends']);
        sort($expected);
        sort($actual);

        $this->assertSame($expected, $actual);
    }
}
