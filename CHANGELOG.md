# Changelog

All notable changes to `nettrine/orm` are documented in this file.

## Migration notes

### v0.10

- `nettrine/orm` uses only `Nettrine\ORM\DI\OrmExtension`.
- Removed split extensions:
  - `nettrine.orm.cache`
  - `nettrine.orm.attributes`
  - `nettrine.orm.xml`
  - `nettrine.orm.annotations`
  - `nettrine.orm.console`
- Configure cache, mapping, and managers under `nettrine.orm.managers.<name>`.

```neon
# before (removed)
extensions:
  nettrine.orm: Nettrine\ORM\DI\OrmExtension
  nettrine.orm.cache: Nettrine\ORM\DI\OrmCacheExtension

nettrine.orm.cache:
  defaultDriver: @cache

# now
extensions:
  nettrine.orm: Nettrine\ORM\DI\OrmExtension

nettrine.orm:
  managers:
    default:
      connection: default
      defaultCache: @cache
      mapping:
        App:
          directories: [%appDir%/Database]
          namespace: App\Database
```
