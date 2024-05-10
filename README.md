<h2 align="center">
    ⚠️ This is proof of concept.<br/>
    PHPStan Wrapper for Composer Dependency Analysis
</h2>

This is a proof of concept PHPStan Extension for [shipmonk/composer-dependency-analyser](https://github.com/shipmonk-rnd/composer-dependency-analyser).

This allows you to use `composer-dependency-analyser` without adding additional steps in your CI pipeline.

## Installation

1. `composer require --dev pb30/phpstan-composer-analysis`
2. Add the following to your `phpstan.neon` includes: `- vendor/pb30/phpstan-composer-analysis/extension.neon` 

## Usage
Composer dependency issues are reported as standard PHPStan errors. They can be ignored using [standard PHPStan `ignoreErrors` configuration](https://phpstan.org/user-guide/ignoring-errors#ignoring-in-configuration-file).

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

## Configuration

Several settings for `composer-dependency-analyser` can be configured in `phpstan.neon`:

```neon
parameters:
    composerAnalysis:
        additionalProdPath:
            - config
            - routes
        additionalDevPaths:
            - database/seeders
        ignoreAllShadowDeps: false
        ignoreAllDevDepsInProd: false
        ignoreAllProdDepsInDev: false
        ignoreAllUnusedDeps: false
        ignoreSpecificUnusedDeps:
            - laravel/tinker
```