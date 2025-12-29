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

namespace Infection\Tests\TestFramework\PhpSpec\Version;

use PHPUnit\Framework\Attributes\RequiresPhp;
use function file_exists;
use Infection\TestFramework\PhpSpec\CommandLineBuilder;
use Infection\TestFramework\PhpSpec\Version\ProcessVersionProvider;
use Infection\TestFramework\PhpSpec\Version\VersionParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use function Safe\file_get_contents;

#[Group('integration')]
#[CoversClass(ProcessVersionProvider::class)]
final class ProcessVersionProviderTest extends TestCase
{
    // This is prepared and downloaded by the Makefile.
    private const PHPSPEC_PHAR = __DIR__ . '/../../../var/tools/phpspec.phar';

    private const PHPSPEC_VERSION = __DIR__ . '/../../../.tools/phpspec-version';

    #[RequiresPhp('<8.5')]
    public function test_it_can_get_the_version(): void
    {
        $this->ensurePharExists();

        $expected = file_get_contents(self::PHPSPEC_VERSION);

        $provider = new ProcessVersionProvider(
            self::PHPSPEC_PHAR,
            new CommandLineBuilder(),
            new VersionParser(),
        );

        $actual = $provider->get();

        $this->assertSame($expected, $actual);
    }

    private function ensurePharExists(): void
    {
        if (!file_exists(self::PHPSPEC_PHAR)) {
            $this->markTestSkipped(
                'The PhpSpec PHAR could not be found. It will be automatically downloaded when executing `make test-unit`.',
            );
        }
    }
}
