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

/*
 * This file is part of the box project.
 *
 * (c) Kevin Herrera <kevin@herrera.io>
 *     Théo Fidry <theo.fidry@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Infection\Tests\TestFramework\PhpSpec\FileSystem;

use Infection\Tests\TestFramework\PhpSpec\TestingUtility\FS;
use PHPUnit\Framework\TestCase;
use function Safe\getcwd;
use function Safe\realpath;
use Symfony\Component\Filesystem\Filesystem;
use function str_replace;

/**
 * Copy/pasted from infection/infection
 */
abstract class FileSystemTestCase extends TestCase
{
    protected string $cwd = '';

    protected string $tmp = '';

    protected function setUp(): void
    {
        $this->cwd = getcwd();
        $this->tmp = realpath(
            FS::tmpDir(
                $this->getTmpDirPrefix(),
            ),
        );

        chdir($this->tmp);
    }

    protected function tearDown(): void
    {
        chdir($this->cwd);

        (new Filesystem())->remove($this->tmp);
    }

    /**
     * If the test case is `App\Tests\MyFilesystemServiceTestCase`, the default prefix will be "App\Tests\MyFilesystemServiceTestCase".
     */
    protected function getTmpDirPrefix(): string
    {
        return str_replace('\\', '', static::class);
    }
}
