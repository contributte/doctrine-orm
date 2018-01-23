<?php declare(strict_types = 1);

namespace Nettrine\ORM\Mapping;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver as DoctrineAnnotationDriver;

class AnnotationDriver extends DoctrineAnnotationDriver
{

	/**
	 * @param Reader $reader
	 * @param string[] $paths
	 */
	public function __construct(Reader $reader, array $paths = [])
	{
		parent::__construct($reader, $paths);
		$this->reader = $reader;
		$this->paths = $paths;
	}

}
