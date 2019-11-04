<?php
/**
 */

namespace Mf\Migrations\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;






class Status extends AbstractCommand
{


    protected static $defaultName = 'status';
    
    protected function configure()
    {

        $this->addArgument('namespace', InputArgument::OPTIONAL,$this->translator->translate('NameSpace') ,null)
            ->setDescription($this->translator->translate('View the status of a set of migrations.'))
            ->setHelp($this->translator->translate("The <info>%command.name%</info> command outputs the status of a set of migrations:\n<info>%command.full_name%</info>\n"));
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $namespace=$input->getArgument('namespace');
        
        $outputStyle = new OutputFormatterStyle('red',"default",["bold"]);
        $output->getFormatter()->setStyle('newm', $outputStyle); 
        
        $output->writeln(PHP_EOL."<info>== ".$this->translator->translate("Configuration")."</info>");
        
        $table = new Table($output);
        $table ->addRow(['>> '.$this->translator->translate("Connection name:"),  $this->config['migrations']["connection"]] );
        $table ->addRow(['>> '.$this->translator->translate("Database Name:"),  $this->connection->Database] );
        $table ->addRow(['>> '.$this->translator->translate("Migrations Directory:"),  $this->config['migrations']["dir"] ]);
        //$table ->addRow(['>> '.$this->translator->translate("Default Namespace:"),  $this->config['migrations']["default_namespace"] ]);
        $this->readRs();
        $table ->addRow(['>> '.$this->translator->translate("Total Executed Migrations:"),  $this->rs->RecordCount ]);
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
        $output->writeln(PHP_EOL."<question>== ".$this->translator->translate("NameSpace:")."</question>");
        foreach ($namespace_list as $ns){
            //цикл по пространствам и вывод отдельной информации по каждому
            $migrations=$this->searchMigrations($ns);
            $output->writeln(PHP_EOL."<info>== {$ns}</info>");
            $table = new Table($output);
            $table->setStyle('compact');
            $table->setColumnWidths([50]);
            $table ->addRow(["|-> ".$this->translator->translate("Executed Migrations:"),  $this->counter_executed_migrations[$ns]]);
            
            
            $version=0; //ищем текущую версию, по applied=true
            $next_version=0; //следующая это первая из не загруженных
            $last_version=0; //последняя из загруженных
            $new=[];
            foreach ($migrations as $m){
                if ($m['applied']){
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
            
            $table ->addRow(['|-> '.$this->translator->translate("Current Version:"),  $version ]);
            $table ->addRow(['|-> '.$this->translator->translate("Next Version:"),  $next_version ]);
            $table ->addRow(['|-> '.$this->translator->translate("Latest Version:"),  $last_version ]);
            $table ->addRow(['|-> '.$this->translator->translate("Available Migrations:"),  count($new) ]);
            $table->render();
            if (count($new) ){
                $output->writeln("<newm>    == ".$this->translator->translate("Available Migration Versions:")."</newm>");
                //выводим не установленные миграции, если есть
                foreach ($new as $m){
                    $output->writeln("    <newm> >>".$this->datetimeFormat($m["version"])." ({$m["version"]})   not migrated   " .$m["description"] ."</newm>");
                }
            }
            
        }
        
    }
    
}