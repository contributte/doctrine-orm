<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI\Definitions;

use Nette\DI\Definitions\Statement;
use Nettrine\ORM\Exception\Logical\InvalidArgumentException;

final class SmartStatement
{

	public static function from(mixed $service): Statement
	{
		if (is_string($service)) {
			return new Statement($service);
		} elseif ($service instanceof Statement) {
			return $service;
		} else {
			throw new InvalidArgumentException('Unsupported type of service');
		}
	}

}
