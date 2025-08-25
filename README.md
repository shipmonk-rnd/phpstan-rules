# ShipMonk PHPStan strict rules
About **40 super-strict rules** we found useful in ShipMonk.
We tend to have PHPStan set up [as strict as possible](#native-phpstan-extra-strictness), but that still was not strict enough for us.
This set of rules should fill the missing gaps we found.

If you find some rules opinionated, you can easily disable them.

## Installation:

```sh
composer require --dev shipmonk/phpstan-rules
```

Use [official extension-installer](https://phpstan.org/user-guide/extension-library#installing-extensions) or enable all rules manually by:
```neon
# phpstan.neon
includes:
    - vendor/shipmonk/phpstan-rules/rules.neon
```

## Configuration:

You can easily disable or reconfigure any rule, for example:
```neon
parameters:
    shipmonkRules:
        enforceReadonlyPublicProperty:
            enabled: false
        forbidUnsafeArrayKey:
            reportMixed: false
```

Or you can disable all rules and enable only those you want:
```neon
parameters:
    shipmonkRules:
        enableAllRules: false

        allowComparingOnlyComparableTypes:
            enabled: true
```

When you try to configure any default array, PHPStan config is **merged by default**,
so if you want to enforce only your values and not to include our defaults, use [exclamation mark](https://doc.nette.org/en/dependency-injection/configuration#toc-merging):

```neon
parameters:
    shipmonkRules:
        forbidCast:
            enabled: true
            blacklist!: ['(unset)'] # force the blacklist to be only (unset)
```

Few rules are enabled, but do nothing unless configured, those are marked with `*`.

## Rules:

### allowComparingOnlyComparableTypes
- Denies using comparison operators `>,<,<=,>=,<=>` over anything other than `int|string|float|DateTimeInterface` or same size tuples containing comparable types. Null is not allowed.
- Mixing different types in those operators is also forbidden, only exception is comparing floats with integers
- Mainly targets to accidental comparisons of objects, enums or arrays which is valid in PHP, but very tricky

```php
function example1(Money $fee1, Money $fee2) {
    if ($fee1 > $fee2) {} // comparing objects is denied
}

new DateTime() > '2040-01-02'; // comparing different types is denied
200 > '1e2'; // comparing different types is denied
```

### backedEnumGenerics *
- Ensures that every BackedEnum child defines generic type
- This rule makes sense only when BackedEnum was hacked to be generic by stub as described in [this article](https://rnd.shipmonk.com/hacking-generics-into-backedenum-in-php-8-1/)
  - This rule does nothing if BackedEnum is not set to be generic, which is a default setup. Use following config to really start using it:
```neon
parameters:
    stubFiles:
        - BackedEnum.php.stub # see article or BackedEnumGenericsRuleTest
    ignoreErrors:
        - '#^Enum .*? has @implements tag, but does not implement any interface.$#'
```
```php
enum MyEnum: string { // missing @implements tag
    case MyCase = 'case1';
}
```

### classSuffixNaming *
- Allows you to enforce class name suffix for subclasses of configured superclass
- Checks nothing by default, configure it by passing `superclass => suffix` mapping
- Passed superclass is not expected to have such suffix, only subclasses are
- You can use interface as superclass

```neon
    shipmonkRules:
        classSuffixNaming:
            superclassToSuffixMapping!:
                \Exception: Exception
                \PHPStan\Rules\Rule: Rule
                \PHPUnit\Framework\TestCase: Test
                \Symfony\Component\Console\Command\Command: Command
```


### enforceClosureParamNativeTypehint
- Enforces usage of native typehints for closure & arrow function parameters
- Does nothing on PHP 7.4 and below as native `mixed` is not available there
- Can be configured by `allowMissingTypeWhenInferred: true` to allow missing typehint when it can be inferred from the context

```php
/**
 * @param list<Entity> $entities
 * @return list<Uuid>
 */
public function getIds(array $entities): array {
    return array_map(
        function ($entity) { // missing native typehint; not reported with allowMissingTypeWhenInferred: true
            return $entity->id;
        },
        $entities
    );
}
```


### enforceEnumMatchRule
- Enforces usage of `match ($enum)` instead of exhaustive conditions like `if ($enum === Enum::One) elseif ($enum === Enum::Two)`
- This rule aims to "fix" a bit problematic behaviour of PHPStan (introduced at 1.10.0 and fixed in [1.10.34](https://github.com/phpstan/phpstan-src/commit/fc7c0283176e5dc3867ade26ac835ee7f52599a9)). It understands enum cases very well and forces you to adjust following code:
```php
enum MyEnum {
    case Foo;
    case Bar;
}

if ($enum === MyEnum::Foo) {
    // ...
} elseif ($enum === MyEnum::Bar) { // always true reported by phpstan (for versions 1.10.0 - 1.10.34)
    // ...
} else {
    throw new LogicException('Unknown case'); // phpstan knows it cannot happen
}
```
Which someone might fix as:
```php
if ($enum === MyEnum::Foo) {
    // ...
} elseif ($enum === MyEnum::Bar) {
    // ...
}
```
Or even worse as:
```php
if ($enum === MyEnum::Foo) {
    // ...
} else {
    // ...
}
```

We believe that this leads to more error-prone code since adding new enum case may not fail in tests.
Very good approach within similar cases is to use `match` construct so that (ideally with `forbidMatchDefaultArmForEnums` enabled) phpstan fails once new case is added.
PHPStan even adds tip about `match` in those cases since `1.10.11`.
For those reasons, this rule detects any always-true/false enum comparisons and forces you to rewrite it to `match ($enum)`.

Since PHPStan [1.10.34](https://github.com/phpstan/phpstan-src/commit/fc7c0283176e5dc3867ade26ac835ee7f52599a9), the behaviour is much better as it does not report error on the last elseif in case that it is followed by else with thrown exception.
Such case raises exception in your tests if you add new enum case, but it is [still silent in PHPStan](https://phpstan.org/r/a4fdc0ab-5d1e-4f38-80ab-8da2e71a6205). This leaves space for error being deployed to production.
So we still believe this rule makes sense even in latest PHPStan.

### enforceIteratorToArrayPreserveKeys
- Enforces presence of second parameter in [iterator_to_array](https://www.php.net/manual/en/function.iterator-to-array.php) call (`$preserve_keys`) as the default value `true` is generally dangerous (risk of data loss / failure)
- You can use both `true` and `false` there, but doing so is intentional choice now

```php
$fn = function () {
    yield new stdClass => 1;
};

iterator_to_array($fn()); // denied, would fail
```


### enforceListReturn
- Enforces usage of `list<T>` when list is always returned from a class method or function
- When only single return with empty array is present in the method, it is not considered as list
- Does nothing when [list types](https://phpstan.org/blog/phpstan-1-9-0-with-phpdoc-asserts-list-type#list-type) are disabled in PHPStan
- Consider enabling also [reportAnyTypeWideningInVarTag](https://phpstan.org/config-reference#reportanytypewideninginvartag) in native PHPStan as it mostly affects lists
```php
/**
 * @return array<string>
 */
public function returnList(): array // error, return phpdoc is generic array, but list is always returned
{
    return ['item'];
}
```

### enforceNativeReturnTypehint
- Enforces usage of native return typehints if supported by your PHP version
- If PHPDoc is present, it deduces needed typehint from that, if not, deduction is performed based on real types returned
- Applies to class methods, closures and functions
- Is disabled, if you have PHPStan set up with `treatPhpDocTypesAsCertain: false`
- Limitations:
  - Does not suggest parent typehint
  - Ignores trait methods
```php
class NoNativeReturnTypehint {
    /**
     * @return list<string>
     */
    public function returnList() // error, missing array typehint
    {
        return ['item'];
    }
}
```

### enforceReadonlyPublicProperty
- Ensures immutability of all public properties by enforcing `readonly` modifier
- No modifier needed for readonly classes in PHP 8.2
- Does nothing if PHP version does not support readonly properties (PHP 8.0 and below)
```php
class EnforceReadonlyPublicPropertyRule {
    public int $foo; // fails, no readonly modifier
    public readonly int $bar;
}
```

### forbidArithmeticOperationOnNonNumber
- Disallows using [arithmetic operators](https://www.php.net/manual/en/language.operators.arithmetic.php) with non-numeric types (only float and int is allowed)
- You can allow numeric-string by using `allowNumericString: true` configuration
- Modulo operator (`%`) allows only integers as it [emits deprecation otherwise](https://3v4l.org/VpVoq)
- Plus operator is allowed for merging arrays

```php
function add(string $a, string $b) {
    return $a + $b; // denied, non-numeric types are allowed
}
```

### forbidCast
- Deny casting you configure
- Possible values to use:
  - `(array)` - denied by default
  - `(object)` - denied by default
  - `(unset)` - denied by default
  - `(bool)`
  - `(int)`
  - `(string)`
  - `(float)` - forbids using also `(double)` and `(real)`

```php
$empty = (array) null; // denied cast
$notEmpty = (array) 0; // denied cast
```
```neon
parameters:
    shipmonkRules:
        forbidCast:
            blacklist!: ['(array)', '(object)', '(unset)']
```

### forbidCheckedExceptionInCallable
- Denies throwing [checked exception](https://phpstan.org/blog/bring-your-exceptions-under-control) in callables (Closures, Arrow functions and First class callables) as those cannot be tracked as checked by PHPStan analysis, because it is unknown when the callable is about to be called
- It is allowed to throw checked exceptions in immediately called callables (e.g. params marked by `@param-immediately-invoked-callable`, see [docs](https://phpstan.org/writing-php-code/phpdocs-basics#callables))
- It allows configuration of functions/methods, where the callable is handling all thrown exceptions and it is safe to throw anything from there; this basically makes such calls ignored by this rule
- It ignores [implicitly thrown Throwable](https://phpstan.org/blog/bring-your-exceptions-under-control#what-does-absent-%40throws-above-a-function-mean%3F)
- Learn more in 🇨🇿 [talk about checked exceptions in general](https://www.youtube.com/watch?v=UQsP1U0sVZM) (🇺🇸 [slides](https://docs.google.com/presentation/d/17gfiWgCewU8F9bFXmKW2qXqvKbAF5cqIoFELfw8b0Cc/edit#slide=id.g14a99d9699a_0_356))

```neon
parameters:
    shipmonkRules:
        forbidCheckedExceptionInCallable:
            allowedCheckedExceptionCallables:
                'Symfony\Component\Console\Question::setValidator': 0 # symfony automatically converts all thrown exceptions to error output, so it is safe to throw anything here
```

- We recommend using following config for checked exceptions:
  - Also, [bleedingEdge](https://phpstan.org/blog/what-is-bleeding-edge) enables proper analysis of dead types in multi-catch, so we recommend enabling even that

```neon
parameters:
    exceptions:
        check:
            missingCheckedExceptionInThrows: true # enforce checked exceptions to be stated in @throws
            tooWideThrowType: true # report invalid @throws (exceptions that are not actually thrown in annotated method)
        implicitThrows: false # no @throws means nothing is thrown (otherwise Throwable is thrown)
        checkedExceptionClasses:
            - YourApp\TopLevelRuntimeException # track only your exceptions (children of some, typically RuntimeException)
```


```php
class TransactionManager {
    /**
     * @param-immediately-invoked-callable $callback
     */
    public function transactional(callable $callback): void {
        // ...
        $callback();
        // ...
    }
}

class UserEditFacade
{
    /**
     * @throws UserNotFoundException
     */
    public function updateUserEmail(UserId $userId, Email $email): void
    {
        $this->transactionManager->transactional(function () use ($userId, $email) {
            $user = $this->userRepository->get($userId); // can throw checked UserNotFoundException
            $user->updateEmail($email);
        })
    }

    public function getUpdateEmailCallback(UserId $userId, Email $email): callable
    {
        return function () use ($userId, $email) {
            $user = $this->userRepository->get($userId); // this usage is denied, it throws checked exception, but you don't know when, thus it cannot be tracked by phpstan
            $user->updateEmail($email);
        };
    }
}
```

### forbidCheckedExceptionInYieldingMethod
- Denies throwing [checked exception](https://phpstan.org/blog/bring-your-exceptions-under-control) within yielding methods as those exceptions are not throw upon method call, but when generator gets iterated.
- This behaviour cannot be easily reflected within PHPStan exception analysis and may cause [false negatives](https://phpstan.org/r/d07ac0f0-a49d-4f82-b1dd-1939058bbeed).
- Make sure you have enabled checked exceptions, otherwise, this rule does nothing

```php
class Provider {
    /** @throws CheckedException */
    public static function generate(): iterable
    {
        yield 1;
        throw new CheckedException(); // denied, gets thrown once iterated
    }
}
```

### forbidCustomFunctions *
- Allows you to easily deny some approaches within your codebase by denying classes, methods and functions
- Configuration syntax is array where key is method name and value is reason used in error message
- Works even with interfaces, constructors and some dynamic class/method names like `$fn = 'sleep'; $fn();`
```neon
parameters:
    shipmonkRules:
        forbidCustomFunctions:
            list:
                'Namespace\SomeClass::*': 'Please use different class' # deny all methods by using * (including constructor)
                'Namespace\AnotherClass::someMethod': 'Please use anotherMethod' # deny single method
                'var_dump': 'Please remove debug code' # deny function
```
```php
new SomeClass(); // Class SomeClass is forbidden. Please use different class
(new AnotherClass())->someMethod(); // Method AnotherClass::someMethod() is forbidden. Please use anotherMethod
```

### forbidEnumInFunctionArguments
- Guards passing native enums to native functions where it fails / produces warning or does unexpected behaviour
- Most of the array manipulation functions does not work with enums as they do implicit __toString conversion inside, but that is not possible to do with enums
- [See test](https://github.com/shipmonk-rnd/phpstan-rules/blob/master/tests/Rule/data/ForbidEnumInFunctionArgumentsRule/code.php) for all functions and their problems
```php
enum MyEnum: string {
    case MyCase = 'case1';
}

implode('', [MyEnum::MyCase]); // denied, would fail on implicit toString conversion
```


### forbidFetchOnMixed
- Denies constant/property fetch on unknown type.
- Any property fetch assumes the caller is an object with such property and therefore, the typehint/phpdoc should be fixed.
- Similar to `forbidMethodCallOnMixed`
- Makes sense only on PHPStan level 8 or below, gets autodisabled on level 9
```php
function example($unknown) {
    $unknown->property; // cannot fetch property on mixed
}
```

### forbidIdenticalClassComparison
- Denies comparing configured classes by `===` or `!==`
- Default configuration contains only `DateTimeInterface`
- You may want to add more classes from your codebase or vendor

```php
function isEqual(DateTimeImmutable $a, DateTimeImmutable $b): bool {
    return $a === $b;  // comparing denied classes
}
```
```neon
parameters:
    shipmonkRules:
        forbidIdenticalClassComparison:
            blacklist!:
                - DateTimeInterface
                - Brick\Money\MoneyContainer
                - Brick\Math\BigNumber
                - Ramsey\Uuid\UuidInterface
```

### forbidIncrementDecrementOnNonInteger
- Denies using `$i++`, `$i--`, `++$i`, `--$i` with any non-integer
- PHP itself is leading towards stricter behaviour here and soft-deprecated **some** non-integer usages in 8.3, see [RFC](https://wiki.php.net/rfc/saner-inc-dec-operators)

```php
$value = '2e0';
$value++; // would be float(3), denied
```

### forbidMatchDefaultArmForEnums
- Denies using default arm in `match()` construct when native enum is passed as subject
- This rules makes sense only as a complement of [native phpstan rule](https://github.com/phpstan/phpstan-src/blob/1.7.x/src/Rules/Comparison/MatchExpressionRule.php#L94) that guards that all enum cases are handled in match arms
- As a result, you are forced to add new arm when new enum case is added. That brings up all the places in your codebase that needs new handling.
```php
match ($enum) {
    MyEnum::Case: 1;
    default: 2; // default arm forbidden
}
```

### forbidMethodCallOnMixed
- Denies calling methods on unknown type.
- Any method call assumes the caller is an object with such method and therefore, the typehint/phpdoc should be fixed.
- Similar to `forbidFetchOnMixed`
- Makes sense only on PHPStan level 8 or below, gets autodisabled on level 9
```php
function example($unknown) {
    $unknown->call(); // cannot call method on mixed
}
```

### forbidNotNormalizedType
- Reports PhpDoc or native type that is not normalized, which can be:
  - when child and parent appears in its union or intersection
  - when same type appears multiple times in its union or intersection
  - when DNF is not used
    - configurable by `checkDisjunctiveNormalForm`
  - supports:
    - parameter typehints & `@param` phpdoc
    - return typehint & `@return` phpdoc
    - property typehint & `@var` phpdoc
    - inline `@var` phpdoc
    - `@throws` phpdoc
    - multi-catch statements
- Main motivation here is that PHPStan normalizes all types before analysis, so it is better to see it in codebase the same way PHPStan does

```php
/**
 * @return mixed|false   // denied, this is still just mixed
 */
public function getAttribute(string $name)
{
    return $this->attributes[$name] ?? false;
}
```

### forbidNullInAssignOperations
- Denies using [assign operators](https://www.php.net/manual/en/language.operators.assignment.php) if null is involved on right side
- You can configure which operators are ignored, by default only `??=` is excluded
```php
function getCost(int $cost, ?int $surcharge): int {
    $cost += $surcharge;  // denied, adding possibly-null value
    return $cost;
}
```


### forbidNullInBinaryOperations
- Denies using binary operators if null is involved on either side
- You can configure which operators are ignored. Default ignore is excluding only `===, !==, ??`
- Following custom setup is recommended when using latest [phpstan-strict-rules](https://github.com/phpstan/phpstan-strict-rules) and `allowComparingOnlyComparableTypes` is enabled
```neon
parameters:
    shipmonkRules:
        forbidNullInBinaryOperations:
            blacklist!: [
                '**', '!=', '==', '+', 'and', 'or', '&&', '||', '%', '-', '/', '*', # checked by phpstan-strict-rules
                '>', '>=', '<', '<=', '<=>', # checked by AllowComparingOnlyComparableTypesRule
                '===', '!==', '??' # valid with null involved
            ]
```
```php
function getFullName(?string $firstName, string $lastName): string {
    return $firstName . ' ' . $lastName; // denied, null involved in binary operation
}
```

### forbidNullInInterpolatedString
- Disallows using nullable expressions within double-quoted strings
- This should probably comply with setup of concat operator (`.`) in `forbidNullInBinaryOperations` so if you blacklisted it there, you might want to disable this rule
```php
public function output(?string $name) {
    echo "Hello $name!"; // denied, possibly null value
}
```

### forbidPhpDocNullabilityMismatchWithNativeTypehint
- Disallows having nullable native typehint while using non-nullable phpdoc
- Checks `@return` and `@param` over methods and `@var` over properties
- PHPStan itself allows using subtype of native type in phpdoc, but [resolves overall type as union of those types](https://phpstan.org/r/6f447c03-d79b-4731-b8c8-125eab3e56fc) making such phpdoc actually invalid

```php
/**
 * @param string $param
 */
public function sayHello(?string $param) {} // invalid phpdoc not containing null
```


### forbidProtectedEnumMethod
- Disallows protected method within enums as those are not extendable anyway
- Ignore method declared in traits as those might be reused in regular classes

```php
enum MyEnum {
    protected function isOpen(): bool {} // protected enum method denied
}
```

### forbidReturnValueInYieldingMethod
- Disallows returning values in yielding methods unless marked to return Generator as the value is accessible only via [Generator::getReturn](https://www.php.net/manual/en/generator.getreturn.php)
- To prevent misuse, this rule can be configured to even stricter mode where it reports such returns regardless of return type declared

```php
class Get {
    public static function oneTwoThree(): iterable { // marked as iterable, caller cannot access the return value by Generator::getReturn
        yield 1;
        yield 2;
        return 3;
    }
}

iterator_to_array(Get::oneTwoThree()); // [1, 2] - see https://3v4l.org/Leu9j
```
```neon
parameters:
    shipmonkRules:
        forbidReturnValueInYieldingMethod:
            reportRegardlessOfReturnType: true # optional stricter mode, defaults to false
```


### forbidUnsafeArrayKey
- Denies non-int non-string array keys
- PHP casts `null`, `float` and `bool` to some nearest int/string
  - You should rather do that intentionally and explicitly
  - Those types are the main difference to default PHPStan behaviour which allows using them as array keys
- You can exclude reporting `mixed` keys via `reportMixed` configuration
- You can exclude reporting `isset($array[$invalid])` and `$array[$invalid] ?? null` via `reportInsideIsset` configuration

```php
$taxRates = [ // denied, float key gets casted to int (causing $taxRates to contain one item)
    1.15 => 'reduced',
    1.21 => 'standard',
];
```

```neon
parameters:
    shipmonkRules:
        forbidUnsafeArrayKey:
            reportMixed: false # defaults to true
            reportInsideIsset: false # defaults to true
```


### forbidVariableTypeOverwriting
- Restricts variable assignment to those that does not change its type
  - Array append `$array[] = 1;` not yet supported
- Null and mixed are not taken into account, advanced phpstan types like non-empty-X are trimmed before comparison
- Rule allows type generalization and type narrowing (parent <-> child)
```php
function example(OrderId $id) {
    $id = $id->getStringValue(); // denied, type changed from object to string
}
```

### forbidUnsetClassField
- Denies calling `unset` over class field as it causes un-initialization, see https://3v4l.org/V8uuP
- Null assignment should be used instead
```php
function example(MyClass $class) {
    unset($class->field); // denied
}
```

### forbidUselessNullableReturn
- Denies marking closure/function/method return type as nullable when null is never returned
- Recommended to be used together with `uselessPrivatePropertyDefaultValue`
```php
public function example(int $foo): ?int { // null never returned
    if ($foo < 0) {
        return 0;
    }
    return $foo;
}
```

### forbidUnusedException
- Reports forgotten exception throw (created or returned from function, but not used in any way)
```php
function validate(): void {
    new Exception(); // forgotten throw
}
```


### forbidUnusedMatchResult
- Reports forgotten usage of match result
- Any `match` with at least one arm returning a value is checked
```php
match ($foo) { // unused match result
    case 'foo' => 1;
}
```


### forbidUnusedClosureParameters
- Reports unused parameters in closures and arrow functions
- Only reports trailing unused parameters (parameters that are unused and all parameters after them are also unused)
```php
fn (int $key, Item $item, string $unused) => $item->ok(); // unused parameter $unused is reported, $key not
```


### requirePreviousExceptionPass
- Detects forgotten exception pass-as-previous when re-throwing
- Checks if caught exception can be passed as argument to the call (including constructor call) in `throw` node inside the catch block
- You may encounter false-positives in some edge-cases, where you do not want to pass exception as previous, feel free to ignore those

```php
try {
    // some code
} catch (RuntimeException $e) {
    throw new LogicException('Cannot happen'); // $e not passed as previous
}
```

- If you want to be even stricter, you can set up `reportEvenIfExceptionIsNotAcceptableByRethrownOne` to `true` and the rule will start reporting even cases where the thrown exception does not have parameter matching the caught exception
  - Defaults to true
  - That will force you to add the parameter to be able to pass it as previous
  - Usable only if you do not throw exceptions from libraries, which is a good practice anyway

```neon
parameters:
    shipmonkRules:
        requirePreviousExceptionPass:
            reportEvenIfExceptionIsNotAcceptableByRethrownOne: true
```
```php
class MyException extends RuntimeException {
    public function __construct() {
        parent::__construct('My error');
    }
}

try {
    // some code
} catch (RuntimeException $e) {
    throw new MyException(); // reported even though MyException cannot accept it yet
}
```

### uselessPrivatePropertyDefaultValue:

- Detects useless default value of a private property that is always initialized in constructor.
- Cannot handle conditions or private method calls within constructor.
- When enabled, return statements in constructors are denied to avoid false positives
- Recommended to be used with `forbidUselessNullableReturn`
```php
class Example
{
    private ?int $field = null; // useless default value

    public function __construct()
    {
        $this->field = 1;
    }
}
```

## Native PHPStan extra strictness
Some strict behaviour in PHPStan is not enabled by default, consider enabling extra strictness even there:

```neon
includes:
    - phar://phpstan.phar/conf/config.levelmax.neon
    - phar://phpstan.phar/conf/bleedingEdge.neon      # https://phpstan.org/blog/what-is-bleeding-edge
    - vendor/phpstan/phpstan-strict-rules/rules.neon  # https://github.com/phpstan/phpstan-strict-rules
parameters:
    checkImplicitMixed: true                                  # https://phpstan.org/config-reference#checkimplicitmixed
    checkBenevolentUnionTypes: true                           # https://phpstan.org/config-reference#checkbenevolentuniontypes
    checkUninitializedProperties: true                        # https://phpstan.org/config-reference#checkuninitializedproperties
    checkMissingCallableSignature: true                       # https://phpstan.org/config-reference#vague-typehints
    checkTooWideReturnTypesInProtectedAndPublicMethods: true  # https://phpstan.org/config-reference#checktoowidereturntypesinprotectedandpublicmethods
    reportAnyTypeWideningInVarTag: true                       # https://phpstan.org/config-reference#reportanytypewideninginvartag
    reportPossiblyNonexistentConstantArrayOffset: true        # https://phpstan.org/config-reference#reportpossiblynonexistentconstantarrayoffset
    reportPossiblyNonexistentGeneralArrayOffset: true         # https://phpstan.org/config-reference#reportpossiblynonexistentgeneralarrayoffset
```

## Contributing
- Check your code by `composer check`
- Autofix coding-style by `composer fix:cs`
- All functionality must be tested
