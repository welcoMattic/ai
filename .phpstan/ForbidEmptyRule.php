<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * PHPStan rule that forbids usage of empty() function.
 *
 * This rule enforces that empty() should not be used in favor of explicit checks
 * like null checks, count() for arrays, or string length checks.
 *
 * @author Oskar Stark <oskarstark@googlemail.com>
 *
 * @implements Rule<FuncCall>
 */
final class ForbidEmptyRule implements Rule
{
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof FuncCall) {
            return [];
        }

        if (!$node->name instanceof Node\Name) {
            return [];
        }

        $functionName = $node->name->toString();

        if ('empty' !== strtolower($functionName)) {
            return [];
        }

        // Allow empty() in ai-bundle config file where validation logic can be complex
        if (str_ends_with($scope->getFile(), 'ai-bundle/config/options.php')) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Usage of empty() function is forbidden. Use explicit checks instead: null check, count() for arrays, or string length checks.'
            )
            ->line($node->getLine())
            ->identifier('symfonyAi.forbidEmpty')
            ->tip('Replace empty() with explicit checks like $var !== null && $var !== \'\' for strings, count($var) > 0 for arrays, etc.')
            ->build(),
        ];
    }
}
