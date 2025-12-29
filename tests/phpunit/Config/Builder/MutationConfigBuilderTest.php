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

namespace Infection\Tests\TestFramework\PhpSpec\Config\Builder;

use Infection\TestFramework\PhpSpec\Config\Builder\MutationConfigBuilder;
use Infection\TestFramework\PhpSpec\Throwable\UnrecognisableConfiguration;
use Infection\Tests\TestFramework\PhpSpec\FileSystem\FileSystemTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use function Safe\file_get_contents;
use Symfony\Component\Yaml\Yaml;

#[Group('integration')]
#[CoversClass(MutationConfigBuilder::class)]
final class MutationConfigBuilderTest extends FileSystemTestCase
{
    private const MUTATION_HASH = 'a1b2c3';

    private const ORIGINAL_FILE_PATH = '/original/file/path';

    private const MUTATED_FILE_PATH = '/mutated/file/path';

    public function test_it_builds_path_to_mutation_config_file(): void
    {
        $projectDir = '/project/dir';
        $originalPhpSpecConfigDecodedContents = Yaml::parseFile(__DIR__ . '/../../../Fixtures/Files/phpspec/phpspec.yml');

        $builder = new MutationConfigBuilder(
            $this->tmp,
            $originalPhpSpecConfigDecodedContents,
            $projectDir,
        );

        $actualPath = $builder->build(
            [],
            self::MUTATED_FILE_PATH,
            self::MUTATION_HASH,
            self::ORIGINAL_FILE_PATH,
            '2.0',
        );

        $this->assertFileExists($actualPath);
        $this->assertSame($this->tmp . '/phpspecConfiguration.a1b2c3.infection.yml', $actualPath);
    }

    public function test_it_adds_original_bootstrap_file_to_custom_autoload(): void
    {
        $projectDir = '/project/dir';
        $originalPhpSpecConfigDecodedContents = Yaml::parseFile(__DIR__ . '/../../../Fixtures/Files/phpspec/phpspec.with.bootstrap.yml');

        $builder = new MutationConfigBuilder(
            $this->tmp,
            $originalPhpSpecConfigDecodedContents,
            $projectDir,
        );

        $this->assertSame(
            $this->tmp . '/phpspecConfiguration.a1b2c3.infection.yml',
            $builder->build(
                [],
                self::MUTATED_FILE_PATH,
                self::MUTATION_HASH,
                self::ORIGINAL_FILE_PATH,
                '2.0',
            ),
        );

        $actualContent = file_get_contents($this->tmp . '/interceptor.phpspec.autoload.a1b2c3.infection.php');

        $this->assertStringContainsString("require_once '/project/dir/bootstrap.php';", $actualContent);
        $this->assertStringNotContainsString('\Phar::loadPhar("%s", "%s");', $actualContent);
    }

    public function test_interceptor_is_included(): void
    {
        $projectDir = '/project/dir';
        $originalPhpSpecConfigDecodedContents = Yaml::parseFile(__DIR__ . '/../../../Fixtures/Files/phpspec/phpspec.yml');

        $builder = new MutationConfigBuilder(
            $this->tmp,
            $originalPhpSpecConfigDecodedContents,
            $projectDir,
        );

        $this->assertSame(
            $this->tmp . '/phpspecConfiguration.a1b2c3.infection.yml',
            $builder->build(
                [],
                self::MUTATED_FILE_PATH,
                self::MUTATION_HASH,
                self::ORIGINAL_FILE_PATH,
                '2.0',
            ),
        );

        $this->assertFileExists($this->tmp . '/interceptor.phpspec.autoload.a1b2c3.infection.php');
        $content = file_get_contents($this->tmp . '/interceptor.phpspec.autoload.a1b2c3.infection.php');

        $this->assertStringContainsString('IncludeInterceptor.php', $content);
    }

    public function test_it_provides_a_friendly_error_if_the_configuration_is_invalud(): void
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

        $builder = new MutationConfigBuilder(
            $this->tmp,
            $originalPhpSpecConfigDecodedContents,
            '/path/to/project',
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
