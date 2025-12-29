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

use Infection\TestFramework\PhpSpec\Config\Builder\InitialConfigBuilder;
use Infection\TestFramework\PhpSpec\Throwable\UnrecognisableConfiguration;
use Infection\Tests\TestFramework\PhpSpec\FileSystem\FileSystemTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use function Safe\file_get_contents;

#[Group('integration')]
#[CoversClass(InitialConfigBuilder::class)]
final class InitialConfigBuilderTest extends FileSystemTestCase
{
    public function test_it_builds_path_to_initial_config_file(): void
    {
        $originalPhpSpecConfigContents = file_get_contents(__DIR__ . '/../../../Fixtures/Files/phpspec/phpspec.yml');

        $builder = new InitialConfigBuilder($this->tmp, $originalPhpSpecConfigContents, false);

        $actualPath = $builder->build('2.0');

        $this->assertFileExists($actualPath);
        $this->assertSame($this->tmp . '/phpspecConfiguration.initial.infection.yml', $actualPath);
    }

    public function test_it_provides_a_friendly_error_if_the_configuration_is_invalud(): void
    {
        $originalPhpSpecConfigContents = <<<'YAML'
            suites: ~
            extensions:
                - Acme\Extension\FirstExampleExtension
                - Acme\Extension\CodeCoverageExtension
                - Acme\Extension\SecondExampleExtension

            YAML;

        $builder = new InitialConfigBuilder(
            $this->tmp,
            $originalPhpSpecConfigContents,
            false,
        );

        $this->expectExceptionObject(
            new UnrecognisableConfiguration(
                'Could not recognise the current configuration format for the version "2.0": The "extensions" configuration key must be null or an associative array.',
            ),
        );

        $builder->build('2.0');
    }
}
