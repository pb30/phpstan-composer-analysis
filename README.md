<h2 align="center">
    ⚠️ This is proof of concept.<br/>
    PHPStan Wrapper for Composer Dependency Analysis
</h2>

This is a proof of concept PHPStan Extension for [shipmonk/composer-dependency-analyser](https://github.com/shipmonk-rnd/composer-dependency-analyser).

This allows you to use `composer-dependency-analyser` without adding additional steps in your CI pipeline.

Issues are reported as standard PHPStan errors, and can be ignored using [standard PHPStan `ignoreErrors` configuration](https://phpstan.org/user-guide/ignoring-errors#ignoring-in-configuration-file).

```
 ------ ---------------------------------------------------------------------
  Line   app/DateHelpers.php
 ------ ---------------------------------------------------------------------
  17     Shadow dependency detected: nesbot/carbon using Carbon\CarbonPeriod
         💡 Class is used, but is not specified in composer.json
 ------ ---------------------------------------------------------------------
 
 ------ -------------------------------------------------------------------------
  Line   app/MyHelper.php
 ------ -------------------------------------------------------------------------
  19     Dev dependency used in production: fakerphp/faker using Faker\Generator
         💡 This should probably be moved to "require" section in composer.json
 ------ -------------------------------------------------------------------------

 ------ ---------------------------------------------------------------------------------
  Line   composer.json
 ------ ---------------------------------------------------------------------------------
  -1     Prod dependency used only in dev paths: spatie/once
         💡 This should probably be moved to "require-dev" section in composer.json
  -1     Unused dependency detected: predis/predis
         💡 This is are listed in composer.json, but no usage was found in scanned paths
 ------ ---------------------------------------------------------------------------------
```

## Todos:
* Allow configuring `composer-dependency-analyser` through `phpstan.neon` to reduce number of tool configuration files a project may need