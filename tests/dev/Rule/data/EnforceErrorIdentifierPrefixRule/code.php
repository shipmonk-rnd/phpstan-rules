<?php declare(strict_types = 1);

use PHPStan\Rules\RuleErrorBuilder;

$identifier1 = 'shipmonk.foo';
$identifier2 = 'foo';

RuleErrorBuilder::message('')->identifier('shipmonk.foo')->build();
RuleErrorBuilder::message('')->identifier('foo')->build(); // error: Error identifier 'foo' is not prefixed with 'shipmonk.'
RuleErrorBuilder::message('')->identifier($identifier1)->build();
RuleErrorBuilder::message('')->identifier($identifier2)->build(); // error: Error identifier 'foo' is not prefixed with 'shipmonk.'
RuleErrorBuilder::message('')->identifier()->build();
RuleErrorBuilder::message('')->build();

$mixed->identifier('');
