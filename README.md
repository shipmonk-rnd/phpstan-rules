# ShipMonk PHPStan rules
Various rules we found useful in ShipMonk.

## Installation:

```sh
composer require shipmonk/phpstan-rules
```

## Rules:

All you need to enable most of the rules is to register them [as documented in phpstan/phpstan](https://phpstan.org/developing-extensions/rules#registering-the-rule-in-the-configuration).
Some of them need some specific [rich parser node visitor](https://phpstan.org/blog/preprocessing-ast-for-custom-rules) to be registered as well.
Rarely, some rules are reliable only when some other rule is enabled.

### ForbidFetchOnMixedRule
- Denies property fetch on unknown type.
- Any property fetch assumes the caller is an object with such property and therefore, the typehint/phpdoc should be fixed.
```neon
rules:
    - ShipMonk\PHPStan\Rule\ForbidFetchOnMixedRule
```
```php
function example($unknown) {
    $unknown->property; // cannot fetch property on mixed
}
```

### ForbidMethodCallOnMixedRule
- Denies calling methods on unknown type.
- Any method call assumes the caller is an object with such method and therefore, the typehint/phpdoc should be fixed.
```neon
rules:
    - ShipMonk\PHPStan\Rule\ForbidMethodCallOnMixedRule
```
```php
function example($unknown) {
    $unknown->call(); // cannot call method on mixed
}
```

### UnsetClassFieldRule
- Denies calling `unset` over class field as it causes un-initialization, see https://3v4l.org/V8uuP
- Null assignment should be used instead
```neon
rules:
    - ShipMonk\PHPStan\Rule\UnsetClassFieldRule
```
```php
function example(MyClass $class) {
    unset($class->field); // denied
}
```

### UselessPrivatePropertyDefaultValueRule:

- Detects useless default value of a private property that is always initialized in constructor.
- Cannot handle conditions or private method calls within constructor.
- Requires `TopLevelConstructorPropertyFetchMarkingVisitor` to work
- Should be used together with `ForbidReturnInConstructorRule` to avoid false positives when return statement is used in constructor
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
- Recommended to be used with `UselessPrivatePropertyNullabilityRule` as removing useless default value may cause useless nullability to be detected
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