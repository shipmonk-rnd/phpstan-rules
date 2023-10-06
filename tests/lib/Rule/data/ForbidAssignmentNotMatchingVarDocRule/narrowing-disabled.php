<?php declare(strict_types = 1);

namespace ForbidAssignmentNotMatchingVarDocRule\NoNarrow;

use Exception;
use LogicException;
use RuntimeException;
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
        $var = $this->returnSelf(); // error: Invalid var phpdoc of $var. Cannot assign ForbidAssignmentNotMatchingVarDocRule\NoNarrow\ExampleClass to ForbidAssignmentNotMatchingVarDocRule\NoNarrow\AnotherClass

        /** @var ExampleInterface $var */
        $var = $this->returnInterface();

        /** @var ExampleClass $var */
        $var = $this->returnInterface(); // error: Invalid var phpdoc of $var. Cannot narrow ForbidAssignmentNotMatchingVarDocRule\NoNarrow\ExampleInterface to ForbidAssignmentNotMatchingVarDocRule\NoNarrow\ExampleClass

        /** @var ExampleClass $var allow-narrowing */
        $var = $this->returnInterface();


        /** @var int $var */
        $var = $this->returnInt();

        /** @var int|string $var */
        $var = $this->returnInt();

        /** @var mixed $var */
        $var = $this->returnInt();


        /** @var string $var */
        $var = $this->returnString();

        /** @var class-string $var */
        $var = $this->returnString(); // error: Invalid var phpdoc of $var. Cannot narrow string to class-string

        /** @var class-string $var allow-narrowing */
        $var = $this->returnString();

        /** @var string $var */
        $var = $this->returnNullableString(); // error: Invalid var phpdoc of $var. Cannot narrow string|null to string

        /** @var string $var allow-narrowing */
        $var = $this->returnNullableString();

        /** @var string|null|int $var */
        $var = $this->returnNullableString();


        /** @var array<ExampleInterface> $var */
        $var = $this->returnArrayOfSelf();

        /** @var array<self> $var */
        $var = $this->returnArrayOfSelf();

        /** @var array<object> $var */
        $var = $this->returnArrayOfSelf();

        /** @var array<mixed> $var */
        $var = $this->returnArrayOfSelf();

        /** @var array<int> $var */
        $var = $this->returnArrayOfSelf(); // error: Invalid var phpdoc of $var. Cannot assign array<ForbidAssignmentNotMatchingVarDocRule\NoNarrow\ExampleClass> to array<int>
    }

    /**
     * @return array<self>
     */
    public function returnArrayOfSelf(): array
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

    public function returnInterface(): ExampleInterface
    {
        return $this;
    }

}
