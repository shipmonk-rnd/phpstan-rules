<?php

namespace ForbidUselessUnionCasesInConditionsRule;


class HelloWorld
{
    public function sayHello(
        \DateTimeImmutable|string $date,
        string $string
    ): void
    {
        if ($date === $string) {} // error: $date === $string is always false when DateTimeImmutable|string is DateTimeImmutable
    }
}
