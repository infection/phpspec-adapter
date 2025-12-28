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

use function array_key_exists;
use Infection\TestFramework\PhpSpec\PhpSpecAdapter;
use function str_contains;
use Symfony\Component\Yaml\Yaml;

/**
 * @internal
 */
final class PhpSpecConfigurationBuilder
{
    /**
     * @param array<string, mixed> $parsedYaml
     */
    public function __construct(
        private readonly string $tmpDirectory,
        private array $parsedYaml,
    ) {
    }

    public function removeCoverageExtension(): void
    {
        foreach (($this->parsedYaml['extensions'] ?? []) as $extensionName => $options) {
            if (self::isCodeCoverageExtension($extensionName)) {
                unset($this->parsedYaml['extensions'][$extensionName]);
            }
        }
    }

    /**
     * @throws NoCodeCoverageException
     */
    public function configureXmlCoverageReportIfNecessary(): void
    {
        $this->assertHasCoverageExtension();

        foreach ($this->parsedYaml['extensions'] as $extensionName => &$options) {
            if (!self::isCodeCoverageExtension($extensionName)) {
                continue;
            }

            $options['format'] = ['xml'];
            $options['output'] = [
                'xml' => $this->tmpDirectory . '/' . PhpSpecAdapter::COVERAGE_DIR,
            ];
        }
        unset($options);
    }

    /**
     * @param non-empty-string $mutantAutoloadPathname
     */
    public function setBootstrap(string $mutantAutoloadPathname): void
    {
        // bootstrap must be before other keys because of PhpSpec bug with populating container under
        // some circumstances
        $this->parsedYaml = ['bootstrap' => $mutantAutoloadPathname] + $this->parsedYaml;
    }

    public function getYaml(): string
    {
        return Yaml::dump($this->parsedYaml);
    }

    /**
     * @throws NoCodeCoverageException
     */
    private function assertHasCoverageExtension(): void
    {
        if (!$this->hasCodeCoverageExtension()) {
            throw NoCodeCoverageException::fromTestFramework('PhpSpec');
        }
    }

    private static function isCodeCoverageExtension(string $extensionName): bool
    {
        return str_contains($extensionName, 'CodeCoverage');
    }

    private function hasCodeCoverageExtension(): bool
    {
        if (!array_key_exists('extensions', $this->parsedYaml)) {
            return false;
        }

        foreach (($this->parsedYaml['extensions'] ?? []) as $extensionName => $options) {
            if (self::isCodeCoverageExtension($extensionName)) {
                return true;
            }
        }

        return false;
    }
}
