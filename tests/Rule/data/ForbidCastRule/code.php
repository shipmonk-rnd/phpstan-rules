<?php

namespace ForbidCastRule;

function test(
    mixed $mixed,
) {
    (array) $mixed; // error: Using (array) is discouraged, please avoid using that.
    (object) $mixed; // error: Using (object) is discouraged, please avoid using that.
    (unset) $mixed; // error: Using (unset) is discouraged, please avoid using that.
    (int) $mixed;
    (string) $mixed;
    (float) $mixed;
    (bool) $mixed;
    (void) $mixed;
}
