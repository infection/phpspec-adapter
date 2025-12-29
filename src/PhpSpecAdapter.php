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

use function explode;
use Infection\AbstractTestFramework\Coverage\TestLocation;
use Infection\AbstractTestFramework\TestFrameworkAdapter;
use Infection\TestFramework\PhpSpec\CommandLine\ArgumentsAndOptionsBuilder;
use Infection\TestFramework\PhpSpec\CommandLine\CommandLineBuilder;
use Infection\TestFramework\PhpSpec\Config\InitialConfigBuilder;
use Infection\TestFramework\PhpSpec\Config\MutationConfigBuilder;
use Infection\TestFramework\PhpSpec\Throwable\NoCodeCoverageConfigured;
use Infection\TestFramework\PhpSpec\Throwable\UnrecognisableConfiguration;
use Infection\TestFramework\PhpSpec\Version\VersionProvider;
use const PHP_EOL;
use function preg_match;
use function sprintf;

final readonly class PhpSpecAdapter implements TestFrameworkAdapter
{
    public const COVERAGE_DIR = 'phpspec-coverage-xml';

    private const ERROR_REGEXPS = [
        '/Fatal error\:/',
        '/Fatal error happened/i',
    ];

    public function __construct(
        private string $testFrameworkExecutable,
        private InitialConfigBuilder $initialConfigBuilder,
        private MutationConfigBuilder $mutationConfigBuilder,
        private ArgumentsAndOptionsBuilder $argumentsAndOptionsBuilder,
        private VersionProvider $versionProvider,
        private CommandLineBuilder $commandLineBuilder,
    ) {
    }

    public function hasJUnitReport(): bool
    {
        return false;
    }

    public function testsPass(string $output): bool
    {
        $lines = explode(PHP_EOL, $output);

        foreach ($lines as $line) {
            if (preg_match('%not ok \\d+ - %', $line) > 0
                && preg_match('%# TODO%', $line) === 0) {
                return false;
            }
        }

        foreach (self::ERROR_REGEXPS as $regExp) {
            if (preg_match($regExp, $output) > 0) {
                return false;
            }
        }

        return true;
    }

    public function getName(): string
    {
        return 'PhpSpec';
    }

    public function getInitialTestRunCommandLine(
        string $extraOptions,
        array $phpExtraArgs,
        bool $skipCoverage,
    ): array {
        return $this->getCommandLine(
            $this->buildInitialConfigFile(),
            $extraOptions,
            $phpExtraArgs,
        );
    }

    public function getMutantCommandLine(
        array $coverageTests,
        string $mutatedFilePath,
        string $mutationHash,
        string $mutationOriginalFilePath,
        string $extraOptions,
    ): array {
        return $this->getCommandLine(
            $this->buildMutationConfigFile(
                $coverageTests,
                $mutatedFilePath,
                $mutationHash,
                $mutationOriginalFilePath,
            ),
            $extraOptions,
            [],
        );
    }

    public function getVersion(): string
    {
        return $this->versionProvider->get();
    }

    public function getInitialTestsFailRecommendations(string $commandLine): string
    {
        return sprintf(
            'Check the executed command to identify the problem: %s',
            $commandLine,
        );
    }

    /**
     * @throws NoCodeCoverageConfigured
     * @throws UnrecognisableConfiguration
     */
    private function buildInitialConfigFile(): string
    {
        return $this->initialConfigBuilder->build($this->getVersion());
    }

    /**
     * @param TestLocation[] $tests
     *
     * @throws UnrecognisableConfiguration
     */
    private function buildMutationConfigFile(
        array $tests,
        string $mutantFilePath,
        string $mutationHash,
        string $mutationOriginalFilePath,
    ): string {
        return $this->mutationConfigBuilder->build(
            $tests,
            $mutantFilePath,
            $mutationHash,
            $mutationOriginalFilePath,
            $this->getVersion(),
        );
    }

    /**
     * @param string[] $phpExtraArgs
     *
     * @return string[]
     */
    private function getCommandLine(
        string $configPath,
        string $extraOptions,
        array $phpExtraArgs,
    ): array {
        $frameworkArgs = $this->argumentsAndOptionsBuilder->build(
            $configPath,
            $extraOptions,
        );

        return $this->commandLineBuilder->build(
            $this->testFrameworkExecutable,
            $phpExtraArgs,
            $frameworkArgs,
        );
    }
}
