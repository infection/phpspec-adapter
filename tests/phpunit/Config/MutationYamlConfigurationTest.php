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

namespace Infection\Tests\TestFramework\PhpSpec\Config;

use function count;
use Infection\TestFramework\PhpSpec\Config\MutationYamlConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class MutationYamlConfigurationTest extends TestCase
{
    private string $tempDir = '/path/to/tmp';

    private string $customAutoloadFilePath = '/custom/path';

    /**
     * @var array<string, mixed>
     */
    private array $defaultConfig = [
        'extensions' => [
            'FirstExtension' => [],
            'PhpSpecCodeCoverageExtension' => [
                'format' => ['xml', 'text'],
                'output' => [
                    'xml' => '/path',
                ],
            ],
            'SomeOtherExtension' => ['option' => 123],
        ],
        'bootstrap' => '/path/to/adc',
        'verbose' => true,
    ];

    public function test_it_removes_code_coverage_extension(): void
    {
        $configuration = $this->getConfigurationObject();

        $parsedYaml = Yaml::parse($configuration->getYaml());

        $expectedConfig = [
            'bootstrap' => '/custom/path',
            'extensions' => [
                'FirstExtension' => [],
                'SomeOtherExtension' => ['option' => 123],
            ],
            'verbose' => true,
        ];

        $this->assertSame($expectedConfig, $parsedYaml);
    }

    public function test_it_returns_same_extensions_when_no_coverage_extension_found(): void
    {
        $originalParsedYaml = ['bootstrap' => '/path/to/adc', 'extensions' => []];
        $configuration = $this->getConfigurationObject($originalParsedYaml);

        $parsedYaml = Yaml::parse($configuration->getYaml());

        $this->assertCount(0, $parsedYaml['extensions']);
    }

    public function test_it_sets_custom_autoloader_path(): void
    {
        $configuration = $this->getConfigurationObject();

        $parsedYaml = Yaml::parse($configuration->getYaml());

        $this->assertSame($this->customAutoloadFilePath, $parsedYaml['bootstrap']);
    }

    /**
     * @param array<string, mixed> $configArray
     */
    private function getConfigurationObject(array $configArray = []): MutationYamlConfiguration
    {
        return new MutationYamlConfiguration(
            $this->tempDir,
            count($configArray) > 0 ? $configArray : $this->defaultConfig,
            $this->customAutoloadFilePath
        );
    }
}
