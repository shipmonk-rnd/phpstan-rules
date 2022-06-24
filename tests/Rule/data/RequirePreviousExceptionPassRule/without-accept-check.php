<?php

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
    throw MyException::createForAnyPrevious(); // error: Exception $e not passed as previous to \MyException::createForAnyPrevious()
}

try {
} catch (RuntimeException $e) {
    throw MyException::createForSpecificPrevious(); // error: Exception $e not passed as previous to \MyException::createForSpecificPrevious()
}

try {
} catch (Throwable $e) {
    throw MyException::createWithoutPrevious(); // error: Exception $e not passed as previous to \MyException::createWithoutPrevious()
}

try {
} catch (LogicException $e) {
    throw MyException::createForSpecificPrevious(); // error: Exception $e not passed as previous to \MyException::createForSpecificPrevious()
}

try {
} catch (LogicException|RuntimeException $e) {
    throw MyException::createForSpecificPrevious(); // error: Exception $e not passed as previous to \MyException::createForSpecificPrevious()
}

