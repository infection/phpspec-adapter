<?php

namespace spec\Infection\PhpSpecAdapter\E2ETests\PhpSpec\Covered;

use Infection\PhpSpecAdapter\E2ETests\PhpSpec\Covered\Calculator;
use PhpSpec\ObjectBehavior;

class CalculatorSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Calculator::class);
    }

    function it_adds_two_positive_numbers()
    {
        $this->add(2, 3)->shouldReturn(5);
    }

    function it_adds_negative_and_positive_numbers()
    {
        $this->add(-5, 5)->shouldReturn(0);
    }

    function it_adds_two_negative_numbers()
    {
        $this->add(-5, -5)->shouldReturn(-10);
    }

    function it_subtracts_two_numbers()
    {
        $this->subtract(5, 3)->shouldReturn(2);
    }

    function it_subtracts_with_negative_result()
    {
        $this->subtract(-5, 5)->shouldReturn(-10);
    }

    function it_subtracts_equal_numbers()
    {
        $this->subtract(5, 5)->shouldReturn(0);
    }

    function it_multiplies_two_positive_numbers()
    {
        $this->multiply(3, 5)->shouldReturn(15);
    }

    function it_multiplies_negative_and_positive_numbers()
    {
        $this->multiply(-3, 5)->shouldReturn(-15);
    }

    function it_multiplies_by_zero()
    {
        $this->multiply(0, 5)->shouldReturn(0);
    }

    function it_divides_two_numbers()
    {
        $this->divide(5, 2)->shouldReturn(2.5);
    }

    function it_divides_negative_and_positive_numbers()
    {
        $this->divide(-5, 2)->shouldReturn(-2.5);
    }

    function it_divides_equal_numbers()
    {
        $this->divide(5, 5)->shouldReturn(1.0);
    }

    function it_throws_exception_when_dividing_by_zero()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->during('divide', [5, 0]);
    }

    function it_checks_if_positive_number_is_positive()
    {
        $this->isPositive(5)->shouldReturn(true);
    }

    function it_checks_if_negative_number_is_not_positive()
    {
        $this->isPositive(-5)->shouldReturn(false);
    }

    function it_checks_if_zero_is_positive()
    {
        $this->isPositive(0)->shouldReturn(true);
    }

    function it_returns_absolute_value_of_positive_number()
    {
        $this->absolute(5)->shouldReturn(5);
    }

    function it_returns_absolute_value_of_negative_number()
    {
        $this->absolute(-5)->shouldReturn(5);
    }

    function it_returns_absolute_value_of_zero()
    {
        $this->absolute(0)->shouldReturn(0);
    }

    function it_handles_boundary_values_for_absolute()
    {
        $this->absolute(1)->shouldReturn(1);
        $this->absolute(-1)->shouldReturn(1);
    }

    function it_ensures_zero_is_not_negated_in_absolute()
    {
        $result = $this->absolute(0);
        $result->shouldReturn(0);
        $result->shouldBe(0);
    }
}