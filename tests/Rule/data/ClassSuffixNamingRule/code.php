<?php declare(strict_types = 1);

namespace ClassSuffixNamingRule;

class CheckedParent {}
interface CheckedInterface {}

interface BadSuffixInterface extends CheckedInterface {} // error: Class name ClassSuffixNamingRule\BadSuffixInterface should end with Suffix2 suffix
interface GoodNameSuffix2 extends CheckedInterface {}

class Whatever {}
class BadSuffixClass extends CheckedParent {} // error: Class name ClassSuffixNamingRule\BadSuffixClass should end with Suffix suffix
class GoodNameSuffix extends CheckedParent {}

class InvalidConfigurationAlwaysGeneratesSomeError extends CheckedParent implements CheckedInterface {} // error: Class name ClassSuffixNamingRule\InvalidConfigurationAlwaysGeneratesSomeError should end with Suffix suffix
class InvalidConfigurationAlwaysGeneratesSomeErrorSuffix extends CheckedParent implements CheckedInterface {} // error: Class name ClassSuffixNamingRule\InvalidConfigurationAlwaysGeneratesSomeErrorSuffix should end with Suffix2 suffix
class InvalidConfigurationAlwaysGeneratesSomeErrorSuffix2 extends CheckedParent implements CheckedInterface {} // error: Class name ClassSuffixNamingRule\InvalidConfigurationAlwaysGeneratesSomeErrorSuffix2 should end with Suffix suffix

new class extends CheckedParent {

};
