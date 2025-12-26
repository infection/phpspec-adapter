<?php

declare(strict_types=1);

namespace Infection\TestFramework\PhpSpec\Throwable;

use RuntimeException;
use function sprintf;

final class UnrecognisablePhpSpecVersion extends RuntimeException
{
    public static function create(string $value): self
    {
        return new self(
            sprintf(
                'The value "%s" is not a valid SemVer (sub)string.',
                $value,
            ),
        );
    }
}
