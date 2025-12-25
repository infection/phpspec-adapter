<?php

namespace spec\Infection\PhpSpecAdapter\E2ETests\PhpSpec\Covered;

use PhpSpec\ObjectBehavior;

use function Infection\PhpSpecAdapter\E2ETests\PhpSpec\Covered\formatName;

/**
 * Note: PhpSpec is designed to test objects, not standalone functions.
 * This spec tests the formatName function by invoking it directly within test methods.
 * For a more idiomatic PhpSpec approach, consider wrapping the function in a class.
 */
class FormatNameFunctionSpec extends ObjectBehavior
{
    function it_formats_name_with_both_names()
    {
        $result = formatName('John', 'Doe');
        if ($result !== 'John Doe') {
            throw new \Exception("Expected 'John Doe', got '{$result}'");
        }
    }

    function it_formats_name_with_first_name_only()
    {
        $result = formatName('John', '');
        if ($result !== 'John') {
            throw new \Exception("Expected 'John', got '{$result}'");
        }
    }

    function it_formats_name_with_last_name_only()
    {
        $result = formatName('', 'Doe');
        if ($result !== 'Doe') {
            throw new \Exception("Expected 'Doe', got '{$result}'");
        }
    }

    function it_formats_name_with_no_names()
    {
        $result = formatName('', '');
        if ($result !== 'Anonymous') {
            throw new \Exception("Expected 'Anonymous', got '{$result}'");
        }
    }
}