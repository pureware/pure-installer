<?php

namespace Pure\Installer\Console\Generator;

use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;

// refactor GeneratorInterface name to installer interface and pass $inout and $output to class props
interface GeneratorInterface
{
    public function generate(Input $input, Output $output): int;
}
