# Nettrine ORM

Integration of [Doctrine\ORM](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/) to Nette Framework.

## Content

- [Setup](#setup)
- [ORM extension](#ormextension)
	- [EntityManagerDecorator](#entitymanagerdecorator)
	- [Configuration](#configuration)
- [Bridges](#bridges)
	- [Annotations Bridge](#annotations-bridge)
	- [XML Bridge](#xml-bridge)
	- [Cache Bridge](#cache-bridge)
	- [Console Bridge](#console-bridge)
- [Other features](#other-features)
	- [ID attribute](#id-attribute)

## Setup

First of all, install [Nettrine DBAL](https://github.com/nettrine/dbal) package and enable `DbalExtension`.

Install package

```bash
composer require nettrine/orm
```

Register extension

```yaml
extensions:
    orm: Nettrine\ORM\DI\OrmExtension
```

Pick any metadata provider, for example **annotations** (they are widely used). We have a special extension for annotations (`Nettrine\ORM\DI\OrmAnnotationsExtension`).

```yaml
extensions:
    orm.annotations: Nettrine\ORM\DI\OrmAnnotationsExtension

orm.annotations:
    paths:
        - %appDir%/Model/Database/Entity
```

## OrmExtension

OrmExtension has a few options you can configure. Let's take a look at them.

### EntityManagerDecorator

Defining your own EntityManagerDecorator is useful for adding or overriding methods you need.

```yaml
orm:
    entityManagerDecoratorClass: App\Model\Database\EntityManagerDecorator
```

```php
namespace App\Model\Database;

use Nettrine\ORM\EntityManagerDecorator as NettrineEntityManagerDecorator;

class EntityManagerDecorator extends NettrineEntityManagerDecorator
{

}

```

### Configuration

List of all configuration options:

```yaml
orm:
    configuration:
        proxyDir: '%tempDir%/proxies'
        autoGenerateProxyClasses: NULL
        proxyNamespace: 'Nettrine\Proxy'
        metadataDriverImpl: NULL
        entityNamespaces: []
        customStringFunctions: []
        customNumericFunctions: []
        customDatetimeFunctions: []
        customHydrationModes: []
        classMetadataFactoryName: NULL
        defaultRepositoryClassName: NULL
        namingStrategy: Doctrine\ORM\Mapping\UnderscoreNamingStrategy
        quoteStrategy: NULL
        entityListenerResolver: NULL
        repositoryFactory: NULL
        defaultQueryHints: []
```

At this time we support only 1 connection, the **default** connection. If you need more connections (more databases?), please open an issue or send a PR. Thanks.

## Bridges

The compiler extensions would be so big that we decided to split them into more separate files / compiler extensions.

### Annotations Bridge

Are you using annotations in your entities?

```php
/**
 * @ORM\Entity
 */
class Category
{
}
```

You will need the `OrmAnnotationsExtension`. This is the default configuration, it uses an autowired cache driver.

```yaml
extensions:
    orm: Nettrine\ORM\DI\OrmExtension
    orm.annotations: Nettrine\ORM\DI\OrmAnnotationsExtension

orm.annotations:
    paths: []
    excludePaths: []
```

### XML Bridge

Are you using XML mapping for your entities?

You will need the `OrmXmlExtension`. This is the default configuration:

```yaml
extensions:
    orm: Nettrine\ORM\DI\OrmExtension
    orm.xml: Nettrine\ORM\DI\OrmXmlExtension

orm.xml:
    paths: []
    fileExtension: .dcm.xml
```

### Cache Bridge

This extension sets up cache for all important parts: `queryCache`, `resultCache`, `hydrationCache`, `metadataCache` and `secondLevelCache`.

This is the default configuration, it uses the autowired driver.

```yaml
extensions:
    orm: Nettrine\ORM\DI\OrmExtension
    orm.cache: Nettrine\ORM\DI\OrmCacheExtension
```

You can also specify a single driver. Or change the `orm.cache.defaultDriver` for all of them.

```yaml
orm.cache:
    defaultDriver: App\DefaultOrmCacheDriver
    queryCache: App\SpecialDriver
    resultCache: App\SpecialOtherDriver
    hydrationCache: App\SpecialDriver('foo')
    metadataCache: @cacheDriver
```

`secondLevelCache` uses autowired driver (or `defaultDriver`, if specified) for `CacheConfiguration` setup, but you can also replace it with custom `CacheConfiguration`

```yaml
orm.cache:
    secondLevelCache: @cacheConfigurationFactory::create('bar')
```

### Console Bridge

This package works well with [Symfony/Console](https://symfony.com/doc/current/components/console.html). Take a look at [contributte/console](https://github.com/contributte/console) tiny integration for Nette Framework.

```yaml
extensions:
    # Console
    console: Contributte\Console\DI\ConsoleExtension(%consoleMode%)

    # Orm
    orm: Nettrine\ORM\DI\OrmExtension
    orm.console: Nettrine\ORM\DI\OrmConsoleExtension
```

Since this moment you can use all registered Doctrine ORM commands using `bin/console`.

![Commands](https://raw.githubusercontent.com/nettrine/orm/master/.docs/assets/commands.png)

## Other features

### Id attribute

You can use the predefined `Id` trait in your Entities.

```php

use Nettrine\ORM\Entity\Attributes\Id;

/**
 * @ORM\Entity
 */
class Category
{

    use Id;

}
```

### Entity Mapping

You can use the predefined `TEntityMapping` trait in your extensions.

```php

use Nette\DI\CompilerExtension;
use Nettrine\ORM\DI\Traits\TEntityMapping;

class CategoryExtension extends CompilerExtension
{

    use TEntityMapping;

    public function loadConfiguration(): void
    {
        $this->setEntityMappings([
            'Forum' => __DIR__ . '/../Entity',
        ]);
    }
}
```

## Examples

You can find more examples in [planette playground](https://github.com/planette/playground) repository.
