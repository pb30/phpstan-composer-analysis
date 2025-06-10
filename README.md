<h2 align="center">
    PHPStan Wrapper for Composer Dependency Analysis
</h2>

<p align="center">
    <a href="https://packagist.org/packages/pb30/phpstan-composer-analysis"><img src="https://img.shields.io/packagist/v/pb30/phpstan-composer-analysis.svg" alt="Latest Version on Packagist"></a>
    <a href="https://github.com/pb30/phpstan-composer-analysis/actions/workflows/static-analysis.yml"><img src="https://github.com/pb30/phpstan-composer-analysis/actions/workflows/static-analysis.yml/badge.svg" alt="static analysis"></a>
    <a href="LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-brightgreen.svg" alt="MIT Licensed"></a>
</p>

This is a PHPStan Extension for [shipmonk/composer-dependency-analyser](https://github.com/shipmonk-rnd/composer-dependency-analyser).

This allows you to use `composer-dependency-analyser` without adding additional steps in your CI pipeline.

## Installation

1. `composer require --dev pb30/phpstan-composer-analysis`
2. Add the following to your `phpstan.neon` includes: `- vendor/pb30/phpstan-composer-analysis/extension.neon` 

## Usage
Composer dependency issues are reported as standard PHPStan errors.

You can ignore any errors or false positives using the [standard PHPStan `ignoreErrors` configuration](https://phpstan.org/user-guide/ignoring-errors#ignoring-in-configuration-file) or through the settings below..

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
        additionalProdPaths:
            - config
            - routes
        additionalDevPaths:
            - database/seeders
        ignoreAllShadowDeps: false
        ignoreAllDevDepsInProd: false
        ignoreAllProdDepsInDev: false
        ignoreAllUnusedDeps: false
        disableExtensionsAnalysis: false
        ignoreSpecificUnusedDeps:
            - laravel/tinker
```
