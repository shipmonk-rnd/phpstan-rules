<?php declare(strict_types = 1);

enum MyEnum: string {
    case MyCase1 = 'MyCase1';
    case MyCase2 = 'MyCase2';
}

$enum = MyEnum::MyCase1;

$result1 = match ($enum) {
    MyEnum::MyCase1 => 1,
    default => 2, // error: Default arm is denied for enums in match, list all values so that this case is raised when new enum case is added.
};

$result2 = match ($enum) {
    MyEnum::MyCase1 => 1,
    MyEnum::MyCase2 => 1,
};

$result3 = match ($enum) {
    MyEnum::MyCase1, MyEnum::MyCase2 => 1,
};

$result4 = match ($enum) {
    default => 1, // error: Default arm is denied for enums in match, list all values so that this case is raised when new enum case is added.
};

$result5 = match ($enum->value) {
    MyEnum::MyCase1->value => 1,
    default => 2,
};
