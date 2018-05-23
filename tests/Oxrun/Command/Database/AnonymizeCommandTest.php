<?php

namespace Oxrun\Command\Database;

use Oxrun\Application;
use Oxrun\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class AnonymizeCommandTest extends TestCase
{
    public function testExecute()
    {
        $app = new Application();
        $app->add(new AnonymizeCommand());

        $command = $app->find('db:anonymize');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                '--debug' => true,
                '--keepdomain' => '@shoptimax.de',
            )
        );

        $this->assertContains('oxaddress', $commandTester->getDisplay());
        $this->assertContains('Anonymizing done.', $commandTester->getDisplay());
    }
}
