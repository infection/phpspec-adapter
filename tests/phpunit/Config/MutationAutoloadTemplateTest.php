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

use Infection\StreamWrapper\IncludeInterceptor;
use Infection\TestFramework\PhpSpec\Config\MutationAutoloadTemplate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(MutationAutoloadTemplate::class)]
final class MutationAutoloadTemplateTest extends TestCase
{
    private const ORIGINAL_FILE_PATH = '/original/file/path';

    private const MUTATED_FILE_PATH = '/mutated/file/path';

    /**
     * @param array<string, mixed> $phpSpecConfig
     */
    #[DataProvider('autoloadTemplateProvider')]
    public function test_it_generates_autoload_template(
        string $projectDirectory,
        array $phpSpecConfig,
        string $expected,
    ): void {
        $template = MutationAutoloadTemplate::create(
            $projectDirectory,
            $phpSpecConfig,
        );

        $actual = $template->build(
            self::ORIGINAL_FILE_PATH,
            self::MUTATED_FILE_PATH,
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>, string}>
     */
    public static function autoloadTemplateProvider(): iterable
    {
        $projectDir = '/project/dir';
        $interceptorPath = IncludeInterceptor::LOCATION;

        yield 'with bootstrap file' => [
            $projectDir,
            Yaml::parse(
                <<<'YAML'
                    bootstrap: bootstrap.php
                    extensions:
                        CodeCoverageExtension: ~
                        TestExtension:
                            options: 123
                    YAML,
            ),
            <<<PHP
                <?php

                require_once '/project/dir/bootstrap.php';

                require_once '{$interceptorPath}';

                use Infection\StreamWrapper\IncludeInterceptor;

                IncludeInterceptor::intercept('/original/file/path', '/mutated/file/path');
                IncludeInterceptor::enable();

                PHP,
        ];

        yield 'without bootstrap file' => [
            $projectDir,
            Yaml::parse(
                <<<'YAML'
                    extensions:
                        CodeCoverageExtension: ~
                        TestExtension:
                            options: 123
                    YAML,
            ),
            <<<PHP
                <?php



                require_once '{$interceptorPath}';

                use Infection\StreamWrapper\IncludeInterceptor;

                IncludeInterceptor::intercept('/original/file/path', '/mutated/file/path');
                IncludeInterceptor::enable();

                PHP,
        ];

        yield 'with inline config without bootstrap' => [
            $projectDir,
            Yaml::parse(
                <<<'YAML'
                    suites:
                        default:
                            namespace: Infection\PhpSpecAdapter\E2ETests\PhpSpec
                            psr4_prefix: Infection\PhpSpecAdapter\E2ETests\PhpSpec
                    YAML,
            ),
            <<<PHP
                <?php



                require_once '{$interceptorPath}';

                use Infection\StreamWrapper\IncludeInterceptor;

                IncludeInterceptor::intercept('/original/file/path', '/mutated/file/path');
                IncludeInterceptor::enable();

                PHP,
        ];
    }
}
