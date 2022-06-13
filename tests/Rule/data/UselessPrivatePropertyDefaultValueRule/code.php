<?php declare(strict_types = 1);

class UselessPrivatePropertyDefaultValueRuleExampleClass
{
    public int $isPublic = 0;

    protected int $isProtected = 0;

    private int $isPrivate = 0; // error: Property UselessPrivatePropertyDefaultValueRuleExampleClass::isPrivate has useless default value (overwritten in constructor)

    private int $isPrivateWithConditionalAssignment = 0; // not detected due to condition used in ctor

    private int $noDefaultValue;

    public function __construct(int $isPublic, int $isProtected, int $isPrivate, int $noDefaultValue)
    {
        $this->isPublic = $isPublic;
        $this->isProtected = $isProtected;
        $this->isPrivate = $isPrivate;
        $this->noDefaultValue = $noDefaultValue;

        if ($isPublic > 0) {
            $this->isPrivateWithConditionalAssignment = 1;
        } else {
            $this->isPrivateWithConditionalAssignment = 2;
        }
    }

}
