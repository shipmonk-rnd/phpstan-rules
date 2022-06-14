<?php declare(strict_types = 1);

class ExampleClass
{

    public function __construct()
    {
        $this->getExceptionAtRuntime(); // error: Method $this->getExceptionAtRuntime() returns exception that was not used in any way.
        $this->getException(); // error: Method $this->getException() returns exception that was not used in any way.
        new \Exception(); // error: Exception new \Exception() was not used in any way.
    }

    public function okUsage1(): void
    {
        throw new \LogicException();
    }

    public function okUsage2(): void
    {
        throw self::getException();
    }

    public function okUsage3(): void
    {
        throw $this->getExceptionAtRuntime();
    }

    public function getExceptionAtRuntime(): \RuntimeException
    {
        return new \RuntimeException();
    }

    public static function getException(): \RuntimeException
    {
        return new \RuntimeException();
    }

}
