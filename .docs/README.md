# Contributte Doctrine ORM

Integration of [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html) for Nette Framework.

## Content

- [Installation](#installation)
- [Configuration](#configuration)
  - [Minimal configuration](#minimal-configuration)
  - [Advanced configuration](#advanced-configuration)
  - [Auto configuration](#auto-configuration)
  - [EntityManager](#entitymanager)
  - [Caching](#caching)
  - [Mapping](#mapping)
    - [Attributes](#attributes)
    - [XML](#xml)
    - [Helper](#helper)
  - [DBAL](#dbal)
  - [Console](#console)
- [Static analyses](#static-analyses)
- [Troubleshooting](#troubleshooting)
- [Examples](#examples)

## Installation

Install package using composer.

```bash
composer require nettrine/orm
```

Register prepared [compiler extension](https://doc.nette.org/en/dependency-injection/nette-container) in your `config.neon` file.

```neon
extensions:
  nettrine.orm: Nettrine\ORM\DI\OrmExtension
```

> [!NOTE]
> This is just **ORM**, for **DBAL** please use [nettrine/dbal](https://github.com/contributte/doctrine-dbal).

## Configuration

### Minimal configuration

```neon
nettrine.orm:
  managers:
    default:
      connection: default
      mapping:
        App:
          directories: [%appDir%/Database]
          namespace: App\Database
```

### Advanced configuration

Here is the list of all available options with their types.

 ```neon
nettrine.orm:
  managers:
    <name>:
      connection: <string>
      entityManagerDecoratorClass: <class>
      configurationClass: <class>

      lazyNativeObjects: <bool>
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
      namingStrategy: <class-string>
      quoteStrategy: <class-string>
      entityListenerResolver: <class-string>
      repositoryFactory: <class-string>
      defaultQueryHints: <mixed[]>
      filters:
        <name>:
          class: <string>
          enabled: <boolean>

      mapping:
        <name>:
          type: <attributes|xml>
          directories: <string[]>
          namespace: <string>
          options:
            fileExtension: <string>
            xsdValidation: <boolean>

      defaultCache: <class-string|service>
      queryCache: <class-string|service>
      resultCache: <class-string|service>
      hydrationCache: <class-string|service>
      metadataCache: <class-string|service>

      secondLevelCache:
        enable: <boolean>
        cache: <class-string|service>
        logger: <class-string|service>
        regions:
          <name>:
            lifetime: <int>
            lockLifetime: <int>
```

For example:

```neon
# See more in nettrine/dbal
nettrine.dbal:
  debug:
    panel: %debugMode%

  connections:
    default:
      driver: pdo_pgsql
      host: localhost
      port: 5432
      user: root
      password: root
      dbname: nettrine

nettrine.orm:
  managers:
    default:
      connection: default
      mapping:
        App:
          directories: [%appDir%/Database]
          namespace: App\Database
```

> [!TIP]
> Take a look at real **Nettrine ORM** configuration example at [contributte/doctrine-project](https://github.com/contributte/doctrine-project/blob/f226bcf46b9bcce2f68961769a02e507936e4682/config/config.neon).

### Auto configuration

By default, this extension will try to autoconfigure itself.

- **proxyDir**: `%tempDir%/proxies`, if `%tempDir%` is not defined,, you have to define it manually.
- **autoGenerateProxyClasses**: `%debugMode%`, if `%debugMode%` is not defined, you have to define it manually.
  - `0` means that the proxy classes must be generated manually.
  - `1` means that the proxy classes are generated automatically.
  - `2` means that the proxy classes are generated automatically when the proxy file does not exist.
  - `3` means that the proxy classes are generated automatically using `eval()` (useful for debugging).
  - `4` means that the proxy classes are generated automatically when the proxy file does not exist or when the proxied file changed.

### Lazy Native Objects

> [!WARNING]
> Requires PHP >= 8.4 and doctrine/orm >= 3.4.0

This setting will override any of the proxy settings and doctrine will use [native lazy objects](https://www.php.net/manual/en/language.oop5.lazy-objects.php) that were added to PHP in version 8.4. No proxies are generated and stored on the disk. This also works with new [property hooks](https://www.php.net/manual/en/language.oop5.property-hooks.php).

This will be required by default in version 4.0.0.

> [!TIP]
> Take a look at more information in official Doctrine documentation:
> - https://www.doctrine-project.org/projects/doctrine-orm/en/3.4/reference/advanced-configuration.html#native-lazy-objects-optional

### EntityManager

EntityManager is a central access point to ORM functionality. It is a wrapper around ObjectManager and holds the metadata and configuration of the ORM.

**EntityManagerDecorator**

You can use `entityManagerDecoratorClass` to decorate EntityManager.

```neon
nettrine.orm:
  managers:
    default:
      connection: default
      entityManagerDecoratorClass: App\MyEntityManagerDecorator
```

**Close & Reset**

If you hit `The EntityManager is closed.` exception, you can use `reset` method to reopen it.

```php
$managerRegistry = $container->getByType(Doctrine\Persistence\ManagerRegistry::class);
$managerRegistry->resetManager(); // default
$managerRegistry->resetManager('second');
```

> [!WARNING]
> Resetting the manager is a dangerous operation. It is also black magic, because you cannot just create a new EntityManager instance,
> you have to reset the current one using internal methods (reflection & binding).
> Class responsible for this operation is [`Nettrine\ORM\ManagerRegistry`](https://github.com/contributte/doctrine-orm/blob/master/src/ManagerRegistry.php).

### Caching

> [!TIP]
> Take a look at more information in official Doctrine documentation:
> - https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/caching.html

A Doctrine ORM can automatically cache query results and metadata. The feature is optional though, and by default, no cache is configured.
You can enable the result cache by setting the `defaultCache` configuration option to an instance of a cache driver or `metadataCache`, `queryCache`, `resultCache`, `hydrationCache` separately.

> [!WARNING]
> Cache adapter must implement `Psr\Cache\CacheItemPoolInterface` interface.
> Use any PSR-6 + PSR-16 compatible cache library like `symfony/cache` or `nette/caching`.

In the simplest case, you can define only `defaultCache` for all caches.

```neon
nettrine.orm:
  managers:
    default:
      # Create cache manually
      defaultCache: App\CacheService(%tempDir%/cache/doctrine/orm)

      # Use registered cache service
      defaultCache: @cacheService
```

Or you can define each cache separately.

```neon
nettrine.orm:
  managers:
    default:
      queryCache: App\CacheService(%tempDir%/cache/doctrine/orm/query)
      resultCache: App\CacheService(%tempDir%/cache/doctrine/orm/result)
      hydrationCache: App\CacheService(%tempDir%/cache/doctrine/orm/hydration)
      metadataCache: App\CacheService(%tempDir%/cache/doctrine/orm/metadata)
```

Second level cache is a bit different. Be sure you know what you are doing, lear more in official [Doctrine documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/3.3/reference/second-level-cache.html).

```neon
nettrine.orm:
  managers:
    default:
        secondLevelCache:
          enable: true
          cache: App\CacheService(%tempDir%/cache/doctrine/orm/slc)
          logger: App\LoggerService()
          regions:
            region1:
              lifetime: 3600
              lockLifetime: 60
            region2:
              lifetime: 86000
              lockLifetime: 60
```

If you like [`symfony/cache`](https://github.com/symfony/cache) you can use it as well.

```neon
nettrine.orm:
    managers:
      default:
        # Use default cache
        defaultCache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: orm, defaultLifetime: 0, directory: %tempDir%/cache/doctrine/orm)

        # Or use separate caches
        queryCache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: orm-query, defaultLifetime: 0, directory: %tempDir%/cache/doctrine/orm/query)
        resultCache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: orm-result, defaultLifetime: 0, directory: %tempDir%/cache/doctrine/orm/result)
        hydrationCache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: orm-hydration, defaultLifetime: 0, directory: %tempDir%/cache/doctrine/orm/hydration)
        metadataCache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: orm-metadata, defaultLifetime: 0, directory: %tempDir%/cache/doctrine/orm/metadata)
```

If you like [`nette/caching`](https://github.com/nette/caching) you can use it as well. Be aware that `nette/caching` is not PSR-6 + PSR-16 compatible, you need `contributte/psr16-caching`.

```neon
nettrine.orm:
    managers:
      default:
        defaultCache: Contributte\Psr6\CachePool(
          Nette\Caching\Cache(
            Nette\Caching\Storages\FileStorage(%tempDir%/cache)
            doctrine/orm
          )
        )
```

> [!IMPORTANT]
> You should always use cache for production environment. It can significantly improve performance of your application.
> Pick the right cache adapter for your needs.
> For example from symfony/cache:
>
> - `FilesystemAdapter` - if you want to cache data on disk
> - `ArrayAdapter` - if you want to cache data in memory
> - `ApcuAdapter` - if you want to cache data in memory and share it between requests
> - `RedisAdapter` - if you want to cache data in memory and share it between requests and servers
> - `ChainAdapter` - if you want to cache data in multiple storages

### Mapping

There are several ways how to map entities to Doctrine ORM. This library supports  **attributes** and **xml** out of the box.

### Attributes

Since PHP 8.0, we can use [#[attributes]](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/attributes-reference.html) for entity mapping.

```php
<?php declare(strict_types=1);

namespace App\Database;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'customer')]
class Customer
{

    #[ORM\Column(length: 32, unique: true, nullable: false)]
    protected string $username;

    #[ORM\Column(columnDefinition: 'CHAR(2) NOT NULL')]
    protected string $country;

}
```

Configuration for attribute mapping looks like this:

```neon
nettrine.orm:
  managers:
    default:
      connection: default
      mapping:
        App:
          directories: [%appDir%/Database]
          namespace: App\Database
```

### XML

The XML mapping driver enables you to provide the ORM metadata in form of XML documents. It requires the dom extension in order to be able to validate your mapping documents against its XML Schema.

> [!TIP]
> Take a look at more information in official Doctrine documentation:
> - https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/xml-mapping.html

```xml

<doctrine-mapping
  xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
  xmlns:xsi="https://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="
    http://doctrine-project.org/schemas/orm/doctrine-mapping
    https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd
">

  ...

</doctrine-mapping>
```

Configuration for XML mapping looks like this:

```neon
nettrine.orm:
  managers:
    default:
      connection: default
      mapping:
        App:
          type: xml
          directories: [%appDir%/Database]
          namespace: App\Database
          options:
            fileExtension: .orm.xml
            xsdValidation: true
```

Setting `xsdValidation` to `false` will allow using custom XML elements in mapping files, as used by some behavior extensions (e.g. gedmo:sortable-position).

### Helper

You can use `MappingHelper` to add multiple mappings at once. This is useful when you have multiple modules with entities.
Create your own [compiler extension](https://doc.nette.org/en/dependency-injection/nette-container) and use `MappingHelper` to add mappings.

It's a good practice if you have separated modules in your applications.

```php
<?php declare(strict_types=1);

namespace App\Model\DI;

use Nette\DI\CompilerExtension;use Nettrine\ORM\DI\Helpers\MappingHelper;

class DoctrineMappingExtension extends CompilerExtension
{

  public function beforeCompile(): void
  {
    MappingHelper::of($this)
        ->addAttribute($connection = 'default', $namespace = 'App\Model\Database', $path = __DIR__ . '/../app/Model/Database')
        ->addAttribute('default', 'Forum\Modules\Database', __DIR__ . '/../../modules/Forum/Database')
        ->addXml('default', 'Gallery1\Modules\Database', __DIR__ . '/../../modules/Gallery1/Database')
        ->addXml('default', 'Gallery2\Modules\Database', __DIR__ . '/../../modules/Gallery2/Database')
  }

}
```

Do not forget to register your extension in `config.neon`.

```neon
extensions:
  category: App\Model\DI\DoctrineMappingExtension
```

### DBAL

> [!TIP]
> Doctrine ORM needs DBAL. You can use `doctrine/dbal` or [nettrine/dbal](https://github.com/contributte/doctrine-dbal).

```bash
composer require nettrine/dbal
```

```neon
extensions:
  nettrine.dbal: Nettrine\DBAL\DI\DbalExtension
  nettrine.orm: Nettrine\ORM\DI\OrmExtension
```

### Console

> [!TIP]
> Doctrine DBAL needs Symfony Console to work. You can use `symfony/console` or [contributte/console](https://github.com/contributte/console).

```bash
composer require contributte/console
```

```neon
extensions:
  console: Contributte\Console\DI\ConsoleExtension(%consoleMode%)

  nettrine.orm: Nettrine\ORM\DI\OrmExtension
```

Since this moment when you type `bin/console`, there'll be registered commands from Doctrine DBAL.

![Console Commands](https://raw.githubusercontent.com/nettrine/orm/master/.docs/assets/console.png)

## Static analyses

You can use [PHPStan](https://github.com/phpstan) to analyze your code.

1. Install PHPStan and Doctrine extension.

```bash
composer require --dev phpstan/phpstan phpstan/phpstan-doctrine
```

2. Create ORM loader for PHPStan, e.q. `phpstan-doctrine.php`.

```php
<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

return App\Bootstrap::boot()
	->createContainer()
	->getByType(Doctrine\ORM\EntityManagerInterface::class);
```

3. Configure PHPStan in `phpstan.neon`.

```neon
includes:
	- vendor/phpstan/phpstan-doctrine/extension.neon

parameters:
	level: 9
	phpVersion: 80200

	tmpDir: %currentWorkingDirectory%/var/tmp/phpstan

	fileExtensions:
		- php
		- phpt

	paths:
		- app

	doctrine:
		objectManagerLoader: phpstan-doctrine.php
```

4. And run PHPStan.

```bash
vendor/bin/phpstan analyse -c phpstan.neon
```

## Troubleshooting

1. Are you looking for custom types? You can register custom types in DBAL, see [Nettrine DBAL](https://github.com/contributte/doctrine-dbal/blob/master/.docs/README.md#types).

2. You have to configure entity mapping (for example attributes), otherwise you will get `It's a requirement to specify a Metadata Driver` error.

## Examples

> [!TIP]
> Take a look at more examples in [contributte/doctrine](https://github.com/contributte/doctrine/tree/master/.docs).
