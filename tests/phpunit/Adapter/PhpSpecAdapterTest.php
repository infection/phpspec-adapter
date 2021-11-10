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

namespace Infection\Tests\TestFramework\PhpSpec\Adapter;

use Infection\AbstractTestFramework\Coverage\TestLocation;
use Infection\TestFramework\PhpSpec\CommandLine\ArgumentsAndOptionsBuilder;
use Infection\TestFramework\PhpSpec\CommandLineBuilder;
use Infection\TestFramework\PhpSpec\Config\Builder\InitialConfigBuilder;
use Infection\TestFramework\PhpSpec\Config\Builder\MutationConfigBuilder;
use Infection\TestFramework\PhpSpec\PhpSpecAdapter;
use Infection\TestFramework\PhpSpec\VersionParser;
use PHPUnit\Framework\TestCase;
use function sprintf;

final class PhpSpecAdapterTest extends TestCase
{
    public function test_it_has_a_name(): void
    {
        $adapter = $this->getAdapter();

        $this->assertSame('PhpSpec', $adapter->getName());
    }

    public function test_it_determines_when_tests_do_not_pass(): void
    {
        $output = <<<OUTPUT
TAP version 13
not ok 1 - Error: Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should find by user id
ok 1 - Infection\Application\Handler\AddViolationHandler: should add violation
ok 2 - Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should add goal
ok 3 - Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should remove existing one
ok 4 - Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should find by user id
not ok 103 - Error: Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should find by user id
1..103

OUTPUT;

        $adapter = $this->getAdapter();

        $this->assertFalse($adapter->testsPass($output));
    }

    public function test_it_determines_when_tests_pass(): void
    {
        $output = <<<OUTPUT
TAP version 13
ok 1 - Infection\Application\Handler\AddViolationHandler: should add violation
ok 2 - Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should add goal
ok 3 - Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should remove existing one
ok 4 - Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should find by user id
1..4

OUTPUT;

        $adapter = $this->getAdapter();

        $this->assertTrue($adapter->testsPass($output));
    }

    public function test_it_catches_fatal_errors(): void
    {
        $output = <<<OUTPUT
TAP version 13
ok 1 - Infection\Application\Handler\AddViolationHandler: should add violation
ok 2 - Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should add goal
ok 3 - Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should remove existing one
ok 4 - Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should find by user id
Fatal error happened .....
1..5

OUTPUT;

        $adapter = $this->getAdapter();

        $this->assertFalse($adapter->testsPass($output));
    }

    public function test_it_catches_fatal_errors_from_start(): void
    {
        $output = <<<OUTPUT
TAP version 13

Fatal error: Access level to Foo\Bar\Foobar::foobar() must be public (as in class Foo\Bar\FoobarInterface) in...

Call Stack:

OUTPUT;

        $adapter = $this->getAdapter();

        $this->assertFalse($adapter->testsPass($output));
    }

    public function test_has_junit_report_returns_false(): void
    {
        $adapter = $this->getAdapter();

        $this->assertFalse($adapter->hasJUnitReport(), 'PhpSpec does not have JUnit Report');
    }

    public function test_it_returns_initial_tests_fail_recommendations(): void
    {
        $adapter = $this->getAdapter();

        $commandLine = 'cli';

        $this->assertSame(
            sprintf('Check the executed command to identify the problem: %s', $commandLine),
            $adapter->getInitialTestsFailRecommendations($commandLine)
        );
    }

    public function test_it_provides_initial_test_run_command_line(): void
    {
        $initialConfigBuilder = $this->createMock(InitialConfigBuilder::class);
        $initialConfigBuilder->expects($this->once())
            ->method('build')
            ->with('7.2.0')
            ->willReturn('/tmp/phpspecConfiguration.initial.infection.yml');

        $commandLineBuilder = $this->createMock(CommandLineBuilder::class);

        $commandLineBuilder->expects($this->once())
            ->method('build')
            ->with(
                '/path/to/phpspec',
                ['-d', 'memory_limit=-1'],
                [
                    'run',
                    '--config',
                    '/tmp/phpspecConfiguration.initial.infection.yml',
                    '--no-ansi',
                    '--format=tap',
                    '--stop-on-failure',
                    '--ansi',
                ]
            )
            ->willReturn(['/path/to/phpspec', '--dummy-argument']);

        $adapter = new PhpSpecAdapter(
            '/path/to/phpspec',
            $initialConfigBuilder,
            $this->createMock(MutationConfigBuilder::class),
            new ArgumentsAndOptionsBuilder(),
            new VersionParser(),
            $commandLineBuilder,
            '7.2.0'
        );

        $initialTestRunCommandLine = $adapter->getInitialTestRunCommandLine('--ansi', ['-d', 'memory_limit=-1'], true);

        $this->assertSame(
            [
                '/path/to/phpspec',
                '--dummy-argument',
            ],
            $initialTestRunCommandLine
        );
    }

    public function test_it_provides_mutant_test_run_command_line(): void
    {
        $coverageTests = [new TestLocation('test', 'path', 1.2)];
        $mutatedFilePath = '/tmp/mutated_file_path.php';
        $mutationHash = 'hash';
        $mutationOriginalFilePath = '/src/Class.php';

        $expectedMutationConfigFile = '/path/file';

        $mutationConfigBuilder = $this->createMock(MutationConfigBuilder::class);

        $mutationConfigBuilder->expects($this->once())
            ->method('build')
            ->with($coverageTests, $mutatedFilePath, $mutationHash, $mutationOriginalFilePath)
            ->willReturn($expectedMutationConfigFile);

        $commandLineBuilder = $this->createMock(CommandLineBuilder::class);

        $commandLineBuilder->expects($this->once())
            ->method('build')
            ->with(
                '/path/to/phpspec',
                [],
                [
                    'run',
                    '--config',
                    $expectedMutationConfigFile,
                    '--no-ansi',
                    '--format=tap',
                    '--stop-on-failure',
                ]
            )
            ->willReturn(['/path/to/phpspec', '--dummy-argument']);

        $adapter = new PhpSpecAdapter(
            '/path/to/phpspec',
            $this->createMock(InitialConfigBuilder::class),
            $mutationConfigBuilder,
            new ArgumentsAndOptionsBuilder(),
            new VersionParser(),
            $commandLineBuilder,
            '7.2.0'
        );

        $initialTestRunCommandLine = $adapter->getMutantCommandLine(
            [new TestLocation('test', 'path', 1.2)],
            $mutatedFilePath,
            $mutationHash,
            $mutationOriginalFilePath,
            ''
        );

        $this->assertSame(
            [
                '/path/to/phpspec',
                '--dummy-argument',
            ],
            $initialTestRunCommandLine
        );
    }

    private function getAdapter(): PhpSpecAdapter
    {
        return new PhpSpecAdapter(
            '/path/to/phpspec',
            $this->createMock(InitialConfigBuilder::class),
            $this->createMock(MutationConfigBuilder::class),
            new ArgumentsAndOptionsBuilder(),
            new VersionParser(),
            new CommandLineBuilder()
        );
    }
}
