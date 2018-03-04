# ORM

- [Minimal configuration](#minimal-configuration)
- [ORM](#ormextension)
	- [Own entity manager](#own-entitymanager)
	- [Configuration](#configuration)
- [Annotations](#ormannotationsextension)
- [Cache](#ormcacheextension)
- [Console](#ormconsoleextension)
- [Other features](#other-features)
	- [ID attribute](#id-attribute)

## Minimal Configuration

Enable DBAL extension

```
extensions:
	dbal: Nettrine\DBAL\DI\DbalExtension
```

Set-up DBAL connection

```
dbal:
	connection:
		host: 127.0.0.1
		user: root
		password:
		dbname: nettrine
		#driver: pdo_pgsql
```

Enable ORM extension

```
extensions:
	orm: Nettrine\ORM\DI\OrmExtension
```

Define metadata provider - Annotations in this case 

```
extensions:
	orm.annotations: Nettrine\ORM\DI\OrmAnnotationsExtension

orm.annotations:
	paths:
		- App/Model/Database/Entity
```

## OrmExtension

@todo

### Own EntityManager

@todo

### Configuration

@todo

## OrmAnnotationsExtension

@todo

## OrmCacheExtension

@todo

## OrmConsoleExtension

@todo

## Other features 

@todo

### Id attribute

@todo
