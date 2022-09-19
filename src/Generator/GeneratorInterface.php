<?php

namespace Pure\Installer\Console\Generator;

use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;

interface GeneratorInterface
{
    public function generate(Input $input, Output $output): int;
}
