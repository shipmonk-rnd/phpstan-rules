<?php

namespace RequirePreviousExceptionPassRule;

use Exception;
use LogicException;
use RuntimeException;
use Throwable;

class MyException extends RuntimeException {

    public static function createForAnyPrevious(?Throwable $previous = null): self
    {
        return new self('My error', 0, $previous);
    }

    public static function createForSpecificPrevious(?RuntimeException $previous = null): self
    {
        return new self('My error', 0, $previous);
    }

    public static function createWithoutPrevious(): self
    {
        return new self('My error');
    }
}

try {
} catch (RuntimeException) {
    throw new LogicException('Message'); // error: Exception (RuntimeException) not passed as previous to new \LogicException('Message')
}

try {
} catch (RuntimeException $e) {
    throw new LogicException('Message', $e);
}

try {
} catch (Exception $e) {
    throw new LogicException('Message'); // error: Exception $e not passed as previous to new \LogicException('Message')
}

try {
} catch (LogicException $e) {
    throw MyException::createForAnyPrevious(); // error: Exception $e not passed as previous to \RequirePreviousExceptionPassRule\MyException::createForAnyPrevious()
}

try {
} catch (RuntimeException $e) {
    throw MyException::createForSpecificPrevious(); // error: Exception $e not passed as previous to \RequirePreviousExceptionPassRule\MyException::createForSpecificPrevious()
}

try {
} catch (Throwable $e) {
    throw MyException::createWithoutPrevious();
}

try {
} catch (LogicException $e) {
    throw MyException::createForSpecificPrevious();
}

try {
} catch (LogicException|RuntimeException $e) {
    throw MyException::createForSpecificPrevious(); // runtime is passable, but logic not, no error here
}

try {
} catch (LogicException $e) {
} catch (LogicException|RuntimeException $e) { // Logic can never occur here, so Runtime must be passed
    throw MyException::createForSpecificPrevious(); // error: Exception $e not passed as previous to \RequirePreviousExceptionPassRule\MyException::createForSpecificPrevious()
}
