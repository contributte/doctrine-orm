# ORM

- [Minimal configuration](#minimal-configuration)
- [ORM base extension](#ormextension)
	- [Own entity manager](#own-entitymanager)
	- [Configuration](#configuration)
- [Annotations Bridge](#annotations-bridge)
- [Cache Bridge](#cache-bridge)
- [Console Bridge](#console-bridge)
- [Other features](#other-features)
	- [ID attribute](#id-attribute)

## Minimal configuration

Enable DBAL extension. Take a look at [nettrine/dbal](https://github.com/nettrine/dbal).

```yaml
extensions:
    dbal: Nettrine\DBAL\DI\DbalExtension
```

Set-up DBAL connection

```yaml
dbal:
    connection:
        host: 127.0.0.1
        user: root
        password:
        dbname: nettrine
        #driver: pdo_pgsql
```

Enable ORM extension

```yaml
extensions:
    orm: Nettrine\ORM\DI\OrmExtension
```

Define metadata provider - Annotations in this case 

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

@todo

## Annotations Bridge

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

## Cache Bridge

@todo

## Console Bridge

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

![Commands](commands.png)

## Other features 

### Id attribute

You can use predefined `Id` trait in your Entities

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
