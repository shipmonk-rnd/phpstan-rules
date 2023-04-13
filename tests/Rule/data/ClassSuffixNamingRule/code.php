<?php declare(strict_types = 1);

namespace ClassSuffixNamingRule;

class CheckedParent {}
class Whatever {}
class BadSuffixClass extends CheckedParent {} // error: Class name ClassSuffixNamingRule\BadSuffixClass should end with Suffix suffix
class GoodNameSuffix extends CheckedParent {}

new class extends CheckedParent {

};
