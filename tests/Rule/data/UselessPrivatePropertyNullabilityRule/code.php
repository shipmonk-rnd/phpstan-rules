<?php declare(strict_types = 1);

class UselessPrivatePropertyNullabilityRuleExampleClass
{
    public ?int $isPublic;

    protected ?int $isProtected;

    private ?int $isPrivate; // error: Property UselessPrivatePropertyNullabilityRuleExampleClass::isPrivate is defined as nullable, but null is never assigned

    private ?int $isPrivateAssigned;

    private ?int $isPrivateWithConditionalAssignment;

    private ?int $isPrivateWithDefaultNull = null;

    private ?int $isPrivateWithDefaultNotNull = 1; // error: Property UselessPrivatePropertyNullabilityRuleExampleClass::isPrivateWithDefaultNotNull is defined as nullable, but null is never assigned

    private ?int $isUninitialized;

    public function __construct(
        int $isPublic,
        int $isProtected,
        int $isPrivate,
        int $isPrivateWithConditionalAssignment,
        int $isPrivateWithDefaultNull,
        int $isPrivateWithDefaultNotNull
    ) {
        $this->isPublic = $isPublic;
        $this->isProtected = $isProtected;
        $this->isPrivate = $isPrivate;
        $this->isPrivateWithConditionalAssignment = $isPrivateWithConditionalAssignment === 0 ? null : 1;
        $this->isPrivateWithDefaultNull = $isPrivateWithDefaultNull;
        $this->isPrivateWithDefaultNotNull = $isPrivateWithDefaultNotNull;
    }

    public function setIsPrivateAssigned(?int $isPrivateAssigned): void
    {
        $this->isPrivateAssigned = $isPrivateAssigned;
    }

}
