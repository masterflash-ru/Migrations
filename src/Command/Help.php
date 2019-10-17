<?php
/**
 */

namespace Mf\Migrations\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputArgument;



class Help extends Command
{

    protected static $defaultName = 'help';

    

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln(PHP_EOL."<info>HELP:</info>" . PHP_EOL. PHP_EOL);
    }
    
}