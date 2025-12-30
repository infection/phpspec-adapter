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

use function explode;
use function ltrim;
use const PHP_EOL;
use function str_starts_with;

/**
 * Minimal implementation that can tell if the tests passed or not from a TAP output.
 *
 * @see https://testanything.org/
 *
 * @internal
 * @final
 */
readonly class TapTestChecker
{
    /**
     * @param string $output Output of a test execution following the TAP format.
     */
    public function testsPass(string $output): bool
    {
        $hasAtLeastOnceSuccessfulTest = false;

        $lines = explode(PHP_EOL, $output);

        foreach ($lines as $line) {
            $leftTrimmedLine = ltrim($line);

            if (str_starts_with($leftTrimmedLine, 'Bail out!')
                || str_starts_with($leftTrimmedLine, 'not ok ')
            ) {
                return false;
            }

            if (str_starts_with($leftTrimmedLine, 'ok ')) {
                $hasAtLeastOnceSuccessfulTest = true;
            }
        }

        return $hasAtLeastOnceSuccessfulTest;
    }
}
