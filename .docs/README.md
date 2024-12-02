# Contributte Doctrine ORM

[Doctrine/ORM](https://www.doctrine-project.org/projects/orm.html) to Nette Framework.


## Content
- [Setup](#setup)
- [Relying](#relying)
- [Configuration](#configuration)
- [Mapping](#mapping)
  - [Attributes](#attributes)
  - [XML](#xml)
  - [Helpers](#helpers)
- [Examples](#examples)
- [Other](#other)


## Setup

Install package

```bash
composer require nettrine/orm
```

Register extension

```neon
extensions:
  nettrine.orm: Nettrine\ORM\DI\OrmExtension
```


## Relying

Take advantage of enpowering this package with 3 extra packages:

- `doctrine/dbal`
- `doctrine/cache`
- `symfony/console`


### `doctrine/dbal`

This package relies on `doctrine/dbal`, use prepared [nettrine/dbal](https://github.com/contributte/doctrine-dbal) integration.

```bash
composer require nettrine/dbal
```

```neon
extensions:
  nettrine.dbal: Nettrine\DBAL\DI\DbalExtension
```

[Doctrine ORM](https://www.doctrine-project.org/projects/orm.html) needs [Doctrine DBAL](https://www.doctrine-project.org/projects/dbal.html) to be configured. If you register `nettrine/dbal` extension it will detect it automatically.

> Doctrine DBAL provides powerful database abstraction layer with many features for database schema introspection, schema management and PDO abstraction.


### `doctrine/cache`

This package relies on `doctrine/cache`, use prepared [nettrine/cache](https://github.com/contributte/doctrine-cache) integration.

```bash
composer require nettrine/cache
```

```neon
extensions:
  nettrine.cache: Nettrine\Cache\DI\CacheExtension
```

[Doctrine ORM](https://www.doctrine-project.org/projects/orm.html) needs [Doctrine Cache](https://www.doctrine-project.org/projects/cache.html) to be configured. If you register `nettrine/cache` extension it will detect it automatically.

`CacheExtension` sets up cache for all important parts: `queryCache`, `resultCache`, `hydrationCache`, `metadataCache` and `secondLevelCache`.

This is the default configuration, it uses the autowired driver.

```neon
extensions:
  nettrine.orm: Nettrine\ORM\DI\OrmExtension
  nettrine.orm.cache: Nettrine\ORM\DI\OrmCacheExtension
```

You can also specify a single driver or change the `nettrine.orm.cache.defaultDriver` for all of them.

```neon
nettrine.orm.cache:
  defaultDriver: App\DefaultOrmCacheDriver
  queryCache: App\SpecialDriver
  resultCache: App\SpecialOtherDriver
  hydrationCache: App\SpecialDriver('foo')
  metadataCache: @cacheDriver
```

`secondLevelCache` uses autowired driver (or `defaultDriver`, if specified) for `CacheConfiguration` setup, but you can also replace it with custom `CacheConfiguration`.

```neon
nettrine.orm.cache:
  secondLevelCache: @cacheConfigurationFactory::create('bar')
```

You can turn off `secondLevelCache` by setting it to `false`:

```neon
nettrine.orm.cache:
  secondLevelCache: false
```

### `symfony/console`

This package relies on `symfony/console`, use prepared [contributte/console](https://github.com/contributte/console) integration.

```bash
composer require contributte/console
```

```neon
extensions:
  console: Contributte\Console\DI\ConsoleExtension(%consoleMode%)

  nettrine.orm: Nettrine\ORM\DI\OrmExtension
  nettrine.orm.console: Nettrine\ORM\DI\OrmConsoleExtension(%consoleMode%)
```

Since this moment when you type `bin/console`, there'll be registered commands from Doctrine DBAL.

![Console Commands](https://raw.githubusercontent.com/nettrine/orm/master/.docs/assets/console.png)


## Configuration

**Schema definition**

 ```neon
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
    filters:
      <string>:
        class: <string>
        enabled: <boolean>

  entityManagerDecoratorClass: <class>
  configurationClass: <class>
```

**Under the hood**

Minimal configuration could look like this:

```neon
nettrine.orm:
  configuration:
    autoGenerateProxyClasses: %debugMode%
```

Take a look at real **Nettrine ORM** configuration example at [contributte/webapp-project](https://github.com/contributte/webapp-skeleton/blob/d23e6cbac9b91d6d069583f1661dd1171ccfe077/app/config/ext/nettrine.neon).

**Side notes**

1. The compiler extensions would be so big that we decided to split them into more separate files / compiler extensions.

2. At this time we support only 1 connection, the **default** connection. If you need more connections (more databases?), please open an issue or send a PR. Thanks.

3. Are you looking for custom types? You can register custom types in DBAL, see [Nettrine DBAL](https://github.com/contributte/doctrine-dbal/blob/master/.docs/README.md#types).

4. You have to configure entity mapping (see below), otherwise you will get `It's a requirement to specify a Metadata Driver` error.


## Mapping

Doctrine ORM needs to know where your entities are located and how they are described (mapping).

Additional metadata provider needs to be registered. We provide bridges for these drivers:

- **attributes** (`Nettrine\ORM\DI\OrmAttributesExtension`)
- **xml** (`Nettrine\ORM\DI\OrmXmlExtension`)


### Attributes

Since PHP 8.0, we can use [#[attributes]](https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/reference/attributes-reference.html) for entity mapping.

```php
<?php

namespace App\Model\Database;

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

Use `OrmAttributesExtension` as the bridge to the AttributeDriver. This is the default configuration.

```neon
extensions:
  nettrine.orm: Nettrine\ORM\DI\OrmExtension
  nettrine.orm.attributes: Nettrine\ORM\DI\OrmAttributesExtension

nettrine.orm.attributes:
  mapping: [
    namespace: path
  ]
  excluded: []
```

Example configuration for entity located at `app/Model/Database` folder.

```neon
nettrine.orm.attributes:
  mapping:
   App\Model\Database: %appDir%/Model/Database
```


### XML

Are you using [XML mapping](https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/xml-mapping.html) for your entities?

```xml
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                          https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    ...

</doctrine-mapping>
```

You will also appreciate ORM => XML bridge, use `OrmXmlExtension`. This is the default configuration:

```neon
extensions:
  nettrine.orm: Nettrine\ORM\DI\OrmExtension
  nettrine.orm.xml: Nettrine\ORM\DI\OrmXmlExtension

nettrine.orm.xml:
  mapping: [
    namespace: path
  ]
  fileExtension: .dcm.xml
  simple: false
```

Using **simple** you will enable [`SimplifiedXmlDriver`](https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/xml-mapping.html#simplified-xml-driver).


### Helpers

**MappingHelper**

You can use the predefined `MappingHelper` helper class in your compiler extensions. Be careful, you have to call it in `beforeCompile` phase.

```php
use Nette\DI\CompilerExtension;
use Nettrine\ORM\DI\Helpers\MappingHelper;

class CategoryExtension extends CompilerExtension
{

  public function beforeCompile(): void
  {
    MappingHelper::of($this)
        ->addAttribute('App\Model\Database', __DIR__ . '/../app/Model/Database')
        ->addAttribute('Forum\Modules\Database', __DIR__ . '/../../modules/Forum/Database')
        ->addXml('Gallery1\Modules\Database', __DIR__ . '/../../modules/Gallery1/Database')
        ->addXml('Gallery2\Modules\Database', __DIR__ . '/../../modules/Gallery2/Database', $simple = TRUE)
  }

}
```


## Examples

### 1. Manual example

```sh
composer require nettrine/cache nettrine/migrations nettrine/fixtures nettrine/dbal nettrine/orm
```

```neon
# Extension > Nettrine
# => order is crucial
#
extensions:
  # Common
  nettrine.cache: Nettrine\Cache\DI\CacheExtension
  nettrine.migrations: Nettrine\Migrations\DI\MigrationsExtension
  nettrine.fixtures: Nettrine\Fixtures\DI\FixturesExtension

  # DBAL
  nettrine.dbal: Nettrine\DBAL\DI\DbalExtension
  nettrine.dbal.console: Nettrine\DBAL\DI\DbalConsoleExtension

  # ORM
  nettrine.orm: Nettrine\ORM\DI\OrmExtension
  nettrine.orm.cache: Nettrine\ORM\DI\OrmCacheExtension
  nettrine.orm.console: Nettrine\ORM\DI\OrmConsoleExtension
```

### 2. Example projects

We've made a few skeletons with preconfigured Nettrine nad Contributte packages.

- https://github.com/contributte/webapp-skeleton
- https://github.com/contributte/apitte-skeleton

### 3. Example playground

- https://github.com/contributte/playground (playground)
- https://contributte.org/examples.html (more examples)

## Other

This repository is inspired by these packages.

- https://github.com/doctrine
- https://gitlab.com/Kdyby/Doctrine
- https://gitlab.com/etten/doctrine
- https://github.com/DTForce/nette-doctrine
- https://github.com/portiny/doctrine

Thank you folks.
