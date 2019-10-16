<?php

namespace Mf\Migrations;

use Zend\Mvc\Application;
use Zend\Stdlib\ArrayUtils;

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
                PRIMARY KEY (`version`)
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
    * инициализация приложения ZF3, 
    */
    public function ZfInit()
    {
        $appConfig = require  'config/application.config.php';
        if (file_exists( 'config/development.config.php')) {
            $appConfig = ArrayUtils::merge($appConfig, require  'config/development.config.php');
        }
        $zf=Application::init($appConfig);
        $this->ServiceManager=$zf->getServiceManager();
        $this->connection=$this->ServiceManager->get('DefaultSystemDb');
        $this->config=$this->ServiceManager->get('config');
    }
    
    
    
    
}
