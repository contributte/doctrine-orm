# Nettrine / ORM

## Content

- [Minimal configuration](#minimal-configuration)
- [ORM extension](#ormextension)
	- [Own entity manager](#own-entitymanager)
	- [Configuration](#configuration)
- [Bridges](#bridges)
	- [Annotations Bridge](#annotations-bridge)
	- [Cache Bridge](#cache-bridge)
	- [Console Bridge](#console-bridge)
- [Other features](#other-features)
	- [ID attribute](#id-attribute)

## Minimal configuration

Enable DBAL extension. Take a look at [Nettrine DBAL](https://github.com/nettrine/dbal).

```yaml
extensions:
    dbal: Nettrine\DBAL\DI\DbalExtension
```

Set-up DBAL connection.

```yaml
dbal:
    connection:
        host: 127.0.0.1
        user: root
        password:
        dbname: nettrine
        #driver: pdo_pgsql
```

Enable ORM extension.

```yaml
extensions:
    orm: Nettrine\ORM\DI\OrmExtension
```

Define metadata provider - Annotations in this case.

```yaml
extensions:
    orm.annotations: Nettrine\ORM\DI\OrmAnnotationsExtension

orm.annotations:
    paths:
        - App/Model/Database/Entity
```

You can found full example in [playground](https://github.com/nettrine/playground).

## OrmExtension

### Own EntityManager

```yaml
orm:
    entityManagerClass: App\Model\Database\EntityManager
```

### Configuration

Full configuration options:

```yaml
orm:
    configuration:
        proxyDir: '%tempDir%/proxies'
        autoGenerateProxyClasses: NULL
        proxyNamespace: 'Nettrine\Proxy'
        metadataDriverImpl: NULL
        entityNamespaces: []
        customStringFunctions: []
        customNumericFuctions: []
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

## Bridges

### Annotations Bridge

```yaml
extensions:
    orm: Nettrine\ORM\DI\OrmExtension
    orm.annotations: Nettrine\ORM\DI\OrmAnnotationsExtension

orm.annotations:
    paths: [] # define paths for Entities 
    ignore: [] # ignored annotations
    cache: Doctrine\Common\Cache\FilesystemCache
    cacheDir: '%tempDir%/cache/Doctrine.Annotations'
```

### Cache Bridge

@todo

### Console Bridge

This package works pretty well with [Symfony/Console](https://symfony.com/doc/current/components/console.html). Take a look at [Contributte/Console](https://github.com/contributte/console)
tiny integration for Nette Framework.

```yaml
extensions:

    # Console
    console: Contributte\Console\DI\ConsoleExtension

    # Orm
    orm: Nettrine\ORM\DI\OrmExtension
    orm.console: Nettrine\ORM\DI\OrmConsoleExtension
```

From this moment when you type `bin/console`, there'll be registered commands from Doctrine ORM.

![Commands](assets/commands.png)

## Other features 

### Id attribute

You can use predefined `Id` trait in your Entities.

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
