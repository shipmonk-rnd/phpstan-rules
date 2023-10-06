<?php

namespace EnforceIteratorToArrayPreserveKeysRule;

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


iterator_to_array($objectAsKey()); // error: Calling iterator_to_array without 2nd parameter $preserve_keys. Default value true might cause failures or data loss.
iterator_to_array($dataLoss()); // error: Calling iterator_to_array without 2nd parameter $preserve_keys. Default value true might cause failures or data loss.
iterator_to_array($noKeys()); // error: Calling iterator_to_array without 2nd parameter $preserve_keys. Default value true might cause failures or data loss.

iterator_to_array($objectAsKey(), false);
iterator_to_array($dataLoss(), false);
iterator_to_array($noKeys(), true);
iterator_to_array(... [$noKeys(), true]);
iterator_to_array(
    preserve_keys: true,
    iterator: $noKeys()
);
