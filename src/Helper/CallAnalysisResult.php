<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Helper;

use PhpParser\Node\Arg;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Type;

class CallAnalysisResult
{

    /**
     * @var FunctionReflection|MethodReflection
     */
    public object $reflection;

    public ?Type $callerType;

    /**
     * @var list<Arg>
     */
    public array $reorderedArguments;

    /**
     * @var array<string, true>
     */
    public array $immediatelyInvokedHashes;

    /**
     * @param FunctionReflection|MethodReflection $reflection
     * @param list<Arg> $reorderedArguments
     * @param array<string, true> $immediatelyInvokedHashes
     */
    public function __construct(
        object $reflection,
        ?Type $callerType,
        array $reorderedArguments,
        array $immediatelyInvokedHashes
    )
    {
        $this->reflection = $reflection;
        $this->callerType = $callerType;
        $this->reorderedArguments = $reorderedArguments;
        $this->immediatelyInvokedHashes = $immediatelyInvokedHashes;
    }

}
