<?php declare(strict_types = 1);

namespace ForbidUnusedExceptionRule;

use Exception;
use LogicException;
use RuntimeException;
use Throwable;

class ExampleClass
{

    public function __construct()
    {
        $this->getExceptionAtRuntime(); // error: Method $this->getExceptionAtRuntime() returns exception that was not used in any way.
        $this->getException(); // error: Method $this->getException() returns exception that was not used in any way.
        new Exception(); // error: Exception new \Exception() was not used in any way.

        $this->okUsage1();
        $this->okUsage2();
        $this->okUsage3();
        $this->okUsage4(new LogicException());
    }

    public function okUsage1(): void
    {
        throw new LogicException();
    }

    public function okUsage2(): void
    {
        throw self::getException();
    }

    public function okUsage3(): void
    {
        throw $this->getExceptionAtRuntime();
    }

    public function okUsage4(Throwable $throwable): void
    {
        $this->okUsage4($throwable);
    }

    public function okUsage5(bool $decide, ?Throwable $throwable): void
    {
        $this->okUsage5($decide, $decide ? new LogicException() : null);
    }

    public function okUsage6(): \Generator
    {
        yield new \OutOfBoundsException();
    }

    public function okUsage7(string $decide): void
    {
        $exception = match ($decide) {
            'foo' => new \OutOfRangeException(),
        };
    }

    public function okUsage8(): void
    {
        echo $this->getException()::class;
    }

    public function getExceptionAtRuntime(): RuntimeException
    {
        return new RuntimeException();
    }

    public static function getException(): RuntimeException
    {
        return new RuntimeException();
    }

}
