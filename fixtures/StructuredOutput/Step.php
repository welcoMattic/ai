<?php

namespace Symfony\AI\Fixtures\StructuredOutput;

final class Step
{
    public function __construct(
        public string $explanation,
        public string $output,
    ) {
    }
}
