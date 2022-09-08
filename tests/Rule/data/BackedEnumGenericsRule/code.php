<?php

namespace BackedEnumGenericsRule;

use BackedEnum;

/**
 * @implements BackedEnum<string>
 */
enum MyStringEnum: string {
}

/**
 * @implements BackedEnum<'a'|'b'>
 */
enum MyStrictStringEnum: string { // error: Enum case C ('c') does not match declared type 'a'|'b'
    case A = 'a';
    case B = 'b';
    case C = 'c';
}

/**
 * @implements BackedEnum<int>
 */
enum MyIntEnum: int {
}

/**
 * @implements BackedEnum<positive-int>
 */
enum MyPositiveIntEnum: int { // error: Enum case NEGATIVE (-1) does not match declared type int<1, max>
    case POSITIVE = 1;
    case NEGATIVE = -1;
}

/**
 * @extends BackedEnum<int>
 */
interface MyBackedEnum extends BackedEnum {

}

enum MyIntEnumWithoutImplements: int { // error: Class BackedEnumGenericsRule\MyIntEnumWithoutImplements extends generic BackedEnum, but does not specify its type. Use @implements BackedEnum<int>
}

enum MyIntEnumWithImplementsInParent: int implements MyBackedEnum {
}
