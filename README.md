# ShipMonk PHPStan rules
Various rules we found useful in ShipMonk.
You may found some of them opinionated, so we recommend picking only those fitting your needs.
If you would like to use all of them anyway, use:

```neon
includes:
    - vendor/shipmonk/phpstan-rules/rules.neon
```

## Installation:

```sh
composer require shipmonk/phpstan-rules
```

## Rules:

All you need to enable most of the rules is to register them [as documented in phpstan/phpstan](https://phpstan.org/developing-extensions/rules#registering-the-rule-in-the-configuration).
Some of them need some specific [rich parser node visitor](https://phpstan.org/blog/preprocessing-ast-for-custom-rules) to be registered as well.
Rarely, some rules are reliable only when some other rule is enabled.

### AllowComparingOnlyComparableTypesRule
- Denies using comparison operators `>,<,<=,>=,<=>` over anything other than int|string|float|DateTimeInterface. Null is not allowed.
- Mixing different types in those operators is also forbidden, only exception is comparing floats with integers
- Mainly targets to accidental comparisons of objects, enums or arrays which is valid in PHP, but very tricky

```neon
rules:
- ShipMonk\PHPStan\Rule\ForbidTrickyComparisonRule
```
```php
function example1(Money $fee1, Money $fee2) {
    if ($fee1 > $fee2) {} // comparing objects is denied
}

new DateTime() > '2040-01-02'; // comparing different types is denied
```

### AllowNamedArgumentOnlyInAttributesRule
- Allows usage of named arguments only in native attributes
- Before native attributes, we used [DisallowNamedArguments](https://github.com/slevomat/coding-standard#slevomatcodingstandardfunctionsdisallownamedarguments). But we used Doctrine annotations, which almost "require" named arguments when converted to native attributes.
- Requires NamedArgumentSourceVisitor to work
```neon
rules:
    - ShipMonk\PHPStan\Rule\AllowNamedArgumentOnlyInAttributesRule
services:
    -
    class: ShipMonk\PHPStan\Visitor\NamedArgumentSourceVisitor
    tags:
        - phpstan.parser.richParserNodeVisitor
```
```php
class User {
    #[Column(type: Types::STRING, nullable: false)] // allowed
    private string $email;

    public function __construct(string $email) {
        $this->setEmail(email: $email); // forbidden
    }
}
```

### ForbidFetchOnMixedRule
- Denies property fetch on unknown type.
- Any property fetch assumes the caller is an object with such property and therefore, the typehint/phpdoc should be fixed.
- Similar to `ForbidMethodCallOnMixedRule`
```neon
rules:
    - ShipMonk\PHPStan\Rule\ForbidFetchOnMixedRule
```
```php
function example($unknown) {
    $unknown->property; // cannot fetch property on mixed
}
```

### ForbidMatchDefaultArmForEnumsRule
- Denies using default arm in `match()` construct when native enum is passed as subject
- This rules makes sense only as a complement of [native phpstan rule](https://github.com/phpstan/phpstan-src/blob/1.7.x/src/Rules/Comparison/MatchExpressionRule.php#L94) that guards that all enum cases are handled in match arms
- As a result, you are forced to add new arm when new enum case is added
```neon
rules:
    - ShipMonk\PHPStan\Rule\ForbidFetchOnMixedRule
```
```php
match ($enum) {
    MyEnum::Case: 1;
    default: 2; // default arm forbidden
}
```

### ForbidMethodCallOnMixedRule
- Denies calling methods on unknown type.
- Any method call assumes the caller is an object with such method and therefore, the typehint/phpdoc should be fixed.
- Similar to `ForbidFetchOnMixedRule`
```neon
rules:
    - ShipMonk\PHPStan\Rule\ForbidMethodCallOnMixedRule
```
```php
function example($unknown) {
    $unknown->call(); // cannot call method on mixed
}
```

### ForbidUnsetClassFieldRule
- Denies calling `unset` over class field as it causes un-initialization, see https://3v4l.org/V8uuP
- Null assignment should be used instead
```neon
rules:
    - ShipMonk\PHPStan\Rule\ForbidUnsetClassFieldRule
```
```php
function example(MyClass $class) {
    unset($class->field); // denied
}
```

### ForbidUselessNullableReturnRule
- Denies marking method return type as nullable when null is never returned
- Recommended to be used together with `UselessPrivatePropertyDefaultValueRule` and `UselessPrivatePropertyNullabilityRule`
```neon
rules:
    - ShipMonk\PHPStan\Rule\ForbidUselessNullableReturnRule
```
```php
class Example {
    public function example(int $foo): ?int { // null never returned
        if ($foo < 0) {
            return 0;
        }
        return $foo;
    }
}
```

### ForbidUnusedExceptionRule
- Reports forgotten exception throw (created or returned from function, but not used in any way)
- Requires `UnusedExceptionVisitor` to work
```neon
rules:
    - ShipMonk\PHPStan\Rule\ForbidUnusedExceptionRule
services:
    -
    class: ShipMonk\PHPStan\Visitor\UnusedExceptionVisitor
    tags:
        - phpstan.parser.richParserNodeVisitor
```
```php
function validate(): void {
    new Exception(); // forgotten throw
}
```

### RequirePreviousExceptionPassRule

- Detects forgotten exception pass-as-previous when re-throwing
- Checks if caught exception can be passed as argument to the call (including constructor call) in `throw` node inside the catch block
- You may encounter false-positives in some edge-cases, where you do not want to pass exception as previous, feel free to ignore those

```neon
rules:
    - ShipMonk\PHPStan\Rule\RequirePreviousExceptionPassRule(
        checkRethrownExceptionAcceptsCaughtOne: true
    )
```
```php
try {
    // some code
} catch (RuntimeException $e) {
    throw new LogicException('Cannot happen'); // $e not passed as previous
}
```

- If you want to be even stricter, you can set up `checkRethrownExceptionAcceptsCaughtOne` to `false` and the rule will start reporting even cases where the thrown exception does not have parameter matching the caught exception
  - That will force you to add the parameter to be able to pass it as previous
  - Usable only if you do not throw exceptions from libraries, which is a good practice anyway

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

### UselessPrivatePropertyDefaultValueRule:

- Detects useless default value of a private property that is always initialized in constructor.
- Cannot handle conditions or private method calls within constructor.
- Requires `TopLevelConstructorPropertyFetchMarkingVisitor` to work
- Should be used together with `ForbidReturnInConstructorRule` to avoid false positives when return statement is used in constructor
- Recommended to be used with `UselessPrivatePropertyNullabilityRule` and `ForbidUselessNullableReturnRule`

```neon
rules:
    - ShipMonk\PHPStan\Rule\UselessPrivatePropertyDefaultValueRule
    - ShipMonk\PHPStan\Rule\ForbidReturnInConstructorRule
services:
    -
    class: ShipMonk\PHPStan\Visitor\TopLevelConstructorPropertyFetchMarkingVisitor
    tags:
        - phpstan.parser.richParserNodeVisitor
```
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

### UselessPrivatePropertyNullabilityRule:
- Detects useless nullability of a private property by checking type of all assignments.
- Requires `ClassPropertyAssignmentVisitor` to work
- Recommended to be used with `UselessPrivatePropertyNullabilityRule` and `ForbidUselessNullableReturnRule` as removing useless default value may cause useless nullability to be detected
```neon
rules:
    - ShipMonk\PHPStan\Rule\UselessPrivatePropertyNullabilityRule
services:
    -
    class: ShipMonk\PHPStan\Visitor\ClassPropertyAssignmentVisitor
    tags:
        - phpstan.parser.richParserNodeVisitor
```
```php
class Example
{
    private ?int $field; // useless nullability

    public function __construct()
    {
        $this->field = 1;
    }

    public function setField(int $value)
    {
        $this->field = $value;
    }
}
```
