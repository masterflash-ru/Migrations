<?php
/**
 */

namespace Mf\Migrations\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;







class Status extends Command
{
    use ConfigDiscoveryTrait;

    protected static $defaultName = 'status';

    
    protected function configure()
    {
        $this->addArgument('namespace', InputArgument::OPTIONAL, 'Namespace',"all");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->ZfInit();
        $namespace=$input->getArgument('namespace');
        if ($namespace=="all"){
            $rs=$this->connection->Execute("select * from migration_versions order by executed_at limit 1");
        } else {
            $rs=$this->connection->Execute("select * from migration_versions where namespace='{$namespace}' order by executed_at desc limit 1");
        }

        
        $this->searchMigrations();
        
        echo  "\n";echo  "\n";
        
        
        $output->writeln(PHP_EOL."<info>== Configuration</info>");
        
        
        $table = new Table($output);
        $table ->addRow(['>> Database Name:',  $this->connection->Database] );
        $table ->addRow(['>> Migrations Namespace:',  $rs->Fields->Item["namespace"]->Value] );
        
        
        
        $table->setStyle('compact');
        $table->setColumnWidths([50]);
        $table->render();
    }
    
}