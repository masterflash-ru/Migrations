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

        $this->addArgument('namespace', InputArgument::OPTIONAL, $this->translator->translate('NameSpace'))
            ->setDescription($this->translator->translate('Generate a blank migration class.'));
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
use Mf\Migrations\MigrationInterface;

class %s extends AbstractMigration implements MigrationInterface
{
    public static $description = "Migration description";

    public function up($schema, $adapter)
    {
        switch ($this->db_type){
            case "mysql":{
                $this->addSql("*Sql instruction*");
                break;
            }
            default:{
                throw new \Exception("the database {$this->db_type} is not supported !");
            }
        }
    }

    public function down($schema, $adapter)
    {
        //throw new \RuntimeException(\'No way to go down!\');
        switch ($this->db_type){
            case "mysql":{
                $this->addSql("*Sql instruction*");
                break;
            }
            default:{
                throw new \Exception("the database {$this->db_type} is not supported !");
            }
        }
    }
}
', $migrationNamespace, $className);
    }

}