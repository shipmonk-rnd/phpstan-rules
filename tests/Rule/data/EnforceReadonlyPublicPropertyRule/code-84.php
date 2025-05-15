<?php

namespace EnforceReadonlyPublicPropertyRule84;

interface I
{
    public string $key { get; }
    public int|float|string $value { get; set; } // error: Public property `value` cannot have a setter hook, because is mark as readonly.
}

// there is DiscriminatorMap Attribute from symfony
abstract readonly class A implements I
{
}

readonly class B
{
    public function __construct(
        public string $key,
        public int $value,
    ) {
    }
}
readonly class C
{
    public function __construct(
        public string $key,
        public string $value,
    ) {
    }
}
readonly class D
{
    public function __construct(
        public string $key,
        public int $value,
    ) {
    }
}


class X
{
    /**
     * @param array<A> $foo
     */
    public function __construct(
        public readonly array $foo = [],
    ) {
    }

    public function getFirstKeyOfFloat(): ?string
    {
        foreach ($this->foo as $item) {
            if (is_float($item->value)) {
                return $item->key;
            }
        }
        return null;
    }
}

