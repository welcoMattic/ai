<?php

namespace Symfony\AI\Fixtures\Tool;

final class ToolNoAttribute1
{
    /**
     * @param string $name  the name of the person
     * @param int    $years the age of the person
     */
    public function __invoke(string $name, int $years): string
    {
        return \sprintf('Happy Birthday, %s! You are %d years old.', $name, $years);
    }
}
