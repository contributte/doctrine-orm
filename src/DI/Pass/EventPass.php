<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI\Pass;

use Doctrine\Common\EventSubscriber;
use Nette\DI\Definitions\ServiceDefinition;
use Nettrine\ORM\Events\ContainerEventManager;
use ReflectionClass;

class EventPass extends AbstractPass
{

	public function loadPassConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		// Event manager
		$builder->addDefinition($this->prefix('eventManager'))
			->setFactory(ContainerEventManager::class);
	}

	public function beforePassCompile(): void
	{
		$builder = $this->getContainerBuilder();

		$eventManagerDef = $builder->getDefinition($this->prefix('eventManager'));
		assert($eventManagerDef instanceof ServiceDefinition);

		foreach ($builder->findByType(EventSubscriber::class) as $serviceName => $serviceDef) {
			/** @var class-string<EventSubscriber> $serviceClass */
			$serviceClass = (string) $serviceDef->getType();
			$rc = new ReflectionClass($serviceClass);

			/** @var EventSubscriber $subscriber */
			$subscriber = $rc->newInstanceWithoutConstructor();
			$events = $subscriber->getSubscribedEvents();

			$eventManagerDef->addSetup('?->addEventListener(?, ?)', ['@self', $events, $serviceName]);
		}
	}

}
