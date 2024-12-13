<?php declare(strict_types = 1);

namespace Nettrine\ORM\Utils;

use Closure;

final class Binder
{

	/**
	 * @param object|class-string $objectOrClass
	 */
	public static function use(object|string $objectOrClass, Closure $closure): mixed
	{
		return $closure->bindTo(is_object($objectOrClass) ? $objectOrClass : null, $objectOrClass)();
	}

}
