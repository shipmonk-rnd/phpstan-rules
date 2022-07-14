<?php declare(strict_types = 1);

enum SomeEnum: string {
    case Bar = 'bar';
    case Baz = 'baz';
}

$enums1 = [SomeEnum::Bar, SomeEnum::Baz];
$enums2 = [SomeEnum::Bar];
$enums3 = [SomeEnum::Baz];

array_intersect($enums1, $enums2, $enums3); // error: Arguments 1, 2, 3 in array_intersect() cannot be enum as the function causes implicit __toString conversion which is not supported for enums
array_intersect_assoc($enums1, $enums2, $enums3); // error: Arguments 1, 2, 3 in array_intersect_assoc() cannot be enum as the function causes implicit __toString conversion which is not supported for enums
array_diff($enums1, $enums2, $enums3); // error: Arguments 1, 2, 3 in array_diff() cannot be enum as the function causes implicit __toString conversion which is not supported for enums
array_diff_assoc($enums1, $enums2, $enums3); // error: Arguments 1, 2, 3 in array_diff_assoc() cannot be enum as the function causes implicit __toString conversion which is not supported for enums
array_unique($enums1); // error: Argument 1 in array_unique() cannot be enum as the function causes implicit __toString conversion which is not supported for enums
array_combine($enums2, $enums3); // error: Argument 1 in array_combine() cannot be enum as the function causes implicit __toString conversion which is not supported for enums
sort($enums1); // error: Argument 1 in sort() cannot be enum as the function causes unexpected results
asort($enums1); // error: Argument 1 in asort() cannot be enum as the function causes unexpected results
arsort($enums1); // error: Argument 1 in arsort() cannot be enum as the function causes unexpected results
natsort($enums1); // error: Argument 1 in natsort() cannot be enum as the function causes implicit __toString conversion which is not supported for enums
array_count_values($enums1); // error: Argument 1 in array_count_values() cannot be enum as the function will skip any enums and produce warning
array_fill_keys($enums1, 1); // error: Argument 1 in array_fill_keys() cannot be enum as the function causes implicit __toString conversion which is not supported for enums
array_flip($enums1); // error: Argument 1 in array_flip() cannot be enum as the function will skip any enums and produce warning
array_product($enums1); // error: Argument 1 in array_product() cannot be enum as the function causes unexpected results
array_sum($enums1); // error: Argument 1 in array_sum() cannot be enum as the function causes unexpected results
implode('', $enums1); // error: Argument 2 in implode() cannot be enum as the function causes implicit __toString conversion which is not supported for enums
in_array(SomeEnum::Bar, $enums1);
