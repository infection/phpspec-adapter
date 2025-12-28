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

use Exception;
use Infection\TestFramework\PhpSpec\Config\PhpSpecConfigurationBuilder;
use function is_a;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(PhpSpecConfigurationBuilder::class)]
final class PhpSpecConfigurationBuilderTest extends TestCase
{
    private const TMP_DIRECTORY = '/path/to/project';

    #[DataProvider('removeCoverageExtensionProvider')]
    public function test_it_can_remove_the_code_coverage_extension(
        string $original,
        string $expected,
    ): void {
        $builder = new PhpSpecConfigurationBuilder(
            self::TMP_DIRECTORY,
            Yaml::parse($original) ?? [],
        );

        $builder->removeCoverageExtension();

        $actual = $builder->getYaml();

        $this->assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function removeCoverageExtensionProvider(): iterable
    {
        yield 'unknown code coverage extension with configuration' => [
            <<<'YAML'
                suites: ~
                extensions:
                    Acme\Extension\FirstExampleExtension:
                        key1: value1
                    Acme\Extension\CodeCoverageExtension:
                        key2: value2
                    Acme\Extension\SecondExampleExtension:
                        key3: value3

                YAML,
            <<<'YAML'
                suites: null
                extensions:
                    Acme\Extension\FirstExampleExtension: { key1: value1 }
                    Acme\Extension\SecondExampleExtension: { key3: value3 }

                YAML,
        ];

        yield 'henrikbjorn/phpspec-code-coverage v3' => [
            <<<'YAML'
                suites: ~
                extensions:
                    PhpSpecCodeCoverage\CodeCoverageExtension: ~

                YAML,
            <<<'YAML'
                suites: null
                extensions: {  }

                YAML,
        ];

        yield 'friends-of-phpspec/phpspec-code-coverage v4' => [
            <<<'YAML'
                suites: ~
                extensions:
                    LeanPHP\PhpSpec\CodeCoverage\CodeCoverageExtension: ~

                YAML,
            <<<'YAML'
                suites: null
                extensions: {  }

                YAML,
        ];

        yield 'friends-of-phpspec/phpspec-code-coverage v4 with configuration' => [
            <<<'YAML'
                suites: ~
                extensions:
                    LeanPHP\PhpSpec\CodeCoverage\CodeCoverageExtension:
                        format:
                            - xml
                        output:
                            xml: var/phpspec-coverage

                YAML,
            <<<'YAML'
                suites: null
                extensions: {  }

                YAML,
        ];

        yield 'friends-of-phpspec/phpspec-code-coverage v5+' => [
            <<<'YAML'
                suites: ~
                extensions:
                    FriendsOfPhpSpec\PhpSpec\CodeCoverage\CodeCoverageExtension: ~

                YAML,
            <<<'YAML'
                suites: null
                extensions: {  }

                YAML,
        ];

        yield 'friends-of-phpspec/phpspec-code-coverage v5+ with configuration' => [
            <<<'YAML'
                suites: ~
                extensions:
                    FriendsOfPhpSpec\PhpSpec\CodeCoverage\CodeCoverageExtension:
                        format:
                            - xml
                        output:
                            xml: var/phpspec-coverage

                YAML,
            <<<'YAML'
                suites: null
                extensions: {  }

                YAML,
        ];

        yield 'nominal' => [
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
            <<<'YAML'
                suites:
                    default: { namespace: Infection\PhpSpecAdapter\E2ETests\PhpSpec, psr4_prefix: Infection\PhpSpecAdapter\E2ETests\PhpSpec }
                extensions: {  }

                YAML,
        ];
    }

    /**
     * @param string|class-string<Exception> $expected
     */
    #[DataProvider('updateCodeCoveragePathProvider')]
    public function test_it_can_configure_the_code_coverage_extension_for_infection(
        string $original,
        string $expected,
    ): void {
        $builder = new PhpSpecConfigurationBuilder(
            self::TMP_DIRECTORY,
            Yaml::parse($original) ?? [],
        );

        $expectsException = is_a($expected, Exception::class, true);

        if ($expectsException) {
            $this->expectException($expected);
        }

        $builder->configureXmlCoverageReportIfNecessary();

        if (!$expectsException) {
            $actual = $builder->getYaml();

            $this->assertSame($expected, $actual);
        }
    }

    /**
     * @return iterable<array{string, string|class-string<Exception>}>
     */
    public static function updateCodeCoveragePathProvider(): iterable
    {
        yield 'unknown code coverage extension with configuration' => [
            <<<'YAML'
                suites: ~
                extensions:
                    Acme\Extension\FirstExampleExtension:
                        key1: value1
                    Acme\Extension\CodeCoverageExtension:
                        key2: value2
                    Acme\Extension\SecondExampleExtension:
                        key3: value3

                YAML,
            <<<'YAML'
                suites: null
                extensions:
                    Acme\Extension\FirstExampleExtension: { key1: value1 }
                    Acme\Extension\CodeCoverageExtension: { key2: value2, format: [xml], output: { xml: /path/to/project/phpspec-coverage-xml } }
                    Acme\Extension\SecondExampleExtension: { key3: value3 }

                YAML,
        ];

        yield 'henrikbjorn/phpspec-code-coverage v3' => [
            <<<'YAML'
                suites: ~
                extensions:
                    PhpSpecCodeCoverage\CodeCoverageExtension: ~

                YAML,
            <<<'YAML'
                suites: null
                extensions:
                    PhpSpecCodeCoverage\CodeCoverageExtension: { format: [xml], output: { xml: /path/to/project/phpspec-coverage-xml } }

                YAML,
        ];

        yield 'friends-of-phpspec/phpspec-code-coverage v4' => [
            <<<'YAML'
                suites: ~
                extensions:
                    LeanPHP\PhpSpec\CodeCoverage\CodeCoverageExtension: ~

                YAML,
            <<<'YAML'
                suites: null
                extensions:
                    LeanPHP\PhpSpec\CodeCoverage\CodeCoverageExtension: { format: [xml], output: { xml: /path/to/project/phpspec-coverage-xml } }

                YAML,
        ];

        yield 'friends-of-phpspec/phpspec-code-coverage v4 with configuration' => [
            <<<'YAML'
                suites: ~
                extensions:
                    LeanPHP\PhpSpec\CodeCoverage\CodeCoverageExtension:
                        format:
                            - xml
                        output:
                            xml: var/phpspec-coverage

                YAML,
            <<<'YAML'
                suites: null
                extensions:
                    LeanPHP\PhpSpec\CodeCoverage\CodeCoverageExtension: { format: [xml], output: { xml: /path/to/project/phpspec-coverage-xml } }

                YAML,
        ];

        yield 'friends-of-phpspec/phpspec-code-coverage v5+' => [
            <<<'YAML'
                suites: ~
                extensions:
                  FriendsOfPhpSpec\PhpSpec\CodeCoverage\CodeCoverageExtension: ~

                YAML,
            <<<'YAML'
                suites: null
                extensions:
                    FriendsOfPhpSpec\PhpSpec\CodeCoverage\CodeCoverageExtension: { format: [xml], output: { xml: /path/to/project/phpspec-coverage-xml } }

                YAML,
        ];

        yield 'friends-of-phpspec/phpspec-code-coverage v5+ with configuration' => [
            <<<'YAML'
                suites: ~
                extensions:
                    FriendsOfPhpSpec\PhpSpec\CodeCoverage\CodeCoverageExtension:
                        format:
                            - xml
                        output:
                            xml: var/phpspec-coverage

                YAML,
            <<<'YAML'
                suites: null
                extensions:
                    FriendsOfPhpSpec\PhpSpec\CodeCoverage\CodeCoverageExtension: { format: [xml], output: { xml: /path/to/project/phpspec-coverage-xml } }

                YAML,
        ];

        yield 'nominal' => [
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
            <<<'YAML'
                suites:
                    default: { namespace: Infection\PhpSpecAdapter\E2ETests\PhpSpec, psr4_prefix: Infection\PhpSpecAdapter\E2ETests\PhpSpec }
                extensions:
                    FriendsOfPhpSpec\PhpSpec\CodeCoverage\CodeCoverageExtension: { format: [xml], output: { xml: /path/to/project/phpspec-coverage-xml } }

                YAML,
        ];
    }

    /**
     * @param non-empty-string $bootstrap
     */
    #[DataProvider('bootstrapProvider')]
    public function test_it_can_configure_the_bootstrap(
        string $original,
        string $bootstrap,
        string $expected,
    ): void {
        $builder = new PhpSpecConfigurationBuilder(
            self::TMP_DIRECTORY,
            Yaml::parse($original) ?? [],
        );

        $builder->setBootstrap($bootstrap);

        $actual = $builder->getYaml();

        $this->assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, non-empty-string, string}>
     */
    public static function bootstrapProvider(): iterable
    {
        yield 'nothing configured' => [
            <<<'YAML'

                YAML,
            '/path/to/mutation-bootstrap.php',
            <<<'YAML'
                bootstrap: /path/to/mutation-bootstrap.php

                YAML,
        ];

        yield 'nothing configured (explicitly)' => [
            <<<'YAML'
                suites: ~
                extensions: ~

                YAML,
            '/path/to/mutation-bootstrap.php',
            <<<'YAML'
                bootstrap: /path/to/mutation-bootstrap.php
                suites: null
                extensions: null

                YAML,
        ];

        // There can be only one bootstrap, ensuring that the mutation one contains
        // the original bootstrap code is out of the scope of this service.
        yield 'a bootstrap file is already configured' => [
            <<<'YAML'
                bootstrap: '/path/to/project/spec/bootstrap.php'
                suites: ~
                extensions: ~

                YAML,
            '/path/to/mutation-bootstrap.php',
            <<<'YAML'
                bootstrap: /path/to/mutation-bootstrap.php
                suites: null
                extensions: null

                YAML,
        ];

        yield 'a bootstrap file is already configured with a different order' => [
            <<<'YAML'
                suites: ~
                extensions: ~
                bootstrap: '/path/to/project/spec/bootstrap.php'

                YAML,
            '/path/to/mutation-bootstrap.php',
            <<<'YAML'
                bootstrap: /path/to/mutation-bootstrap.php
                suites: null
                extensions: null

                YAML,
        ];
    }
}
