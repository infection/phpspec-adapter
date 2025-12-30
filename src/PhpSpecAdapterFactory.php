<?php
/**
 * This code is licensed under the BSD 3-Clause License.
 *
 * Copyright (c) 2017, Maks Rafalko
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * * Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

declare(strict_types=1);

namespace Infection\TestFramework\PhpSpec;

use function file_get_contents;
use Infection\AbstractTestFramework\TestFrameworkAdapter;
use Infection\AbstractTestFramework\TestFrameworkAdapterFactory;
use Infection\TestFramework\PhpSpec\CommandLine\ArgumentsAndOptionsBuilder;
use Infection\TestFramework\PhpSpec\CommandLine\CommandLineBuilder;
use Infection\TestFramework\PhpSpec\Config\InitialConfigBuilder;
use Infection\TestFramework\PhpSpec\Config\MutationAutoloadTemplate;
use Infection\TestFramework\PhpSpec\Config\MutationConfigBuilder;
use Infection\TestFramework\PhpSpec\Version\CachedVersionProvider;
use Infection\TestFramework\PhpSpec\Version\ProcessVersionProvider;
use Infection\TestFramework\PhpSpec\Version\VersionParser;
use InvalidArgumentException;
use function method_exists;
use function sprintf;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

final readonly class PhpSpecAdapterFactory implements TestFrameworkAdapterFactory
{
    private const NAME = 'PhpSpec';

    /**
     * @param string[] $sourceDirectories
     */
    public static function create(
        string $testFrameworkExecutable,
        string $tmpDirectory,
        string $testFrameworkConfigPath,
        ?string $testFrameworkConfigDir,
        string $jUnitFilePath,
        string $projectDirectory,
        array $sourceDirectories,
        bool $skipCoverage,
    ): TestFrameworkAdapter {
        $filesystem = new Filesystem();

        $phpSpecConfigDecodedContents = self::getPhpSpecConfigDecodedContents(
            $filesystem,
            $testFrameworkConfigPath,
        );

        $coverageDirectoryPath = self::createDefaultCoverageXmlDirectoryPath($tmpDirectory);

        $commandLineBuilder = new CommandLineBuilder();

        return new PhpSpecAdapter(
            self::NAME,
            $testFrameworkExecutable,
            new InitialConfigBuilder(
                $tmpDirectory,
                $coverageDirectoryPath,
                $phpSpecConfigDecodedContents,
                $skipCoverage,
                $filesystem,
            ),
            new MutationConfigBuilder(
                $tmpDirectory,
                $phpSpecConfigDecodedContents,
                MutationAutoloadTemplate::create(
                    $projectDirectory,
                    $phpSpecConfigDecodedContents,
                ),
                $filesystem,
            ),
            new ArgumentsAndOptionsBuilder(),
            new CachedVersionProvider(
                new ProcessVersionProvider(
                    $testFrameworkExecutable,
                    $commandLineBuilder,
                    new VersionParser(self::NAME),
                ),
            ),
            $commandLineBuilder,
            new TapTestChecker(),
        );
    }

    public static function getAdapterName(): string
    {
        return 'phpspec';
    }

    public static function getExecutableName(): string
    {
        return 'phpspec';
    }

    private static function createDefaultCoverageXmlDirectoryPath(string $tmpDirectory): string
    {
        return $tmpDirectory . '/coverage-xml';
    }

    /**
     * @return mixed[]
     */
    private static function getPhpSpecConfigDecodedContents(
        Filesystem $filesystem,
        string $testFrameworkConfigPath,
    ): array {
        // TODO: remove the polyfill code once we drop support for Symfony 6.4
        // @phpstan-ignore function.alreadyNarrowedType
        $phpSpecConfigContents = method_exists($filesystem, 'readFile')
            ? $filesystem->readFile($testFrameworkConfigPath)
            : file_get_contents($testFrameworkConfigPath);

        if ($phpSpecConfigContents === false) {
            throw new InvalidArgumentException(
                sprintf(
                    'Could not read PHPSpec configuration file "%s".',
                    $testFrameworkConfigPath,
                ),
            );
        }

        return Yaml::parse($phpSpecConfigContents);
    }
}
