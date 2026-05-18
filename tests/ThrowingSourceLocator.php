<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan;

use LogicException;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;

class ThrowingSourceLocator implements SourceLocator
{

    public function locateIdentifier(
        Reflector $reflector,
        Identifier $identifier,
    ): ?Reflection // @phpstan-ignore return.internalInterface
    {
        throw new LogicException('Reflection must not be called during rule construction');
    }

    /**
     * @return list<Reflection>
     */
    public function locateIdentifiersByType(
        Reflector $reflector,
        IdentifierType $identifierType,
    ): array // @phpstan-ignore return.internalInterface
    {
        throw new LogicException('Reflection must not be called during rule construction');
    }

}
