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

use Infection\TestFramework\PhpSpec\FinderException;
use PHPUnit\Framework\TestCase;
use function Safe\sprintf;

final class FinderExceptionTest extends TestCase
{
    public function test_composer_not_found_exception(): void
    {
        $exception = FinderException::composerNotFound();

        $this->assertSame(
            'Unable to locate a Composer executable on local system. Ensure that Composer is installed and available.',
            $exception->getMessage()
        );
    }

    public function test_php_executable_not_found(): void
    {
        $exception = FinderException::phpExecutableNotFound();

        $this->assertSame(
            'Unable to locate the PHP executable on the local system. Please report this issue, and include details about your setup.',
            $exception->getMessage()
        );
    }

    public function test_test_framework_not_found(): void
    {
        $framework = 'framework';

        $exception = FinderException::testFrameworkNotFound($framework);

        $this->assertSame(
            sprintf(
                'Unable to locate a %s executable on local system. Ensure that %s is installed and available.',
                $framework,
                $framework
            ),
            $exception->getMessage()
        );
    }

    public function test_test_custom_path_does_not_exist(): void
    {
        $framework = 'framework';
        $path = 'foo/bar/abc';

        $exception = FinderException::testCustomPathDoesNotExist($framework, $path);

        $this->assertSame(
            sprintf('The custom path to %s was set as "%s" but this file did not exist.',
                $framework,
                $path
            ),
            $exception->getMessage()
        );
    }
}
