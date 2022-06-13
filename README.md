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
