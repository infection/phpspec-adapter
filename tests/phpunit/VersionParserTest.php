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

namespace Infection\Tests\TestFramework\PhpSpec;

use Exception;
use Infection\TestFramework\PhpSpec\VersionParser;
use InvalidArgumentException;
use function is_array;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(VersionParser::class)]
final class VersionParserTest extends TestCase
{
    private VersionParser $versionParser;

    protected function setUp(): void
    {
        $this->versionParser = new VersionParser();
    }

    #[DataProvider('versionProvider')]
    public function test_it_parses_version_from_string(
        string $value,
        string|Exception $expected,
    ): void {
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        $result = $this->versionParser->parse($value);

        if (!($expected instanceof Exception)) {
            $this->assertSame($expected, $result);
        }
    }

    public static function versionProvider(): iterable
    {
        yield 'Hoa version' => ['3.17.05.02', '3.17.05'];

        yield 'common RC notation 1' => ['5.0.0-rc1', '5.0.0-rc1'];

        yield 'common RC notation 2' => ['5.0.0-rc.1', '5.0.0-rc.1'];

        foreach (self::validSemanticVersionProvider() as $version) {
            if (is_array($version)) {
                yield $version;
            } else {
                yield [$version, $version];

                yield ['phpspec v' . $version . ' by Marcelo & Ciaran', $version];
            }
        }

        foreach (self::invalidSemanticVersionProvider() as $version) {
            yield is_array($version)
                ? $version
                : [$version, $version];
        }

        foreach (self::phpSpecVersionProvider() as $version) {
            if (is_array($version)) {
                yield $version;
            } else {
                yield [$version, $version];

                yield ['phpspec v' . $version . ' by Marcelo & Ciaran', $version];
            }
        }
    }

    private static function validSemanticVersionProvider(): iterable
    {
        // Source: https://semver.org/#is-there-a-suggested-regular-expression-regex-to-check-a-semver-string
        yield '0.0.4';

        yield '1.2.3';

        yield '10.20.30';

        yield '1.1.2-prerelease+meta';

        yield '1.1.2+meta';

        yield ['1.1.2+meta-valid', '1.1.2+meta'];    // TODO: this is incorrect

        yield '1.0.0-alpha';

        yield '1.0.0-beta';

        yield '1.0.0-alpha.beta';

        yield '1.0.0-alpha.beta.1';

        yield '1.0.0-alpha.1';

        yield '1.0.0-alpha0.valid';

        yield '1.0.0-alpha.0valid';

        yield ['1.0.0-alpha-a.b-c-somethinglong+build.1-aef.1-its-okay', '1.0.0-alpha'];    // TODO: this is incorrect

        yield '1.0.0-rc.1+build.1';

        yield '2.0.0-rc.1+build.123';

        yield '1.2.3-beta';

        yield ['10.2.3-DEV-SNAPSHOT', '10.2.3-DEV'];    // TODO: this is incorrect

        yield ['1.2.3-SNAPSHOT-123', '1.2.3-SNAPSHOT'];    // TODO: this is incorrect

        yield '1.0.0';

        yield '2.0.0';

        yield '1.1.7';

        yield '2.0.0+build.1848';

        yield '2.0.1-alpha.1227';

        yield '1.0.0-alpha+beta';

        yield ['1.2.3----RC-SNAPSHOT.12.9.1--.12+788', '1.2.3'];    // TODO: this is incorrect

        yield ['1.2.3----R-S.12.9.1--.12+meta', '1.2.3'];    // TODO: this is incorrect

        yield ['1.2.3----RC-SNAPSHOT.12.9.1--.12', '1.2.3'];

        yield ['1.0.0+0.build.1-rc.10000aaa-kk-0.1', '1.0.0+0.build.1'];    // TODO: this is incorrect

        yield '99999999999999999999999.999999999999999999.99999999999999999';

        yield '1.0.0-0A.is.legal';
    }

    private static function invalidSemanticVersionProvider(): iterable
    {
        // https://semver.org/#is-there-a-suggested-regular-expression-regex-to-check-a-semver-string
        yield [
            '1',
            new InvalidArgumentException('Parameter does not contain a valid SemVer (sub)string.'),
        ];

        yield '1.2';

        yield '1.2.3-0123';

        yield '1.2.3-0123.0123';

        yield '1.1.2+.123';

        yield [
            '+invalid',
            new InvalidArgumentException('Parameter does not contain a valid SemVer (sub)string.'),
        ];

        yield [
            '-invalid',
            new InvalidArgumentException('Parameter does not contain a valid SemVer (sub)string.'),
        ];

        yield [
            '-invalid+invalid',
            new InvalidArgumentException('Parameter does not contain a valid SemVer (sub)string.'),
        ];

        yield [
            '-invalid.01',
            new InvalidArgumentException('Parameter does not contain a valid SemVer (sub)string.'),
        ];

        yield [
            'alpha',
            new InvalidArgumentException('Parameter does not contain a valid SemVer (sub)string.'),
        ];

        yield [
            'alpha.beta',
            new InvalidArgumentException('Parameter does not contain a valid SemVer (sub)string.'),
        ];

        yield [
            'alpha.beta.1',
            new InvalidArgumentException('Parameter does not contain a valid SemVer (sub)string.'),
        ];

        yield [
            'alpha.1',
            new InvalidArgumentException('Parameter does not contain a valid SemVer (sub)string.'),
        ];

        yield [
            'alpha+beta',
            new InvalidArgumentException('Parameter does not contain a valid SemVer (sub)string.'),
        ];

        yield [
            'alpha_beta',
            new InvalidArgumentException('Parameter does not contain a valid SemVer (sub)string.'),
        ];

        yield [
            'alpha.',
            new InvalidArgumentException('Parameter does not contain a valid SemVer (sub)string.'),
        ];

        yield [
            'alpha..',
            new InvalidArgumentException('Parameter does not contain a valid SemVer (sub)string.'),
        ];

        yield [
            'beta',
            new InvalidArgumentException('Parameter does not contain a valid SemVer (sub)string.'),
        ];

        yield ['1.0.0-alpha_beta', '1.0.0-alpha'];    // TODO: this is incorrect

        yield [
            '-alpha.',
            new InvalidArgumentException('Parameter does not contain a valid SemVer (sub)string.'),
        ];

        yield '1.0.0-alpha..';

        yield '1.0.0-alpha..1';

        yield '1.0.0-alpha...1';

        yield '1.0.0-alpha....1';

        yield '1.0.0-alpha.....1';

        yield '1.0.0-alpha......1';

        yield '1.0.0-alpha.......1';

        yield '01.1.1';

        yield '1.01.1';

        yield '1.1.01';

        yield '1.2';

        yield ['1.2.3.DEV', '1.2.3'];  // TODO: this is incorrect

        yield '1.2-SNAPSHOT';

        yield ['1.2.31.2.3----RC-SNAPSHOT.12.09.1--..12+788', '1.2.31'];  // TODO: this is incorrect

        yield ['1.2-RC-SNAPSHOT', '1.2-RC'];  // TODO: this is incorrect

        yield ['-1.0.3-gamma+b7718', '1.0.3-gamma+b7718'];  // TODO: this is incorrect

        yield [
            '+justmeta',
            new InvalidArgumentException('Parameter does not contain a valid SemVer (sub)string.'),
        ];

        yield ['9.8.7+meta+meta', '9.8.7+meta'];  // TODO: this is incorrect

        yield ['9.8.7-whatever+meta+meta', '9.8.7-whatever+meta'];  // TODO: this is incorrect

        // TODO: this is incorrect
        yield [
            '99999999999999999999999.999999999999999999.99999999999999999----RC-SNAPSHOT.12.09.1--------------------------------..12',
            '99999999999999999999999.999999999999999999.99999999999999999',
        ];
    }

    private static function phpSpecVersionProvider(): iterable
    {
        // https://packagist.org/packages/phpspec/phpspec
        yield '1.4.0';

        yield '2.0.0-BETA1';

        yield '2.0.0-RC1';

        yield '2.2.0-BETA';

        yield ['2.5.x-dev', '2.5.'];    // TODO: this is incorrect

        yield [
            'dev-main',
            new InvalidArgumentException('Parameter does not contain a valid SemVer (sub)string.'),
        ];    // TODO: this is incorrect
    }
}
