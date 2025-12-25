<?php

namespace spec\Infection\PhpSpecAdapter\E2ETests\PhpSpec\Covered;

use Infection\PhpSpecAdapter\E2ETests\PhpSpec\Covered\UserService;
use PhpSpec\ObjectBehavior;

class UserServiceSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(UserService::class);
    }

    function it_adds_user_successfully()
    {
        $this->addUser('John Doe', 'john@example.com')->shouldReturn(true);
        $this->getUserCount()->shouldReturn(1);
        $this->hasLogs()->shouldReturn(true);
    }

    function it_fails_to_add_user_with_empty_name()
    {
        $this->addUser('', 'john@example.com')->shouldReturn(false);
        $this->getUserCount()->shouldReturn(0);

        $this->getLogs()->shouldHaveCount(1);
        $this->getLogs()[0]->shouldBe('Failed to add user: empty name or email');
    }

    function it_fails_to_add_user_with_empty_email()
    {
        $this->addUser('John Doe', '')->shouldReturn(false);
        $this->getUserCount()->shouldReturn(0);

        $this->getLogs()->shouldHaveCount(1);
        $this->getLogs()[0]->shouldBe('Failed to add user: empty name or email');
    }

    function it_fails_to_add_duplicate_user()
    {
        $this->addUser('John Doe', 'john@example.com');
        $this->clearLogs();

        $this->addUser('Jane Doe', 'john@example.com')->shouldReturn(false);
        $this->getUserCount()->shouldReturn(1);

        $this->getLogs()->shouldHaveCount(1);
        $this->getLogs()[0]->shouldBe('Failed to add user: email john@example.com already exists');
    }

    function it_removes_user_successfully()
    {
        $this->addUser('John Doe', 'john@example.com');
        $this->clearLogs();

        $this->removeUser('john@example.com')->shouldReturn(true);
        $this->getUserCount()->shouldReturn(0);

        $this->getLogs()->shouldHaveCount(1);
        $this->getLogs()[0]->shouldBe('User john@example.com removed successfully');
    }

    function it_fails_to_remove_non_existent_user()
    {
        $this->removeUser('john@example.com')->shouldReturn(false);

        $this->getLogs()->shouldHaveCount(1);
        $this->getLogs()[0]->shouldBe('Failed to remove user: email john@example.com not found');
    }

    function it_returns_user_data()
    {
        $this->addUser('John Doe', 'john@example.com');

        $user = $this->getUser('john@example.com');
        $user->shouldBeArray();
        $user['name']->shouldBe('John Doe');
        $user['email']->shouldBe('john@example.com');
    }

    function it_returns_null_for_non_existent_user()
    {
        $this->getUser('john@example.com')->shouldReturn(null);
    }

    function it_checks_if_user_exists()
    {
        $this->userExists('john@example.com')->shouldReturn(false);

        $this->addUser('John Doe', 'john@example.com');

        $this->userExists('john@example.com')->shouldReturn(true);
    }

    function it_has_logger_trait_methods()
    {
        $this->hasLogs()->shouldReturn(false);
        $this->getLogs()->shouldHaveCount(0);

        $this->addUser('John Doe', 'john@example.com');

        $this->hasLogs()->shouldReturn(true);
        $logs = $this->getLogs();
        if (count($logs->getWrappedObject()) === 0) {
            throw new \Exception('Expected logs to not be empty');
        }

        $this->clearLogs();

        $this->hasLogs()->shouldReturn(false);
        $this->getLogs()->shouldHaveCount(0);
    }

    function it_has_public_log_method()
    {
        $reflection = new \ReflectionMethod(UserService::class, 'log');
        if (!$reflection->isPublic()) {
            throw new \Exception('log() method must be public');
        }

        $this->log('Direct log call');
        $this->getLogs()->shouldContain('Direct log call');
    }
}