# Nettrine / ORM

## Content

- [Minimal configuration](#minimal-configuration)
- [ORM extension](#ormextension)
	- [EntityManager](#entitymanager)
	- [Configuration](#configuration)
- [Bridges](#bridges)
	- [Annotations Bridge](#annotations-bridge)
	- [Cache Bridge](#cache-bridge)
	- [Console Bridge](#console-bridge)
- [Other features](#other-features)
	- [ID attribute](#id-attribute)

## Minimal configuration

At first you gonna needed Doctrine DBAL extension. Take a look at [Nettrine DBAL](https://github.com/nettrine/dbal) in this organization. Install nettrine/dbal over composer.

```
composer install nettrine/dbal
```

Place `DbalExtension` into your config neon.

```yaml
extensions:
    dbal: Nettrine\DBAL\DI\DbalExtension
```

And set-up DBAL connection.

```yaml
dbal:
    connection:
        host: 127.0.0.1
        user: root
        password:
        dbname: nettrine
        #driver: pdo_pgsql
```

Secondly, enable Doctrine ORM extension. It's provided by this package. 

```yaml
extensions:
    orm: Nettrine\ORM\DI\OrmExtension
```

Pick any metadata provider, for example widely used are **annotations**.

```yaml
extensions:
    orm.annotations: Nettrine\ORM\DI\OrmAnnotationsExtension

orm.annotations:
    paths:
        - App/Model/Database/Entity
```

You can find full example and more examples in our [playground](https://github.com/nettrine/playground) repository.

## OrmExtension

OrmExtension has a few options you can configure. Let's take a look at them.

### EntityManager

Define own EntityManager is useful for addind or overriding methods you needed.

```yaml
orm:
    entityManagerClass: App\Model\Database\EntityManager
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

## Bridges

The compiler extensions would be so big that we decided to split them into more single files / compiler extensions. 

### Annotations Bridge

Are you using annotations in your entities?

```php
/**
 * @ORM\Entity
 */
class Category
{
```

You gonna needed `OrmAnnotationsExtension`.

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

Since this moment when you type `bin/console`, there'll be registered commands from Doctrine ORM.

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
