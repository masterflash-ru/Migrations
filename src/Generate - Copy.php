<?php
/**
 */

namespace Mf\Migrations;

class Generate
{
    use ConfigDiscoveryTrait;

    /**
     * @var resource
     */
    private $errorStream;

    /**
     * @param string Path to project.
     */
    private $projectDir;

    /**
     * @param string $projectDir Location to resolve project from.
     * @param null|resource $errorStream Stream to which to write errors; defaults to STDERR
     */
    public function __construct($projectDir = '', $errorStream = null)
    {
        $this->projectDir = $projectDir;
        $this->errorStream = is_resource($errorStream) ? $errorStream : STDERR;
    }

    
    public function __invoke($arguments)
    {
        try{
            $migration_config=$this->getMigrationsConfig();
            $migration_folder=rtrim(getcwd().DIRECTORY_SEPARATOR.$migration_config["migrations"]['dir'],DIRECTORY_SEPARATOR);
            //создадим папку если ее нет
            if (!is_dir($migration_folder)){
                mkdir($migration_folder,0777);
            }
            //пространство имен из параметра
            $argument = array_shift($arguments);
            if (empty($argument)){
                $argument=$migration_config["migrations"]['default_namespace'];
            }
            
            $className = 'Version' . date('YmdHis', time());
            $classPath = $migration_folder . DIRECTORY_SEPARATOR . $className . '.php';
            
            if (file_exists($classPath)) {
                throw new \Exception(sprintf('Migration %s exists!', $className));
            }
            //file_put_contents($classPath, $this->getTemplate($argument,$className));
            fwrite (STDOUT,PHP_EOL."Generated new migration class to ".realpath ($classPath)  . PHP_EOL. PHP_EOL);
            
        } catch (\Exception $e){
            fwrite(STDERR,$e->getMessage());
        }
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
