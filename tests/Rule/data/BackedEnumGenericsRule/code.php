<?php

namespace BackedEnumGenericsRule;

use BackedEnum;

/**
 * @implements BackedEnum<string>
 */
enum MyStringEnum: string {
}

/**
 * @implements BackedEnum<int>
 */
enum MyIntEnum: int {
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
