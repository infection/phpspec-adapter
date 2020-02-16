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

namespace Infection\TestFramework\PhpSpec\Config;

use function array_key_exists;

/**
 * @internal
 */
abstract class AbstractYamlConfiguration
{
    /**
     * @var string
     */
    protected $tempDirectory;

    /**
     * @var array<string, mixed>
     */
    protected $parsedYaml;

    /**
     * AbstractYamlConfiguration constructor.
     *
     * @param array<string, mixed> $parsedYaml
     */
    public function __construct(string $tmpDir, array $parsedYaml)
    {
        $this->tempDirectory = $tmpDir;
        $this->parsedYaml = $parsedYaml;
    }

    abstract public function getYaml(): string;

    protected function isCodeCoverageExtension(string $extensionName): bool
    {
        return strpos($extensionName, 'CodeCoverage') !== false;
    }

    /**
     * @param array<string, mixed> $parsedYaml
     */
    protected function hasCodeCoverageExtension(array $parsedYaml): bool
    {
        if (!array_key_exists('extensions', $parsedYaml)) {
            return false;
        }

        foreach ($parsedYaml['extensions'] as $extensionName => $options) {
            if ($this->isCodeCoverageExtension($extensionName)) {
                return true;
            }
        }

        return false;
    }
}