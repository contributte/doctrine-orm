<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Nette\DI\Definitions\Statement;
use Nettrine\ORM\DI\Helpers\SmartStatement;
use Nettrine\ORM\Exception\LogicalException;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

// Convert string to Statement
Toolkit::test(function (): void {
	$result = SmartStatement::from('SomeClass');

	Assert::type(Statement::class, $result);
	Assert::equal('SomeClass', $result->getEntity());
});

// Return Statement as-is
Toolkit::test(function (): void {
	$statement = new Statement('SomeClass', ['arg1', 'arg2']);

	$result = SmartStatement::from($statement);

	Assert::same($statement, $result);
	Assert::equal(['arg1', 'arg2'], $result->arguments);
});

// Throw exception for invalid type - integer
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		SmartStatement::from(123);
	}, LogicalException::class, 'Unsupported type of service');
});

// Throw exception for invalid type - array
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		SmartStatement::from(['invalid']);
	}, LogicalException::class, 'Unsupported type of service');
});

// Throw exception for invalid type - null
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		SmartStatement::from(null);
	}, LogicalException::class, 'Unsupported type of service');
});

// Throw exception for invalid type - object
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		SmartStatement::from(new stdClass());
	}, LogicalException::class, 'Unsupported type of service');
});
