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
use function assert;
use Infection\AbstractTestFramework\Coverage\TestLocation;
use Infection\StreamWrapper\IncludeInterceptor;
use Infection\TestFramework\PhpSpec\Throwable\UnrecognisableConfiguration;
use function is_string;
use Phar;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strstr;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @phpstan-import-type DecodedPhpSpecConfig from PhpSpecConfigurationBuilder
 *
 * @internal
 * @final
 */
readonly class MutationConfigBuilder
{
    /**
     * @param DecodedPhpSpecConfig $originalPhpSpecConfigDecodedContents
     */
    public function __construct(
        private string $tempDirectory,
        private array $originalPhpSpecConfigDecodedContents,
        private string $projectDir,
        private Filesystem $filesystem,
    ) {
    }

    /**
     * @param TestLocation[] $tests
     *
     * @throws UnrecognisableConfiguration
     */
    public function build(
        array $tests,
        string $mutantFilePath,
        string $mutationHash,
        string $mutationOriginalFilePath,
        string $version,
    ): string {
        $customAutoloadFilePath = sprintf(
            '%s/interceptor.phpspec.autoload.%s.infection.php',
            $this->tempDirectory,
            $mutationHash,
        );

        $this->filesystem->dumpFile(
            $customAutoloadFilePath,
            $this->createCustomAutoloadWithInterceptor(
                $mutationOriginalFilePath,
                $mutantFilePath,
                $this->originalPhpSpecConfigDecodedContents,
            ),
        );

        try {
            $configuration = PhpSpecConfigurationBuilder::create(
                $this->tempDirectory,
                $this->originalPhpSpecConfigDecodedContents,
            );
        } catch (UnrecognisableConfiguration $exception) {
            throw $exception->enrichWithVersion($version);
        }

        $configuration->setBootstrap($customAutoloadFilePath);
        $configuration->removeCoverageExtension();

        $newYaml = $configuration->getYaml();

        $path = $this->buildPath($mutationHash);

        $this->filesystem->dumpFile($path, $newYaml);

        return $path;
    }

    /**
     * @param array<string, mixed> $parsedYaml
     */
    private function createCustomAutoloadWithInterceptor(
        string $originalFilePath,
        string $mutantFilePath,
        array $parsedYaml,
    ): string {
        $originalBootstrap = $this->getOriginalBootstrapFilePath($parsedYaml);
        $autoloadPlaceholder = $originalBootstrap !== null ? "require_once '{$originalBootstrap}';" : '';
        /** @var string $interceptorPath */
        $interceptorPath = IncludeInterceptor::LOCATION;

        $customAutoload = <<<AUTOLOAD
            <?php

            %s
            %s

            AUTOLOAD;

        return sprintf(
            $customAutoload,
            $autoloadPlaceholder,
            $this->getInterceptorFileContent(
                $interceptorPath,
                $originalFilePath,
                $mutantFilePath,
            ),
        );
    }

    private function buildPath(string $mutationHash): string
    {
        $fileName = sprintf('phpspecConfiguration.%s.infection.yml', $mutationHash);

        return $this->tempDirectory . '/' . $fileName;
    }

    /**
     * @param array<string, mixed> $parsedYaml
     */
    private function getOriginalBootstrapFilePath(array $parsedYaml): ?string
    {
        if (!array_key_exists('bootstrap', $parsedYaml)) {
            return null;
        }

        return sprintf('%s/%s', $this->projectDir, $parsedYaml['bootstrap']);
    }

    private function getInterceptorFileContent(
        string $interceptorPath,
        string $originalFilePath,
        string $mutantFilePath,
    ): string {
        $infectionPhar = '';

        if (str_starts_with(__FILE__, 'phar:')) {
            $infectionPhar = sprintf(
                '\Phar::loadPhar("%s", "%s");',
                str_replace(
                    'phar://',
                    '',
                    Phar::running(true),
                ),
                'infection.phar',
            );
        }

        $namespacePrefix = $this->getInterceptorNamespacePrefix();

        return <<<CONTENT
            {$infectionPhar}
            require_once '{$interceptorPath}';

            use {$namespacePrefix}Infection\StreamWrapper\IncludeInterceptor;

            IncludeInterceptor::intercept('{$originalFilePath}', '{$mutantFilePath}');
            IncludeInterceptor::enable();
            CONTENT;
    }

    private function getInterceptorNamespacePrefix(): string
    {
        $prefix = strstr(__NAMESPACE__, 'Infection', true);
        assert(is_string($prefix));

        return $prefix;
    }
}
