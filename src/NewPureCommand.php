<?php

namespace Pure\Installer\Console;

use Pure\Installer\Console\Generator\Plugin\PluginGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NewPureCommand extends Command
{
    private array $allowedTypes = ['plugin', 'project'];

    protected function configure()
    {
        $this
            ->setName('new')
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'Choose between plugin or project'
            )
            ->addOption('pluginName', 'p', InputArgument::OPTIONAL, 'The name of a plugin. Only used if type is plugin', null)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Override files. Be careful when using!')
            ->addOption('workingDir', null, InputOption::VALUE_OPTIONAL, 'The path were you want to create the new plugin', null)
            ->addOption('git', null, InputOption::VALUE_NONE, 'Initialize a git repo and first commit. Only a remote url have to be set.')
            ->addOption('branch', null, InputOption::VALUE_OPTIONAL, 'Init branch name for git', 'main')
            ->setDescription('Create an new Shopware project or plugin with a ready to use boilerplate.');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(PHP_EOL."<fg=blue>
 _____  _    _ _____  ______ 
|  __ \| |  | |  __ \|  ____|
| |__) | |  | | |__) | |__   
|  ___/| |  | |  _  /|  __|  
| |    | |__| | | \ \| |____ 
|_|     \____/|_|  \_\______| </>".PHP_EOL.PHP_EOL);

        $output->writeln('Pure installer');

        $type = $input->getArgument('type');
        if (!in_array($type, $this->allowedTypes)) {
            throw new \RuntimeException(sprintf('Type is not an allowed value. Only "%s" is allowed.', implode(' or ', $this->allowedTypes)));
        }

        if ($type === 'plugin') {
            return (new PluginGenerator())->generate($input, $output);
        }

        return self::SUCCESS;
    }
}
