<?php

namespace Symfony\AI\Fixtures\StructuredOutput;

final class MathReasoning
{
    /**
     * @param Step[] $steps
     */
    public function __construct(
        public array $steps,
        public string $finalAnswer,
    ) {
    }
}
