<?php

namespace ComposerAnalyzer;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\FileNode;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use ShipMonk\ComposerDependencyAnalyser\Analyser;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;
use ShipMonk\ComposerDependencyAnalyser\Printer;
use ShipMonk\ComposerDependencyAnalyser\Result\AnalysisResult;
use ShipMonk\ComposerDependencyAnalyser\Result\SymbolUsage;
use ShipMonk\ComposerDependencyAnalyser\Stopwatch;

class ComposerCollector implements Collector
{
    /** @var RuleError[] */
    public static array $results;

    private string $cwd;

    private string $composerJsonPath;

    /** @var string[] */
    private array $additionalProdPaths = [];

    /** @var string[] */
    private array $additionalDevPaths = [];

    private bool $ignoreAllShadowDeps = false;

    private bool $ignoreAllDevDepsInProd = false;

    private bool $ignoreAllProdDepsInDev = false;

    private bool $ignoreAllUnusedDeps = false;

    private bool $disableExtensionsAnalysis = false;

    /** @var string[] */
    private array $ignoreSpecificUnusedDeps = [];

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws ShouldNotHappenException
     */
    public function __construct(string $cwd, array $options)
    {
        $this->cwd = $cwd;
        $this->additionalProdPaths = $options['additionalProdPaths'] ?? [];
        $this->additionalDevPaths = $options['additionalDevPaths'] ?? [];
        $this->ignoreAllShadowDeps = boolval($options['ignoreAllShadowDeps'] ?? false);
        $this->ignoreAllDevDepsInProd = boolval($options['ignoreAllDevDepsInProd'] ?? false);
        $this->ignoreAllProdDepsInDev = boolval($options['ignoreAllProdDepsInDev'] ?? false);
        $this->ignoreAllUnusedDeps = boolval($options['ignoreAllUnusedDeps'] ?? false);
        $this->disableExtensionsAnalysis = boolval($options['disableExtensionsAnalysis'] ?? false);
        $this->ignoreSpecificUnusedDeps = $options['ignoreSpecificUnusedDeps'] ?? [];

        $results = $this->runComposerDependencyAnalyser();
        self::$results = $this->reformatResults($results);
    }

    private function runComposerDependencyAnalyser(): AnalysisResult
    {
        // From vendor/shipmonk/composer-dependency-analyser/bin/composer-dependency-analyser
        $stdOutPrinter = new Printer(resource: STDOUT, noColor: true);
        $stdErrPrinter = new Printer(resource: STDERR, noColor: true);
        $initializer = new ComposerInitializer(cwd: $this->cwd, stdOutPrinter: $stdOutPrinter, stdErrPrinter: $stdErrPrinter);
        $stopwatch = new Stopwatch;
        $options = $initializer->initCliOptions(cwd: $this->cwd, argv: []);
        $composerJson = $initializer->initComposerJson(options: $options);
        $initializer->initComposerAutoloader(composerJson: $composerJson);
        $configuration = $initializer->initConfiguration(options: $options, composerJson: $composerJson);
        $this->composerJsonPath = dirname($composerJson->composerVendorDir) .'/composer.json';

        $configuration->ignoreErrors([ErrorType::UNKNOWN_CLASS, ErrorType::UNKNOWN_FUNCTION]);

        foreach ($this->additionalProdPaths as $path) {
            $configuration->addPathToScan(path: $path, isDev: false);
        }

        foreach ($this->additionalDevPaths as $path) {
            $configuration->addPathToScan(path: $path, isDev: true);
        }

        if ($this->ignoreAllShadowDeps) {
            $configuration->ignoreErrors([ErrorType::SHADOW_DEPENDENCY]);
        }

        if ($this->ignoreAllDevDepsInProd) {
            $configuration->ignoreErrors([ErrorType::DEV_DEPENDENCY_IN_PROD]);
        }

        if ($this->ignoreAllProdDepsInDev) {
            $configuration->ignoreErrors([ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV]);
        }

        if ($this->ignoreAllUnusedDeps) {
            $configuration->ignoreErrors([ErrorType::UNUSED_DEPENDENCY]);
        }

        if ($this->disableExtensionsAnalysis) {
            $configuration->disableExtensionsAnalysis();
        }

        foreach ($this->ignoreSpecificUnusedDeps as $packageName) {
            $configuration->ignoreErrorsOnPackage($packageName, [ErrorType::UNUSED_DEPENDENCY]);
        }

        $classLoaders = $initializer->initComposerClassLoaders();

        return (new Analyser(
            stopwatch: $stopwatch,
            defaultVendorDir: $composerJson->composerVendorDir,
            classLoaders: $classLoaders,
            config: $configuration,
            composerJsonDependencies: $composerJson->dependencies
        ))->run();
    }

    /**
     * @return RuleError[]
     *
     * @throws ShouldNotHappenException
     */
    private function reformatResults(AnalysisResult $results): array
    {
        return array_merge(
            $this->buildErrors(
                $results->getShadowDependencyErrors(),
                'Shadow dependency detected',
                'Class is used, but is not specified in composer.json',
                'shadow'
            ),
            $this->buildErrors(
                $results->getDevDependencyInProductionErrors(),
                'Dev dependency used in production',
                'This should probably be moved to "require" section in composer.json',
                'devInProd'
            ),
            array_map(
                fn ($error) => RuleErrorBuilder::message("Prod dependency used only in dev paths: {$error}")
                    ->file($this->composerJsonPath)
                    ->tip('This should probably be moved to "require-dev" section in composer.json')
                    ->identifier('composer.prodInDev')
                    ->build(),
                $results->getProdDependencyOnlyInDevErrors()),
            array_map(
                fn ($error) => RuleErrorBuilder::message("Unused dependency detected: {$error}")
                    ->file($this->composerJsonPath)
                    ->tip('This is listed in composer.json, but no usage was found in scanned paths')
                    ->identifier('composer.unused')
                    ->build(),
                $results->getUnusedDependencyErrors()),
        );
    }

    /**
     * @param  array<string, array<string, array<SymbolUsage>>>  $errors
     * @return RuleError[]
     *
     * @throws ShouldNotHappenException
     */
    private function buildErrors(array $errors, string $message, string $tip, string $identifier): array
    {
        $ruleErrors = [];
        foreach ($errors as $composerPkg => $class) {
            foreach ($class as $className => $instances) {
                foreach ($instances as $instance) {
                    $ruleErrors[] = RuleErrorBuilder::message("{$message}: {$composerPkg} using {$className}")
                        ->file($instance->getFilepath())
                        ->line($instance->getLineNumber())
                        ->tip($tip)
                        ->identifier('composer.'.$identifier)
                        ->build();
                }
            }
        }

        return $ruleErrors;
    }

    public function getNodeType(): string
    {
        return FileNode::class;
    }

    /**
     * @return RuleError[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // We're not actually processing nodes, we just are using a Collector have this run once per analyze run
        return [];
    }
}
