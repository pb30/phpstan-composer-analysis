<?php

namespace ComposerAnalyzer;

use Composer\Autoload\ClassLoader;
use ShipMonk\ComposerDependencyAnalyser\Initializer;

class ComposerInitializer extends Initializer
{
    public function initComposerClassLoaders(): array
    {
        // TODO: Every run outputs the following error. For now we are overriding and not performing the check
        // Detected multiple class loaders:
        // • phar:///vendor/phpstan/phpstan/phpstan.phar/vendor
        // • /vendor
        return ClassLoader::getRegisteredLoaders();
    }
}
