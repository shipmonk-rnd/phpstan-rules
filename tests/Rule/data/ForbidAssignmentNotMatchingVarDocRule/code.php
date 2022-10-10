<?php declare(strict_types = 1);

namespace ForbidAssignmentNotMatchingVarDocRule;

use Exception;
use LogicException;
use RuntimeException;
use ShipMonk\PHPStan\Rule\ForbidAssignmentNotMatchingVarDocRule;
use Throwable;

class AnotherClass {

}

interface ExampleInterface {

}

abstract class ExampleClassParent implements ExampleInterface
{

}

class ExampleClass extends ExampleClassParent
{

    public function test(): void
    {
        /** @var array $var */
        $var = $this->returnArrayShape();

        /** @var mixed[] $var */
        $var = $this->returnArrayShape();

        /** @var mixed $var */
        $var = $this->returnArrayShape();

        /** @var mixed[][] $var */
        $var = $this->returnArrayShape(); // error: Invalid var phpdoc of $var. Cannot assign array{id: int, value: string} to array<array>

        /** @var array{id: int, value: string} $var */
        $var = $this->returnArrayShape();

        /** @var array{id: int, value: string, notPresent: bool} $var */
        $var = $this->returnArrayShape(); // error: Invalid var phpdoc of $var. Cannot assign array{id: int, value: string} to array{id: int, value: string, notPresent: bool}

        /** @var array{id: int, value: string, notPresent: bool} $var check-shape-only */
        $var = $this->returnArrayShape(); // error: Invalid var phpdoc of $var. Cannot assign array{id: mixed, value: mixed} to array{id: int, value: string, notPresent: bool}

        /** @var array{id: string, value: string} $var */
        $var = $this->returnArrayShape(); // error: Invalid var phpdoc of $var. Cannot assign array{id: int, value: string} to array{id: string, value: string}

        /** @var array{id: string, value: string} $var check-shape-only */
        $var = $this->returnArrayShape();

        /** @var iterable<array{invalid: string}> $var check-shape-only */
        $var = $this->returnIterableWithArrayShape(); // error: Invalid var phpdoc of $var. Cannot assign iterable<array{id: mixed, value: mixed}> to iterable<array{invalid: string}>


        /** @var self $var */
        $var = $this->returnSelf();

        /** @var ExampleClass $var */
        $var = $this->returnSelf();

        /** @var ExampleInterface $var */
        $var = $this->returnSelf();

        /** @var ExampleClassParent $var */
        $var = $this->returnSelf();

        /** @var AnotherClass $var */
        $var = $this->returnSelf(); // error: Invalid var phpdoc of $var. Cannot assign ForbidAssignmentNotMatchingVarDocRule\ExampleClass to ForbidAssignmentNotMatchingVarDocRule\AnotherClass


        /** @var int $var */
        $var = $this->returnInt();

        /** @var int|string $var */
        $var = $this->returnInt();

        /** @var mixed $var */
        $var = $this->returnInt();


        /** @var string $var */
        $var = $this->returnString();

        /** @var class-string $var */
        $var = $this->returnString(); // error: Invalid var phpdoc of $var. Cannot assign string to class-string


        /** @var string $var */
        $var = $this->returnNullableString(); // error: Invalid var phpdoc of $var. Cannot assign string|null to string

        /** @var string|null|int $var */
        $var = $this->returnNullableString();


        /** @var array<ExampleInterface> $var */
        $var = $this->returnListOfSelf();

        /** @var array<self> $var */
        $var = $this->returnListOfSelf();

        /** @var array<object> $var */
        $var = $this->returnListOfSelf();

        /** @var array<mixed> $var */
        $var = $this->returnListOfSelf();

        /** @var array<int> $var */
        $var = $this->returnListOfSelf(); // error: Invalid var phpdoc of $var. Cannot assign array<int, ForbidAssignmentNotMatchingVarDocRule\ExampleClass> to array<int>
    }

    /**
     * @return list<self>
     */
    public function returnListOfSelf(): array
    {
        return [];
    }

    /**
     * @return array{ id: int, value: string }
     */
    public function returnArrayShape(): array
    {
        return ['id' => 1, 'value' => 'foo'];
    }

    /**
     * @return iterable<array{ id: int, value: string }>
     */
    public function returnIterableWithArrayShape(): iterable
    {
        return [['id' => 1, 'value' => 'foo']];
    }

    public function returnInt(): int
    {
        return 0;
    }

    public function returnString(): string
    {
        return '';
    }

    public function returnNullableString(): ?string
    {
        return '';
    }

    public function returnSelf(): self
    {
        return $this;
    }

}
