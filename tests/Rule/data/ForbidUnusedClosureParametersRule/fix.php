<?php declare(strict_types = 1);

namespace ForbidUnusedClosureParametersRuleFix;

$one = fn (int $used, string $unused) => $used;
$two = fn (int $used, string $first, int $second) => $used;
$three = fn (int $first, int $second) => 1;
$four = function (int $used, string $unused): int {
    return $used;
};
$five = fn (int $unused, int $used) => $used;
