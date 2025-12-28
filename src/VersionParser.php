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

namespace Infection\TestFramework\PhpSpec;

use Infection\TestFramework\PhpSpec\Throwable\UnrecognisableTestFrameworkVersion;
use function preg_match;

/**
 * @internal
 */
final readonly class VersionParser
{
    private const VERSION_REGEX = '/(?<version>\d+\.\d+\.?\d*)(?<prerelease>-[\d\p{L}.]+)?(?<build>\+[\d\p{L}.]+)?/';

    /**
     * Parses a string value to try to extract the exact version out of it. The input can
     * typically be the output of `$ tool --version`, which usually may include information
     * about the tool name and authors besides the version itself.
     *
     * @throws UnrecognisableTestFrameworkVersion
     *
     * @return non-empty-string
     */
    public function parse(string $value): string
    {
        $matches = [];
        $matched = preg_match(self::VERSION_REGEX, $value, $matches) > 0;

        if (!$matched) {
            throw UnrecognisableTestFrameworkVersion::create(
                'PhpSpec',
                $value,
            );
        }

        return $matches[0];
    }
}
