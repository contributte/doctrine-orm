<?php declare(strict_types = 1);

namespace Nettrine\ORM\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver as DoctrineAnnotationDriver;

class AnnotationDriver extends DoctrineAnnotationDriver
{

	/**
	 * @param AnnotationReader $reader
	 * @param string[] $paths
	 */
	public function __construct(AnnotationReader $reader, array $paths = [])
	{
		parent::__construct($reader, $paths);
		$this->reader = $reader;
		$this->paths = $paths;
	}

}
