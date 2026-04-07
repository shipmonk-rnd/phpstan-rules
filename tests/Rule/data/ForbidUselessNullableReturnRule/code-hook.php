<?php

namespace ForbidUselessNullableReturnRuleHook;

class HookedProperties {

    public ?string $value = null {
        get {
            $this->value !== null || throw new \LogicException('No value set');

            return $this->value;
        }
    }

    public ?int $number = null {
        get {
            return $this->number;
        }
    }

}
