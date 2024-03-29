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

namespace Infection\TestFramework\PhpSpec\Config\Builder;

use function array_key_exists;
use function assert;
use function file_put_contents;
use Infection\AbstractTestFramework\Coverage\TestLocation;
use Infection\StreamWrapper\IncludeInterceptor;
use Infection\TestFramework\PhpSpec\Config\MutationYamlConfiguration;
use function is_string;
use Phar;
use function sprintf;
use function str_replace;
use function strpos;
use function strstr;
use Symfony\Component\Yaml\Yaml;

/**
 * @internal
 */
class MutationConfigBuilder
{
    private string $tempDirectory;
    private string $originalYamlConfigPath;
    private string $projectDir;

    public function __construct(string $tempDirectory, string $originalYamlConfigPath, string $projectDir)
    {
        $this->tempDirectory = $tempDirectory;
        $this->originalYamlConfigPath = $originalYamlConfigPath;
        $this->projectDir = $projectDir;
    }

    /**
     * @param TestLocation[] $tests
     */
    public function build(
        array $tests,
        string $mutantFilePath,
        string $mutationHash,
        string $mutationOriginalFilePath
    ): string {
        $customAutoloadFilePath = sprintf(
            '%s/interceptor.phpspec.autoload.%s.infection.php',
            $this->tempDirectory,
            $mutationHash
        );

        $parsedYaml = Yaml::parseFile($this->originalYamlConfigPath);

        file_put_contents($customAutoloadFilePath, $this->createCustomAutoloadWithInterceptor($mutationOriginalFilePath, $mutantFilePath, $parsedYaml));

        $yamlConfiguration = new MutationYamlConfiguration(
            $this->tempDirectory,
            $parsedYaml,
            $customAutoloadFilePath
        );

        $newYaml = $yamlConfiguration->getYaml();

        $path = $this->buildPath($mutationHash);

        file_put_contents($path, $newYaml);

        return $path;
    }

    /**
     * @param array<string, mixed> $parsedYaml
     */
    private function createCustomAutoloadWithInterceptor(string $originalFilePath, string $mutantFilePath, array $parsedYaml): string
    {
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
            $this->getInterceptorFileContent($interceptorPath, $originalFilePath, $mutantFilePath)
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

    private function getInterceptorFileContent(string $interceptorPath, string $originalFilePath, string $mutantFilePath): string
    {
        $infectionPhar = '';

        if (strpos(__FILE__, 'phar:') === 0) {
            $infectionPhar = sprintf(
                '\Phar::loadPhar("%s", "%s");',
                str_replace('phar://', '', Phar::running(true)),
                'infection.phar'
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
