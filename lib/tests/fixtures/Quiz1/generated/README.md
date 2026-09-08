# Generated Quiz1 interoperability cartridges

These `.imscc` files are **not** committed. Generate them from the repo root:

```
php qa/export-quiz1-fixtures.php
```

Or to a directory of your choice:

```
php qa/export-quiz1-fixtures.php /tmp/quiz1-cc12
```

Validate any cartridge:

```
php qa/validate-imscc.php lib/tests/fixtures/Quiz1/generated/00-minimal-qti.imscc
```

Manual Canvas/Sakai import order: `00` through `08`.
