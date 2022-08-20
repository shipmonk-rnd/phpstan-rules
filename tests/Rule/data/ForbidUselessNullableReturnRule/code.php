<?php

namespace ForbidUselessNullableReturnRule;

class ExampleClass {

    private ?int $foo;

    private int $bar;

    public function getNullable1(int $one): ?bool // error: Declared return type bool|null contains null, but it is never returned. Returned types: bool.
    {
        return $one === 1 || $this->bar === 1;
    }

    public function getNullable2(?int $two): ?int // error: Declared return type int|null contains null, but it is never returned. Returned types: int.
    {
        return $two ?? $this->bar;
    }

    public function getNullable3(int $one, ?int $two): ?int // error: Declared return type int|null contains null, but it is never returned. Returned types: int.
    {
        if ($one > 1) {
            return 1;
        }

        if ($two !== null) {
            return $two;
        }

        return $two ?? $one;
    }

    public function getNullable4(?int $two): ?int
    {
        return $this->foo ?? $two ?? null;
    }

    public function getStrict1(): int
    {
        return 1;
    }

    public function getStrict2(int $one): int
    {
        if ($one > 0) {
            return $this->bar;
        }
        return $one;
    }

    public function getStrict3(): int
    {
        return $this->bar;
    }

}
