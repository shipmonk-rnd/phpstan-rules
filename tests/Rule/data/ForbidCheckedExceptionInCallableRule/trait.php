<?php

namespace ForbidCheckedExceptionInCallableRuleTrait;

trait FaultyTrait
{
    /**
     * @throws \Exception
     */
    public function throw(): void
    {
        throw new \Exception();
    }

    public function acceptCallable(callable $c): void
    {

    }

    public function failing(): void
    {
        $this->acceptCallable(function() {
            $this->throw(); // error: Throwing checked exception Exception in closure!
        });

    }
}
