<?php

namespace ComposerAnalyzer;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;

class ComposerRule implements Rule
{
    private ComposerCollector $depAnalyzer;

    public function __construct(ComposerCollector $depAnalyzer)
    {
        $this->depAnalyzer = $depAnalyzer;
    }

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $errors = $this->depAnalyzer::$results;
        $this->depAnalyzer::$results = [];

        return $errors;
    }
}
