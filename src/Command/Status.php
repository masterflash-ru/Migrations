<?php
/**
 */

namespace Mf\Migrations\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;






class Status extends Command
{
    use ConfigDiscoveryTrait;

    protected static $defaultName = 'status';

    
    protected function configure()
    {
        $this->addArgument('namespace', InputArgument::OPTIONAL, 'Namespace',null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->ZfInit();
        $namespace=$input->getArgument('namespace');
        
        $rs=clone $this->rs;
        
        $outputStyle = new OutputFormatterStyle('red',"default",["bold"]);
        $output->getFormatter()->setStyle('newm', $outputStyle); 
        
        $output->writeln(PHP_EOL."<info>== Configuration</info>");
        
        $table = new Table($output);
        $table ->addRow(['>> Connection name:',  'DefaultSystemDb'] );
        $table ->addRow(['>> Database Name:',  $this->connection->Database] );
        $table ->addRow(['>> Migrations Directory:',  $this->config['migrations']["dir"] ]);
        $table ->addRow(['>> Default Namespace:',  $this->config['migrations']["default_namespace"] ]);
        $table ->addRow(['>> Total Executed Migrations:',  $rs->RecordCount ]);
        $table->setStyle('compact');
        $table->setColumnWidths([50]);
        $table->render();

        $namespace_list=[];
        if (empty($namespace)){
            //получим все что есть, и выделим все пространства имен
            $migrations=$this->searchMigrations();
            foreach ($migrations as $m){
                $namespace_list[]=$m["namespace"];
            }
            $namespace_list=array_unique($namespace_list);
        } else {
            $namespace_list[]=$namespace;
        }
        $output->writeln(PHP_EOL."<question>== NameSpace:</question>");
        foreach ($namespace_list as $ns){
            //цикл по пространствам и вывод отдельной информации по каждому
            $migrations=$this->searchMigrations($ns);
            $output->writeln(PHP_EOL."<info>== {$ns}</info>");
            $table = new Table($output);
            $table->setStyle('compact');
            $table->setColumnWidths([50]);
            $table ->addRow(['>> Executed Migrations:',  $this->rs->RecordCount ]);
            
            //ищем текущую версию, по applied=true
            $version=0;
            $next_version=0;
            $last_version=0;
            $new=[];
            foreach ($migrations as $m){
                if (empty($version) && $m['applied']){
                    $version= $this->datetimeFormat($m["version"])." ({$m["version"]})";
                }
                if (empty($next_version) && !$m['applied']){
                    $next_version= $this->datetimeFormat($m["version"])." ({$m["version"]})";
                }
                if (!$m['applied']){
                    $new[]=$m;
                }
                $last_version=$this->datetimeFormat($m["version"])." ({$m["version"]})";
            }
            
            $table ->addRow(['>> Current Version:',  $version ]);
            $table ->addRow(['>> Next Version:',  $next_version ]);
            $table ->addRow(['>> Latest Version:',  $last_version ]);
            $table ->addRow(['>> Available Migrations:',  count($new) ]);
            $table->render();
            if (count($new) ){
                $output->writeln("<newm>    == Available Migration Versions:</newm>");
                //выводим не установленные миграции, если есть
                foreach ($new as $m){
                    $output->writeln("    <newm> >>".$this->datetimeFormat($m["version"])." ({$m["version"]})   not migrated   " .$m["description"] ."</newm>");
                }
            }
            
        }
        
    }
    
}