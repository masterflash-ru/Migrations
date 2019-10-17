<?php

namespace Mf\Migrations\Command;

use Zend\Mvc\Application;
use Zend\Stdlib\ArrayUtils;
use Mf\Migrations\Lib\MigrationsFilterIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use RecursiveIteratorIterator;
use ArrayIterator;
use ReflectionClass;
use ReflectionProperty;
use ADO\Service\RecordSet;


/**
 * Shared functionality
 */
trait ConfigDiscoveryTrait
{
    //SQL создания тублицы для разных DB
    protected $create=[
        "mysql"=>"CREATE TABLE `migration_versions` (
                `version` varchar(20) DEFAULT NULL COMMENT 'версия файла (вырезано из имени)',
                `namespace` char(255) DEFAULT NULL COMMENT 'пространство имен',
                `executed_at` datetime DEFAULT NULL COMMENT 'дата загрузки миграции',
                PRIMARY KEY (`version`),
                KEY `namespace` (`namespace`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
    ];
    
    /**
    * ServiceManager ZF3 полностью инициализаированный
    */
    protected $ServiceManager;
    
    /**
    * соединение с базой для работы с ADO
    */
    protected $connection;
    
    /**
    * конфиг приложения
    */
    protected $config=[];
    
    /**
    * RS выборки всех миграций из базы
    */
    protected $rs;
    
    /**
    * инициализация приложения ZF3, 
    */
    public function ZfInit()
    {
        $appConfig = require  getcwd().'/config/application.config.php';
        if (file_exists( getcwd().'/config/development.config.php')) {
            $appConfig = ArrayUtils::merge($appConfig, require  getcwd().'/config/development.config.php');
        }
        $zf=Application::init($appConfig);
        $this->ServiceManager=$zf->getServiceManager();
        $this->connection=$this->ServiceManager->get('DefaultSystemDb');
        $this->config=$this->ServiceManager->get('config');
        $this->rs=new RecordSet();
        $this->rs->CursorType =adOpenKeyset;
        $this->rs->MaxRecords=0;
        $this->rs->Open("select * from migration_versions",$this->connection);
    }
    
    /**
    * рекукрсивынй поиск миграций
    * $namespace - пространство имен для выборки, если null то все
    * applied - 1-выбрать только примененные, 0- выбрать только не примененные, null - все
    */
    public function  searchMigrations ($namespace=null, $applied=null)
    {
        $dirItr = new RecursiveDirectoryIterator(getcwd(),FilesystemIterator::SKIP_DOTS);
        $filterItr = new MigrationsFilterIterator($dirItr);
        
        foreach(new RecursiveIteratorIterator($filterItr) as $FileInfo) {
            require_once $FileInfo->getpathName();
        }
        
        //ищем среди всех классов все миграции, что бы сделать анализ
        $classes=array_filter(get_declared_classes() ,function($c){return preg_match('/(Version(\d+))/', $c);} );
        $classes_rez = new ArrayIterator();
        foreach ($classes as $class){
            
            $r=new ReflectionClass($class);
            $ns=$r->getNamespaceName();
            if ((!empty($namespace) && $namespace!=$ns) || !$r->implementsInterface('Mf\Migrations\MigrationInterface')){
                continue;
            }
            $description=$r->getProperty('description');
            preg_match('/(Version(\d+))/', $r->getShortName(), $matches);
            $this->rs->Find("version='{$matches[2]}' and namespace='{$ns}'");
            
            if (is_null($applied) || $applied!==(int)$this->rs->EOF) {
                $classes_rez->append([
                    "class_name"=>$r->getShortName(),
                    "namespace"=>$ns,
                    'version' =>$matches[2],
                    'description' =>$description->getValue(),
                    'applied' =>!$this->rs->EOF,
                ]);
            }
        }
            print_r($classes_rez);
        

    }
    
}
