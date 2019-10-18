<?php
/**
 */

namespace Mf\Migrations\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputArgument;



class Generate extends AbstractCommand
{

    protected static $defaultName = 'generate';


    protected function configure()
    {

        $this->addArgument('namespace', InputArgument::OPTIONAL, $this->translator->translate('NameSpace'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $migration_folder=rtrim(getcwd().DIRECTORY_SEPARATOR.$this->config["migrations"]['dir'],DIRECTORY_SEPARATOR);
        //создадим папку если ее нет
        if (!is_dir($migration_folder)){
            mkdir($migration_folder,0777);
        }
        
        $namespace=$input->getArgument('namespace');
        
        $className = 'Version' . date('YmdHis', time());
        $classPath = $migration_folder . DIRECTORY_SEPARATOR . $className . '.php';
        
        if (file_exists($classPath)) {
            throw new \Exception(sprintf('Migration %s exists!', $className));
        }
        file_put_contents($classPath, $this->getTemplate($namespace,$className));

        $output->writeln(PHP_EOL."<info>Generated new migration class to ".realpath ($classPath) ."</info>" . PHP_EOL. PHP_EOL);
    }
    
    protected function getTemplate($migrationNamespace,$className)
    {
        return sprintf('<?php

namespace %s;

use Mf\Migrations\AbstractMigration;
use Zend\Db\Metadata\MetadataInterface;

class %s extends AbstractMigration
{
    public static $description = "Migration description";

    public function up(MetadataInterface $schema)
    {
        //$this->addSql(/*Sql instruction*/);
    }

    public function down(MetadataInterface $schema)
    {
        //throw new \RuntimeException(\'No way to go down!\');
        //$this->addSql(/*Sql instruction*/);
    }
}
', $migrationNamespace, $className);
    }

}