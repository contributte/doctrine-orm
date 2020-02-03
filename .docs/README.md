# Nettrine ORM

[Doctrine/ORM](https://www.doctrine-project.org/projects/orm.html) to Nette Framework.


## Content
- [Setup](#setup)
- [Relying](#relying)
- [Configuration](#configuration)
- [Mapping](#mapping)
  - [Annotations](#annotations)
  - [XML](#xml)
  - [Helpers](#helpers)
- [Examples](#examples)


## Setup

Install package

```bash
composer require nettrine/orm
```

Register extension

```yaml
extensions:
  nettrine.orm: Nettrine\ORM\DI\OrmExtension
```


## Relying

Take advantage of enpowering this package with 3 extra packages:

- `doctrine/dbal`
- `doctrine/cache`
- `symfony/console`


### `doctrine/dbal`

This package relies on `doctrine/dbal`, use prepared [nettrine/dbal](https://github.com/nettrine/dbal) integration.

```bash
composer require nettrine/dbal
```

```yaml
extensions:
  nettrine.dbal: Nettrine\DBAL\DI\DbalExtension
```

[Doctrine ORM](https://www.doctrine-project.org/projects/orm.html) needs [Doctrine DBAL](https://www.doctrine-project.org/projects/dbal.html) to be configured. If you register `nettrine/dbal` extension it will detect it automatically.

> Doctrine DBAL provides powerful database abstraction layer with many features for database schema introspection, schema management and PDO abstraction.


### `doctrine/cache`

This package relies on `doctrine/cache`, use prepared [nettrine/cache](https://github.com/nettrine/cache) integration.

```bash
composer require nettrine/cache
```

```yaml
extensions:
  nettrine.cache: Nettrine\Cache\DI\CacheExtension
```

[Doctrine ORM](https://www.doctrine-project.org/projects/orm.html) needs [Doctrine Cache](https://www.doctrine-project.org/projects/cache.html) to be configured. If you register `nettrine/cache` extension it will detect it automatically.

`CacheExtension` sets up cache for all important parts: `queryCache`, `resultCache`, `hydrationCache`, `metadataCache` and `secondLevelCache`.

This is the default configuration, it uses the autowired driver.

```yaml
extensions:
  nettrine.orm: Nettrine\ORM\DI\OrmExtension
  nettrine.orm.cache: Nettrine\ORM\DI\OrmCacheExtension
```

You can also specify a single driver or change the `nettrine.orm.cache.defaultDriver` for all of them.

```yaml
nettrine.orm.cache:
  defaultDriver: App\DefaultOrmCacheDriver
  queryCache: App\SpecialDriver
  resultCache: App\SpecialOtherDriver
  hydrationCache: App\SpecialDriver('foo')
  metadataCache: @cacheDriver
```

`secondLevelCache` uses autowired driver (or `defaultDriver`, if specified) for `CacheConfiguration` setup, but you can also replace it with custom `CacheConfiguration`.

```yaml
nettrine.orm.cache:
  secondLevelCache: @cacheConfigurationFactory::create('bar')
```


### `symfony/console`

This package relies on `symfony/console`, use prepared [contributte/console](https://github.com/contributte/console) integration.

```bash
composer require contributte/console
```

```yaml
extensions:
  console: Contributte\Console\DI\ConsoleExtension(%consoleMode%)

  nettrine.orm: Nettrine\ORM\DI\OrmExtension
  nettrine.orm.console: Nettrine\ORM\DI\OrmConsoleExtension(%consoleMode%)
```

Since this moment when you type `bin/console`, there'll be registered commands from Doctrine DBAL.

![Console Commands](https://raw.githubusercontent.com/nettrine/orm/master/.docs/assets/console.png)


## Configuration

**Schema definition**

 ```yaml
nettrine.orm:
  configuration:
    proxyDir: <path>
    autoGenerateProxyClasses: <boolean>
    proxyNamespace: <string>
    metadataDriverImpl: <service>
    entityNamespaces: <mixed[]>
    customStringFunctions: <mixed[]>
    customNumericFunctions: <mixed[]>
    customDatetimeFunctions: <mixed[]>
    customHydrationModes: <string[]>
    classMetadataFactoryName: <string>
    defaultRepositoryClassName: <string>
    namingStrategy: <class>
    quoteStrategy: <class>
    entityListenerResolver: <class>
    repositoryFactory: <class>
    defaultQueryHints: <mixed[]>

  entityManagerDecoratorClass: <class>
  configurationClass: <class>
```

**Under the hood**

Minimal configuration could look like this:

```yaml
nettrine.orm:
  configuration:
    autoGenerateProxyClasses: %debugMode%
```

Take a look at real **Nettrine ORM** configuration example at [Nutella Project](https://github.com/planette/nutella-project/blob/90f1eca94fa62b7589844481549d4823d3ed20f8/app/config/ext/nettrine.neon).

**Side notes**

1. The compiler extensions would be so big that we decided to split them into more separate files / compiler extensions.

2. At this time we support only 1 connection, the **default** connection. If you need more connections (more databases?), please open an issue or send a PR. Thanks.


## Mapping

Doctrine ORM needs to know where your entities are located and how they are described (mapping).

Additional metadata provider needs to be registered. We provide bridges for these drivers:

- **annotations** (`Nettrine\ORM\DI\OrmAnnotationsExtension`)
- **xml** (`Nettrine\ORM\DI\OrmXmlExtension`)


### Annotations

Are you using annotations in your entities?

```php
/**
 * @ORM\Entity
 */
class Category
{
}
```

This feature relies on `doctrine/annotations`, use prepared [nettrine/annotations](https://github.com/nettrine/annotations) integration.

```bash
composer require nettrine/annotations
```

```yaml
extensions:
  nettrine.annotations: Nettrine\Annotations\DI\AnnotationsExtension
```

You will also appreciate ORM => Annotations bridge, use `OrmAnnotationsExtension`. This is the default configuration, it uses an autowired cache driver.

```yaml
extensions:
  nettrine.orm: Nettrine\ORM\DI\OrmExtension
  nettrine.orm.annotations: Nettrine\ORM\DI\OrmAnnotationsExtension

nettrine.orm.annotations:
  namespaces: []
  paths: []
  excludePaths: []
```

### XML

Are you using XML mapping for your entities?

You will also appreciate ORM => XML bridge, use `OrmXmlExtension`. This is the default configuration:

```yaml
extensions:
  nettrine.orm: Nettrine\ORM\DI\OrmExtension
  nettrine.orm.xml: Nettrine\ORM\DI\OrmXmlExtension

nettrine.orm.xml:
  namespaces: []
  paths: []
  fileExtension: .dcm.xml
```


### Helpers

You can use the predefined `TEntityMapping` trait in your compiler extensions.

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


## Other

This repository is inspired by these packages.

- https://gitlab.com/Kdyby/Doctrine
- https://gitlab.com/etten/doctrine
- https://github.com/DTForce/nette-doctrine
- https://github.com/portiny/doctrine

Thank you guys.


## Examples

You can find more examples in [planette playground](https://github.com/planette/playground) repository.
