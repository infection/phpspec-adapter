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

namespace Infection\TestFramework\PhpSpec\Config;

use Infection\TestFramework\PhpSpec\Throwable\NoCodeCoverageConfigured;
use Infection\TestFramework\PhpSpec\Throwable\UnrecognisableConfiguration;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @phpstan-import-type DecodedPhpSpecConfig from PhpSpecConfigurationBuilder
 *
 * @internal
 * @final
 */
readonly class InitialConfigBuilder
{
    /**
     * @param DecodedPhpSpecConfig $originalPhpSpecConfigDecodedContents
     */
    public function __construct(
        private string $tmpDirectory,
        private string $coverageDirectoryPath,
        private array $originalPhpSpecConfigDecodedContents,
        private bool $skipCoverage,
        private Filesystem $filesystem,
    ) {
    }

    /**
     * @throws NoCodeCoverageConfigured
     * @throws UnrecognisableConfiguration
     */
    public function build(string $version): string
    {
        $path = $this->buildPath();

        try {
            $configuration = PhpSpecConfigurationBuilder::create(
                $this->coverageDirectoryPath,
                $this->originalPhpSpecConfigDecodedContents,
            );
        } catch (UnrecognisableConfiguration $exception) {
            throw $exception->enrichWithVersion($version);
        }

        if ($this->skipCoverage) {
            $configuration->removeCoverageExtension();
        } else {
            $configuration->configureXmlCoverageReportIfNecessary();
        }

        $this->filesystem->dumpFile($path, $configuration->getYaml());

        return $path;
    }

    private function buildPath(): string
    {
        return $this->tmpDirectory . '/phpspecConfiguration.initial.infection.yml';
    }
}
