<?php declare(strict_types = 1);

namespace UselessPrivatePropertyDefaultValueRule;

class AsymmetricVisibilityTest
{
    public private(set) int $publicPrivateSet = 0;

    protected private(set) int $protectedPrivateSet = 0;

    public protected(set) int $publicProtectedSet = 0;

    public public(set) int $publicPublicSet = 0;

    protected protected(set) int $protectedProtectedSet = 0;

    private private(set) int $privatePrivateSet = 0; // error: Property UselessPrivatePropertyDefaultValueRule\AsymmetricVisibilityTest::privatePrivateSet has useless default value (overwritten in constructor)

    private int $normalPrivate = 0; // error: Property UselessPrivatePropertyDefaultValueRule\AsymmetricVisibilityTest::normalPrivate has useless default value (overwritten in constructor)

    public function __construct(int $a, int $b, int $c, int $d, int $e, int $f, int $g)
    {
        $this->publicPrivateSet = $a;
        $this->protectedPrivateSet = $b;
        $this->publicProtectedSet = $c;
        $this->publicPublicSet = $d;
        $this->protectedProtectedSet = $e;
        $this->privatePrivateSet = $f;
        $this->normalPrivate = $g;
    }
}
