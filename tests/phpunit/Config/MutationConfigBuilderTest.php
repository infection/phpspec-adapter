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

use Infection\TestFramework\PhpSpec\Config\MutationConfigBuilder;
use Infection\TestFramework\PhpSpec\Throwable\UnrecognisableConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

#[Group('integration')]
#[CoversClass(MutationConfigBuilder::class)]
final class MutationConfigBuilderTest extends TestCase
{
    private const MUTATION_HASH = 'a1b2c3';

    private const ORIGINAL_FILE_PATH = '/original/file/path';

    private const MUTATED_FILE_PATH = '/mutated/file/path';

    public function test_it_builds_path_to_mutation_config_file(): void
    {
        $projectDir = '/project/dir';
        $originalPhpSpecConfigDecodedContents = Yaml::parse(
            <<<'YAML'
                suites:
                    default:
                        namespace: Infection\PhpSpecAdapter\E2ETests\PhpSpec
                        psr4_prefix: Infection\PhpSpecAdapter\E2ETests\PhpSpec

                extensions:
                    FriendsOfPhpSpec\PhpSpec\CodeCoverage\CodeCoverageExtension:
                        format:
                            - xml
                        output:
                            xml: var/phpspec-coverage

                YAML,
        );

        $expectedMutationConfig = <<<'YAML'
            bootstrap: /path/to/tmp/interceptor.phpspec.autoload.a1b2c3.infection.php
            suites:
                default: { namespace: Infection\PhpSpecAdapter\E2ETests\PhpSpec, psr4_prefix: Infection\PhpSpecAdapter\E2ETests\PhpSpec }
            extensions: {  }

            YAML;

        $expectedInterceptorPath = '/path/to/tmp/interceptor.phpspec.autoload.a1b2c3.infection.php';
        $expectedMutationConfigPath = '/path/to/tmp/phpspecConfiguration.a1b2c3.infection.yml';

        $dumpedFiles = [];

        $fileSystemMock = $this->createMock(Filesystem::class);
        $fileSystemMock
            ->expects($this->exactly(2))
            ->method('dumpFile')
            ->with(
                $this->logicalOr(
                    $this->equalTo($expectedInterceptorPath),
                    $this->equalTo($expectedMutationConfigPath),
                ),
                $this->callback(
                    static function (string $contents) use (&$dumpedFiles) {
                        $dumpedFiles[] = $contents;

                        return true;
                    },
                ),
            );

        $builder = new MutationConfigBuilder(
            '/path/to/tmp',
            $originalPhpSpecConfigDecodedContents,
            $projectDir,
            $fileSystemMock,
        );

        $actualPath = $builder->build(
            [],
            self::MUTATED_FILE_PATH,
            self::MUTATION_HASH,
            self::ORIGINAL_FILE_PATH,
            '2.0',
        );
        // This is because we first dump the interceptor.
        $actualMutationConfig = $dumpedFiles[1];

        $this->assertSame($expectedMutationConfigPath, $actualPath);
        $this->assertSame($expectedMutationConfig, $actualMutationConfig);
    }

    public function test_it_adds_original_bootstrap_file_to_custom_autoload(): void
    {
        $projectDir = '/project/dir';
        $originalPhpSpecConfigDecodedContents = Yaml::parseFile(__DIR__ . '/../../Fixtures/Files/phpspec/phpspec.with.bootstrap.yml');

        $dumpedFiles = [];

        $fileSystemMock = $this->createMock(Filesystem::class);
        $fileSystemMock
            ->expects($this->exactly(2))
            ->method('dumpFile')
            ->with(
                $this->anything(),
                $this->callback(
                    static function (string $contents) use (&$dumpedFiles) {
                        $dumpedFiles[] = $contents;

                        return true;
                    },
                ),
            );

        $builder = new MutationConfigBuilder(
            '/path/to/tmp',
            $originalPhpSpecConfigDecodedContents,
            $projectDir,
            $fileSystemMock,
        );

        $builder->build(
            [],
            self::MUTATED_FILE_PATH,
            self::MUTATION_HASH,
            self::ORIGINAL_FILE_PATH,
            '2.0',
        );

        // The interceptor is dumped first
        $interceptorContent = $dumpedFiles[0];

        $this->assertStringContainsString("require_once '/project/dir/bootstrap.php';", $interceptorContent);
        $this->assertStringNotContainsString('\Phar::loadPhar("%s", "%s");', $interceptorContent);
    }

    public function test_interceptor_is_included(): void
    {
        $projectDir = '/project/dir';
        $originalPhpSpecConfigDecodedContents = Yaml::parseFile(__DIR__ . '/../../Fixtures/Files/phpspec/phpspec.yml');

        $dumpedFiles = [];

        $fileSystemMock = $this->createMock(Filesystem::class);
        $fileSystemMock
            ->expects($this->exactly(2))
            ->method('dumpFile')
            ->with(
                $this->anything(),
                $this->callback(
                    static function (string $contents) use (&$dumpedFiles) {
                        $dumpedFiles[] = $contents;

                        return true;
                    },
                ),
            );

        $builder = new MutationConfigBuilder(
            '/path/to/tmp',
            $originalPhpSpecConfigDecodedContents,
            $projectDir,
            $fileSystemMock,
        );

        $builder->build(
            [],
            self::MUTATED_FILE_PATH,
            self::MUTATION_HASH,
            self::ORIGINAL_FILE_PATH,
            '2.0',
        );

        // The interceptor is dumped first
        $interceptorContent = $dumpedFiles[0];

        $this->assertStringContainsString('IncludeInterceptor.php', $interceptorContent);
    }

    public function test_it_provides_a_friendly_error_if_the_configuration_is_invalid(): void
    {
        $originalPhpSpecConfigDecodedContents = Yaml::parse(
            <<<'YAML'
                suites: ~
                extensions:
                    - Acme\Extension\FirstExampleExtension
                    - Acme\Extension\CodeCoverageExtension
                    - Acme\Extension\SecondExampleExtension

                YAML,
        );

        $fileSystemMock = $this->createMock(Filesystem::class);
        $fileSystemMock
            ->expects($this->exactly(1))
            ->method('dumpFile')
            ->withAnyParameters();

        $builder = new MutationConfigBuilder(
            '/path/to/tmp',
            $originalPhpSpecConfigDecodedContents,
            '/path/to/project',
            $fileSystemMock,
        );

        $this->expectExceptionObject(
            new UnrecognisableConfiguration(
                'Could not recognise the current configuration format for the version "2.0": The "extensions" configuration key must be null or an associative array.',
            ),
        );

        $builder->build(
            [],
            self::MUTATED_FILE_PATH,
            self::MUTATION_HASH,
            self::ORIGINAL_FILE_PATH,
            '2.0',
        );
    }
}
