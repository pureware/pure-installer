<?php

namespace Pure\Installer\Console\Generator\Plugin;

use Pure\Installer\Console\Generator\GeneratorInterface;
use Pureware\TemplateGenerator\Generator\DirectoryGenerator;
use Pureware\TemplateGenerator\Parser\TwigParser;
use Pureware\TemplateGenerator\TreeBuilder\TreeBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\UnicodeString;

class PluginGenerator implements GeneratorInterface
{

    private ?string $pluginName = null;
    private string $namespace;
    private string $composerName;

    public function generate(Input $input, Output $output): int
    {
        $this->pluginName = $input->getOption('pluginName');
        $io = new SymfonyStyle($input, $output);
        if (!$this->pluginName) {
            $this->pluginName = $io->ask('Name of the plugin');
        }

        $this->resolveNamespace();

        $this->namespace = $io->ask('Base namespace', $this->namespace); /** @todo validate input */

        $parser = new TwigParser();
        $parser->setTemplateData(
            [
                'pluginName' => $this->pluginName,
                'namespace' => $this->namespace,
                'composerName' => $this->composerName,
                'copyright' => '',
                'composerDescriptionEn' => '', //@todo
                'composerDescriptionDe' => '', // @todo
                'shopwareVersion' => '>6.4.6.0 <6.5.0.0'//@todo
            ]
        );

        $generator = new DirectoryGenerator(getcwd() . DIRECTORY_SEPARATOR . $this->pluginName, $parser);
        if ($input->getOption('force')) {
            $generator->setForce(true);
        }

        $directory = (new TreeBuilder())->buildTree(__DIR__ . '/../../Resources/skeleton/plugin', $this->pluginName);

        $generator->generate($directory);


        return Command::SUCCESS;

    }

    public function resolveNamespace(): void {

        $snakeCase = (new UnicodeString($this->pluginName))->camel()->title()->snake();
        $strings = explode('_', $snakeCase);
        if (count($strings) < 2) {
            throw new \RuntimeException('Could not resolve a namespace for this plugin name. Provide a name with a prefix i.e. SwagPlugin');
        }

        $prefix = \ucfirst(\array_shift($strings));
        $class = (new UnicodeString(implode("_", $strings)))->camel()->title();

        $this->namespace = $prefix . "\\" . $class;

        $this->composerName = \strtolower($prefix) . "\\" . (new AsciiSlugger())->slug($class->snake());
    }
}
