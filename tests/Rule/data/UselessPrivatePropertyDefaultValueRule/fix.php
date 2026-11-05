<?php declare(strict_types = 1);

namespace UselessPrivatePropertyDefaultValueRuleFix;

class ExampleClass
{

    private int $overwritten = 0;

    private ?string $overwrittenNullable = null;

    private int $first = 1, $second = 2, $kept = 3;

    private int $conditional = 0;

    public function __construct(bool $flag)
    {
        $this->overwritten = 1;
        $this->overwrittenNullable = 'a';
        $this->first = 1;
        $this->second = 2;

        if ($flag) {
            $this->conditional = 1;
        }
    }

}
