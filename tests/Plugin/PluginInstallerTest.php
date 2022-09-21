<?php

namespace Pure\Installer\Console\Tests\Plugin;

use PHPUnit\Framework\TestCase;
use Pure\Installer\Console\NewPureCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class PluginInstallerTest extends TestCase
{
    public function test_command_creates_new_plugin()
    {
        $testPluginName = 'TestFancyPluginName';
        $executeDirectory = __DIR__ . '/../../test_output';
        $fullPath = $executeDirectory . DIRECTORY_SEPARATOR . $testPluginName;

        if (file_exists($fullPath)) {
            exec('rm -rf ' . $fullPath);
        }

        $this->assertDirectoryDoesNotExist($fullPath, 'Plugin still exist');


        $application = new Application();
        $application->add(new NewPureCommand());
        $command = $application->find('new');
        $command = new CommandTester($command);
        $execute = $command->execute(
            [
                'type' => 'plugin',
                '--pluginName' => $testPluginName,
                '--workingDir' => $executeDirectory,
                '--git' => true
            ]
        );

        $this->assertDirectoryExists($fullPath, 'Plugin dir does not exists');
        $this->assertDirectoryExists($fullPath . DIRECTORY_SEPARATOR . '.git', 'Git dir does not exists');

    }
}
