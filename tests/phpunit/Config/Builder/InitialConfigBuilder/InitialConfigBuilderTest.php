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

namespace Infection\Tests\TestFramework\PhpSpec\Config\Builder\InitialConfigBuilder;

use Infection\TestFramework\PhpSpec\Config\Builder\InitialConfigBuilder;
use Infection\TestFramework\PhpSpec\Config\InitialYamlConfiguration;
use Infection\Tests\TestFramework\PhpSpec\FileSystem\FileSystemTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(InitialConfigBuilder::class)]
#[CoversClass(InitialYamlConfiguration::class)]
final class InitialConfigBuilderTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__.'/Fixtures';

    #[DataProvider('originalConfigProvider')]
    public function test_it_builds_path_to_initial_config_file(
        string $originalYamlConfigPath,
        bool $skipCoverage,
        string $version,
        string $expectedContents,
    ): void
    {
        $fileSystemMock = $this->createMock(Filesystem::class);

        $expectedPath = '/path/to/tmp/phpspecConfiguration.initial.infection.yml';

        $fileSystemMock
            ->expects($this->once())
            ->method('dumpFile')
            ->with($expectedPath, $expectedContents);

        $builder = new InitialConfigBuilder(
            $fileSystemMock,
            '/path/to/tmp',
            $originalYamlConfigPath,
            $skipCoverage,
        );

        $actualPath = $builder->build($version);

        $this->assertSame($expectedPath, $actualPath);
    }

    public static function originalConfigProvider(): iterable
    {
        yield 'basic' => [
            self::FIXTURES_DIR.'/phpspec.yml',
            false,
            '2.0',
            <<<'YAML'
            extensions:
                CodeCoverageExtension: { format: [xml], output: { xml: /path/to/tmp/phpspec-coverage-xml } }
                TestExtension: { options: 123 }
            
            YAML,
        ];
    }
}
