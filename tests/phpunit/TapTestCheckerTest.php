<?php

namespace Infection\Tests\TestFramework\PhpSpec;

use Infection\TestFramework\PhpSpec\TapTestChecker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TapTestChecker::class)]
final class TapTestCheckerTest extends TestCase
{
    #[DataProvider('tapOutputProvider')]
    public function test_it_can_tell_if_a_test_passed_or_not_from_the_execution_output(
        string $output,
        bool $expected,
    ): void
    {
        $checker = new TapTestChecker();

        $actual = $checker->testsPass($output);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, bool}>
     */
    public static function tapOutputProvider(): iterable
    {
        yield 'TAP result type: version' => [
            'TAP version 12',
            true,
        ];

        yield 'TAP result type: plan' => [
            '1..42',
            true,
        ];

        yield 'TAP result type: pragma; turn on strict mode' => [
            // Not really used in the PHP community
            'pragma +strict',
            true,
        ];

        yield 'TAP result type: pragma; disable feature' => [
            // Not really used in the PHP community
            'pragma -foo',
            true,
        ];

        yield 'TAP result type: successful test' => [
            'ok 3 - We should start with some Hello world!',
            true,
        ];

        yield 'TAP result type: successful test with a directive' => [
            'ok 3 - We should start with some Hello world! # TODO passed!',
            true,
        ];

        yield 'TAP result type: failing test' => [
            'not ok 17 - Pigs can fly',
            false,
        ];

        yield 'TAP result type: failing test with a directive' => [
            'not ok 17 - Pigs can fly # TODO not enough acid!',
            true,
        ];

        yield 'TAP result type: comment' => [
            '# Hope we don\'t use up all of our tokens.',
            true,
        ];

        yield 'TAP result type: bailout' => [
            'Bail out!  We ran out of tokens!',
            false,
        ];

        yield 'TAP result type: unknown' => [
            '... yo, this ain\'t TAP! ...',
            true,
        ];

        yield 'TAP v13 specific: YAML block for better diagnostic' => [
            <<<'TAP'
            ---
            message: ''
            severity: fail
            ...
            TAP,
            true,   // We do not fail on the diagnostic itself
        ];

        yield 'TAP v13 specific: YAML block for better diagnostic (complete example)' => [
            <<<'TAP'
            TAP version 13
            not ok 2 - First line of the input valid
            ---
            message: ''
            severity: fail
            ...
            TAP,
            false,
        ];

        yield 'TAP v13 specific: YAML block for better diagnostic (complete example with ident – done in PhpSpec)' => [
            <<<'TAP'
            TAP version 13
            not ok 2 - First line of the input valid
              ---
              message: ''
              severity: fail
              ...
            TAP,
            false,
        ];

        yield 'example of successful TAP output with no version specified (defaults to v12)' => [
            <<<'TAP'
            1..5
            ok 1 - database connection established
            ok 2 - user table exists
            ok 3 - can insert new user
            ok 4 - can retrieve user data
            ok 5 - can update user record
            TAP,
            true,
        ];

        yield 'example of failing TAP output with no version specified (defaults to v12)' => [
            <<<'TAP'
            1..6
            ok 1 - database connection established
            not ok 2 - user table exists
            # Expected table 'users' but found 'user'
            ok 3 - can insert new user # SKIP table not found
            not ok 4 - can retrieve user data
            # Query failed: table does not exist
            ok 5 - can update user record # TODO implement update logic
            not ok 6 - can delete user
            # Error: Permission denied
            TAP,
            false,
        ];

        yield 'example of successful TAP output with v12' => [
            <<<'TAP'
            TAP version 12
            1..5
            ok 1 - database connection established
            ok 2 - user table exists
            ok 3 - can insert new user
            ok 4 - can retrieve user data
            ok 5 - can update user record
            TAP,
            true,
        ];

        yield 'example of failing TAP output with v12' => [
            <<<'TAP'
            TAP version 12
            1..6
            ok 1 - database connection established
            not ok 2 - user table exists
            # Expected table 'users' but found 'user'
            ok 3 - can insert new user # SKIP table not found
            not ok 4 - can retrieve user data
            # Query failed: table does not exist
            ok 5 - can update user record # TODO implement update logic
            not ok 6 - can delete user
            # Error: Permission denied
            TAP,
            false,
        ];

        yield 'example of successful TAP output with v13' => [
            <<<'TAP'
            TAP version 13
            1..5
            ok 1 - database connection established
            ok 2 - user table exists
            ok 3 - can insert new user
            ok 4 - can retrieve user data
            ok 5 - can update user record
            TAP,
            true,
        ];

        yield 'example of failing TAP output with v13' => [
            <<<'TAP'
            TAP version 13
            1..6
            ok 1 - database connection established
            not ok 2 - user table exists
            ---
            message: 'Table name mismatch'
            severity: fail
            data:
              expected: 'users'
              got: 'user'
              query: 'SHOW TABLES LIKE "users"'
            ...
            ok 3 - can insert new user # SKIP table not found
            not ok 4 - can retrieve user data
            ---
            message: 'Query execution failed'
            severity: fail
            data:
              error: 'Table does not exist'
              error_code: 1146
              query: 'SELECT * FROM users WHERE id = 1'
            ...
            ok 5 - can update user record # TODO implement update logic
            ---
            message: 'Update feature not yet implemented'
            severity: todo
            ...
            not ok 6 - can delete user
            ---
            message: 'Permission denied'
            severity: fail
            data:
              user: 'test_user'
              required_permission: 'DELETE'
              current_permissions: ['SELECT', 'INSERT']
            ...
            TAP,
            false,
        ];

        yield 'example of successful TAP output with v14' => [
            <<<'TAP'
            TAP version 14
            1..3
            ok 1 - system initialization
                # Subtest: database operations
                1..4
                ok 1 - connection established
                ok 2 - schema validated
                ok 3 - test data inserted
                ok 4 - queries executed successfully
            ok 2 - database operations
                # Subtest: API endpoints
                1..3
                ok 1 - GET /users returns 200
                ok 2 - POST /users creates record
                ok 3 - DELETE /users/123 removes record
            ok 3 - API endpoints
            TAP,
            true,
        ];

        yield 'example of failing TAP output with v14' => [
            <<<'TAP'
            TAP version 14
            pragma +strict
            1..4
            ok 1 - system initialization
                # Subtest: database operations
                1..4
                ok 1 - connection established
                not ok 2 - schema validated
                ---
                message: 'Schema validation failed'
                severity: fail
                data:
                  expected_version: '2.1.0'
                  actual_version: '2.0.5'
                  missing_columns: ['email_verified', 'last_login']
                ...
                ok 3 - test data inserted # SKIP schema invalid
                not ok 4 - queries executed successfully
                ---
                message: 'Column not found in query'
                severity: fail
                data:
                  error: "Unknown column 'email_verified' in 'field list'"
                  query: 'SELECT id, email, email_verified FROM users'
                ...
            not ok 2 - database operations
                # Subtest: API endpoints
                1..3
                ok 1 - GET /users returns 200
                not ok 2 - POST /users creates record
                ---
                message: 'Request failed with validation error'
                severity: fail
                data:
                  status_code: 422
                  error: 'email_verified field is required'
                  request_body:
                    name: 'John Doe'
                    email: '[email protected]'
                ...
                ok 3 - DELETE /users/123 removes record # SKIP creation failed
            not ok 3 - API endpoints
            ok 4 - cleanup completed # TODO verify all resources released
            ---
            message: 'Cleanup verification not implemented'
            severity: todo
            ...
            TAP,
            false,
        ];

        yield 'example of PhpSpec TAP output for a successful execution (generated from the e2e tests)' => [
            <<<'TAP'
            TAP version 13
            ok 1 - Infection\Application\Handler\AddViolationHandler: should add violation
            ok 2 - Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should add goal
            ok 3 - Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should remove existing one
            ok 4 - Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should find by user id
            1..4

            TAP,
            true,
        ];

        yield 'example of PhpSpec TAP output for a failed execution (generated from the e2e tests)' => [
            <<<'TAP'
            TAP version 13
            not ok 1 - Error: Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should find by user id
            ok 1 - Infection\Application\Handler\AddViolationHandler: should add violation
            ok 2 - Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should add goal
            ok 3 - Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should remove existing one
            ok 4 - Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should find by user id
            not ok 103 - Error: Infection\Infrastructure\Domain\Model\Goal\InMemoryGoalRepository: should find by user id
            1..103

            TAP,
            false,
        ];

        yield 'example of PhpSpec TAP output for a failed execution with a diagnosis (generated from the e2e tests)' => [
            <<<'TAP'
            TAP version 13
            ok 1 - Infection\PhpSpecAdapter\E2ETests\PhpSpec\Covered\Calculator: is initializable
            not ok 2 - Infection\PhpSpecAdapter\E2ETests\PhpSpec\Covered\Calculator: adds two positive numbers
              ---
              message: ''
              severity: fail
              ...
            ok 3 - Infection\PhpSpecAdapter\E2ETests\PhpSpec\Covered\Calculator: subtracts two numbers
            1..3

            TAP,
            false,
        ];
    }
}
