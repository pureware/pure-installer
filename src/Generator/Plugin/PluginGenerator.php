<?php

namespace Pure\Installer\Console\Generator\Plugin;

use Pure\Installer\Console\Generator\GeneratorInterface;
use Pureware\TemplateGenerator\Generator\DirectoryGenerator;
use Pureware\TemplateGenerator\Parser\TwigParser;
use Pureware\TemplateGenerator\TreeBuilder\TreeBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\UnicodeString;

class PluginGenerator implements GeneratorInterface
{
    public const DEFAULT_VERSION = '6.4.15.0';
    private ?string $pluginName = null;
    private ?string $workingDir = null;
    private string $namespace;
    private string $composerName;
    private string $shopwareVersion;

    public function generate(Input $input, Output $output): int
    {
        $this->pluginName = $input->getOption('pluginName');
        $this->shopwareVersion = $input->getOption('shopwareVersion') ?? $this->getLatestShopwareVersion();
        $io = new SymfonyStyle($input, $output);
        $io->progressStart(4);
        if (!$this->pluginName) {
            $this->pluginName = $io->ask('Name of the plugin');
        }

        $this->resolveNamespace();

        $this->namespace = $io->ask('Base namespace', $this->namespace); /** @todo validate input */

        $workingDir = $input->getOption('workingDir') ?? getcwd();
        $this->workingDir = rtrim($workingDir, DIRECTORY_SEPARATOR);
        $pluginPath = $workingDir . DIRECTORY_SEPARATOR . $this->pluginName;

        $parser = new TwigParser();
        $parser->setTemplateData(
            [
                'pluginName' => $this->pluginName,
                'namespace' => $this->namespace,
                'composerName' => $this->composerName,
                'copyright' => '',
                'composerDescriptionEn' => '',
                'composerDescriptionDe' => '',
                'phpVersion' => '~8.0',
                'shopwareVersion' => $this->shopwareVersion,
                'dockwareVersion' => 'latest',
                'containerName' => 'shop_plugin'
            ]
        );
        $io->progressAdvance(1);

        $generator = new DirectoryGenerator($pluginPath, $parser);
        if ($input->getOption('force')) {
            $generator->setForce(true);
        }

        $directory = (new TreeBuilder())->buildTree(__DIR__ . '/../../Resources/skeleton/plugin', $this->pluginName);
        $io->progressAdvance(1);

        $generator->generate($directory);

        $commands = [
            $this->findComposer() . ' install --working-dir=' . $pluginPath,
            sprintf('echo "%s"', 'PURE installed composer dependencies'),
            sprintf('ls -la %s', $pluginPath)
        ];
        $this->executeCommands($commands, $output);
        $io->progressAdvance(1);
        $output->writeln('');

        $messages = [
            '',
            sprintf('%s: %s', ' ✓ Created the plugin. Change directory', $pluginPath),
            '✓ Installed composer dependencies'
        ];

        if ($input->getOption('git')) {
            $this->initGit($output, $input->getOption('branch'));
            $messages[] = '✓ init git. Dont forget to set remote url.';
        }

        $io->success($messages);

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

        $this->composerName = \strtolower($prefix) . "/" . (new AsciiSlugger())->slug($class->snake());
    }

    protected function findComposer(): string
    {
        $composerPath = getcwd() . '/composer.phar';

        if (file_exists($composerPath)) {
            return '"'.PHP_BINARY.'" '.$composerPath;
        }

        return 'composer';
    }

    private function initGit(OutputInterface $output, string $branch): void {
        if (file_exists($this->workingDir . DIRECTORY_SEPARATOR . $this->pluginName . DIRECTORY_SEPARATOR . '.git')) {
            $output->write('Git already exists. Skipping.');
            return;
        }

        $commands = [
            'cd ' . $this->workingDir . DIRECTORY_SEPARATOR . $this->pluginName,
            'git init',
            'git add .',
            'git commit -m "PURE shopware plugin"',
            "git branch -M {$branch}",
        ];

        $this->executeCommands($commands, $output);
    }

    protected function executeCommands($commands, OutputInterface $output): Process
    {
        $cli = Process::fromShellCommandline(implode(' && ', $commands));
        $cli->setTty(true);

        $cli->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        return $cli;
    }

    protected function getLatestShopwareVersion(): string {

        try {
            $client = new \GuzzleHttp\Client();
            $get = $client->get('https://api.github.com/repos/shopware/platform/releases/latest', [
                'content-type' => 'application/json'
            ]);
            $content = $get->getBody()->getContents();
            $json = json_decode($content, true);
            return str_replace('v', '', $json['tag_name']);

        } catch (\Exception $exception) {
           return self::DEFAULT_VERSION;
        }
    }
}
