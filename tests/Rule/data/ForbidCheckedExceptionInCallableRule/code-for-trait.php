<?php

namespace ForbidCheckedExceptionInCallableRuleForTrait;

use ForbidCheckedExceptionInCallableRuleTrait\FaultyTrait;

class ClassWithTrait
{
    use FaultyTrait;

    public function inClassTest()
    {
        $this->acceptCallable($this->throw(...)); // error: Throwing checked exception Exception in first-class-callable!
    }
}
