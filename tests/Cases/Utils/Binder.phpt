<?php declare(strict_types = 1);

// phpcs:ignoreFile

namespace Tests\Cases\Utils;

use Contributte\Tester\Toolkit;
use Nettrine\ORM\Utils\Binder;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Bind to object instance - access private property
Toolkit::test(function (): void {
	$obj = new class {

		private string $secret = 'hidden';

	};

	$result = Binder::use($obj, fn (): string => $this->secret);

	Assert::equal('hidden', $result);
});

// Bind to object instance - modify private property
Toolkit::test(function (): void {
	$obj = new class {

		private string $secret = 'hidden';

		public function getSecret(): string
		{
			return $this->secret;
		}

	};

	Binder::use($obj, function (): void {
		$this->secret = 'modified'; // @phpstan-ignore-line
	});

	Assert::equal('modified', $obj->getSecret());
});

// Bind to class string - access static property
Toolkit::test(function (): void {
	$result = Binder::use(TestClassWithStatic::class, fn (): string => self::$staticValue);

	Assert::equal('static_secret', $result);
});

// Return value from closure
Toolkit::test(function (): void {
	$obj = new class {

		private int $value = 42;

	};

	$result = Binder::use($obj, fn (): int => $this->value * 2);

	Assert::equal(84, $result);
});

// Return null from closure
Toolkit::test(function (): void {
	$obj = new class {

	};

	$result = Binder::use($obj, fn (): mixed => null);

	Assert::null($result);
});

class TestClassWithStatic
{

	private static string $staticValue = 'static_secret';

}
