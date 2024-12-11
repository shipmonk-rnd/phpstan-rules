<?php

namespace EnforceIteratorToArrayPreserveKeysRule;

/**
 * Isolate the generator, so that PHPStan is not aware of the keys.
 */
function passThru(\Generator $iterable): \Generator {
    yield from $iterable;
}

$objectAsKey = function () {
    yield new \stdClass => 1;
};

$dataLoss = function () {
    yield 1 => 1;
    yield 1 => 2;
};

$noKeys = function () {
    yield 1;
    yield 2;
};


iterator_to_array(passThru($objectAsKey())); // error: Calling iterator_to_array without 2nd parameter $preserve_keys. Default value true might cause failures or data loss.
iterator_to_array(passThru($dataLoss())); // error: Calling iterator_to_array without 2nd parameter $preserve_keys. Default value true might cause failures or data loss.
iterator_to_array(passThru($noKeys())); // error: Calling iterator_to_array without 2nd parameter $preserve_keys. Default value true might cause failures or data loss.

iterator_to_array(passThru($objectAsKey()), false);
iterator_to_array(passThru($dataLoss()), false);
iterator_to_array(passThru($noKeys()), true);
iterator_to_array(... [passThru($noKeys()), true]);
iterator_to_array(
    preserve_keys: true,
    iterator: passThru($noKeys())
);
