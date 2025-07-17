<?php

namespace EnforceNativeReturnTypehintRuleHooks;

class Person
{
    public int $age = 0 {
        set => $value >= 0 ? $value : throw new InvalidArgumentException;
    }

}
