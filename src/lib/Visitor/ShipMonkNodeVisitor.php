<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Visitor;

interface ShipMonkNodeVisitor
{

    public const NODE_ATTRIBUTE_PREFIX = '_sm_'; // prevent collisions with visitors of other vendors

}
