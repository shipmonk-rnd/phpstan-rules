<?php declare(strict_types = 1);

enum Foo: string {
    case Bar = 'bar';
    case Baz = 'baz';
}

$enums1 = [Foo::Bar, Foo::Baz];
$enums2 = [Foo::Bar];
$enums3 = [Foo::Baz];

array_intersect($enums1, $enums2, $enums3); // error: Arguments #1, #2, #3 to array_intersect() cannot be enum as the function causes implicit __toString conversion which is not supported for enums
array_intersect_assoc($enums1, $enums2, $enums3); // error: Arguments #1, #2, #3 to array_intersect_assoc() cannot be enum as the function causes implicit __toString conversion which is not supported for enums
array_diff($enums1, $enums2, $enums3); // error: Arguments #1, #2, #3 to array_diff() cannot be enum as the function causes implicit __toString conversion which is not supported for enums
array_diff_assoc($enums1, $enums2, $enums3); // error: Arguments #1, #2, #3 to array_diff_assoc() cannot be enum as the function causes implicit __toString conversion which is not supported for enums
array_unique($enums1); // error: Argument #1 to array_unique() cannot be enum as the function causes implicit __toString conversion which is not supported for enums
array_combine($enums2, $enums3); // error: Argument #1 to array_combine() cannot be enum as the function causes implicit __toString conversion which is not supported for enums
sort($enums1); // error: Argument #1 to sort() cannot be enum as the function causes unexpected results
asort($enums1); // error: Argument #1 to asort() cannot be enum as the function causes unexpected results
arsort($enums1); // error: Argument #1 to arsort() cannot be enum as the function causes unexpected results
natsort($enums1); // error: Argument #1 to natsort() cannot be enum as the function causes implicit __toString conversion which is not supported for enums
array_count_values($enums1); // error: Argument #1 to array_count_values() cannot be enum as the function will skip any enums and produce warning
array_fill_keys($enums1, 1); // error: Argument #1 to array_fill_keys() cannot be enum as the function causes implicit __toString conversion which is not supported for enums
array_flip($enums1); // error: Argument #1 to array_flip() cannot be enum as the function will skip any enums and produce warning
array_product($enums1); // error: Argument #1 to array_product() cannot be enum as the function causes unexpected results
array_sum($enums1); // error: Argument #1 to array_sum() cannot be enum as the function causes unexpected results
implode('', $enums1); // error: Argument #2 to implode() cannot be enum as the function causes implicit __toString conversion which is not supported for enums
in_array(Foo::Bar, $enums1);
