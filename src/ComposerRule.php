<?php

namespace ComposerAnalyzer;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;

/**
 * @implements Rule<CollectedDataNode>
 */
class ComposerRule implements Rule
{
    private ComposerCollector $depAnalyzer;

    public function __construct(ComposerCollector $depAnalyzer)
    {
        $this->depAnalyzer = $depAnalyzer;
    }

    public function getNodeType(): string
    {
        // use collector rule, to make sure we run the underlying analyzer only once
        // see https://github.com/pb30/phpstan-composer-analysis/issues/10
        return CollectedDataNode::class;
    }

    /**
     * @return RuleError[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->isOnlyFilesAnalysis()) {
            return [];
        }

        return $this->depAnalyzer->analyze();
    }
}
