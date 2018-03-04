# ORM

- [Minimal configuration](#minimal-configuration)
- [ORM](#ormextension)
	- [Own entity manager](#own-entitymanager)
	- [Configuration](#configuration)
- [Annotations](#annotations)
- [Cache](#ormcacheextension)
- [Console](#console)
- [Other features](#other-features)
	- [ID attribute](#id-attribute)

## Minimal Configuration

Enable DBAL extension

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

## OrmExtension

Base OrmExtension

### Own EntityManager

```yaml
orm:
    entityManagerClass: App\Model\Database\EntityManager
```

### Configuration

@todo

## Annotations

```yaml
extensions:
    orm.annotations: Nettrine\ORM\DI\OrmAnnotationsExtension

orm.annotations:
    paths: [] # define paths for Entities 
    ignore: [] # ignored annotations
    cache: Doctrine\Common\Cache\FilesystemCache
    cacheDir: '%tempDir%/cache/Doctrine.Annotations'
```

## OrmCacheExtension

@todo

## Console

Adds console commands

![Commands](commands.png)

This extension require `Symfony\Console`, you can use [Contributte/Console](https://github.com/contributte/console) for example.

```yaml
extensions:
    console: Contributte\Console\DI\ConsoleExtension
    orm.console: Nettrine\ORM\DI\OrmConsoleExtension
```

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
