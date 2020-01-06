<?php

namespace Mf\Migrations\Command;

use Symfony\Component\Console\Command\Command;

use Mf\Migrations\Lib\MigrationsFilterIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use RecursiveIteratorIterator;
use ArrayIterator;
use ReflectionClass;
use ReflectionProperty;
use ADO\Service\RecordSet;
use DateTimeImmutable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Laminas\I18n\Translator\Translator;
use Exception;

/**
 * общий функционал
 */
abstract class AbstractCommand extends Command
{
    
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
    protected $rs=null;
    
    /**
    * переводчик ZF3
    */
    protected $translator;
    
    /*
    * флаг отсутсвия таблицы с миграциями
    */
    protected $have_db_migration=true;
    
    /**
    * счетчик кол-ва загруженных миграций
    * массив, ключ это пространство имен
    */
    protected $counter_executed_migrations=[];
    
    /**
    * инициализация приложения ZF3, 
    */
    public function __construct($ServiceManager)
    {
        $this->ServiceManager=$ServiceManager;
        $this->config=$this->ServiceManager->get('config');
        $this->connection=$this->ServiceManager->get($this->config['migrations']["connection"]);
    
        $this->translator = new Translator();
        $this->translator->addTranslationFile("PhpArray", __DIR__."/../i18/ru.php","default","ru_RU");
        parent::__construct();
    }
    
    /**
    * рекукрсивынй поиск миграций
    * $namespace - пространство имен для выборки, если null то все
    * applied - 1-выбрать только примененные, 0- выбрать только не примененные, null - все
    * $version - номер версии для поиска в формате 20181214101258, null - не учитывать ее
    */
    public function  searchMigrations ($namespace=null, $applied=null,$version=null)
    {
        $alias=null;
        if (in_array($version,['first', 'prev', 'next', 'latest'])){
            $alias=$version;
            $version=null;
        }
        
        $this->counter_executed_migrations=[];
        $dirItr = new RecursiveDirectoryIterator(getcwd(),FilesystemIterator::SKIP_DOTS);
        $filterItr = new MigrationsFilterIterator($dirItr);
        
        foreach(new RecursiveIteratorIterator($filterItr) as $FileInfo) {
            require_once $FileInfo->getpathName();
        }
        $this->readRs();
        
        //ищем среди всех классов все миграции, что бы сделать анализ
        $classes=array_filter(get_declared_classes() ,function($c){return preg_match('/(Version(\d+))/', $c);} );
        $classes_rez = new ArrayIterator();
        
        //на случай, если введеное пространство не будет найдено
        if ($this->have_db_migration){
            $this->rs->Filter="version='-1'";
        }

        foreach ($classes as $class){
            $r=new ReflectionClass($class);
            $ns=$r->getNamespaceName();
            if ((!empty($namespace) && $namespace!=$ns) || !$r->implementsInterface('Mf\Migrations\MigrationInterface')){
                continue;
            }
            $description=$r->getProperty('description');
            preg_match('/(Version(\d+))/', $r->getShortName(), $matches);
            if ($this->have_db_migration){
                $ns1=str_replace('\\','\\\\',$ns); //временный костыль до выяснения работы фильтра
                $this->rs->Filter="version='{$matches[2]}' and namespace='{$ns1}'";
            }
            

            if (
                (is_null($applied) || $applied!==(int)$this->rs->EOF) && 
                (is_null($version) || $version && $version==$matches[2])
            ) {
                $classes_rez->append([
                    "class_name"=>$r->getShortName(),
                    "namespace"=>$ns,
                    'version' =>$matches[2],
                    'description' =>$description->getValue(),
                    'applied' =>boolval($this->rs->RecordCount),
                ]);
                if (!isset($this->counter_executed_migrations[$ns])){
                    $this->counter_executed_migrations[$ns]=0;
                } 
                if (!$this->rs->EOF) {
                    $this->counter_executed_migrations[$ns]++;
                }

            }
        }
        $classes_rez->uasort(function ($a, $b) {
            if ($a['version'] == $b['version']) {
                return 0;
            }
            return ($a['version'] < $b['version']) ? -1 : 1;
        });

        return $classes_rez;
    }
    
    /**
    * форматировать дату-время из имени миграции
    */
    public function datetimeFormat($str)
    {
        return DateTimeImmutable::createFromFormat("YmdHis", $str)->format('Y-m-d H:i:s');
    }
    
    /**
    * чтение и заполнение RS с данными миграций
    */
    protected function readRs()
    {
        if (!is_null($this->rs)) {
            return;
        }
        $this->rs=new RecordSet();
        $this->rs->CursorType =adOpenKeyset;
        $this->rs->MaxRecords=0;
        try {
            $this->rs->Open("select * from migration_versions",$this->connection);
        } catch (Exception $e){
            $this->have_db_migration=false;
        }
    }
    
    protected function askConfirmation(string $question,  InputInterface $input,  OutputInterface $output ) 
    {
        return $this->getHelper('question')->ask(
            $input,
            $output,
            new ConfirmationQuestion($question)
        );
    }

    protected function canExecute(string $question, InputInterface $input, OutputInterface $output) 
    {
        return ! $input->isInteractive() || $this->askConfirmation($question, $input, $output);
    }

    /**
    * обработка пути, если ввели параметр write-sql
    * на выходе строка
    */
    protected function doPatch($path)
    {
        //поработаем с путем, если указали опцию write-sql
        if ($path !== false){
            $now =  new DateTimeImmutable();
            if (is_dir($path)) {
                $path  = realpath($path);
                $path .= '/data/migrations/' . $now->format('YmdHis') . '.sql';
            } else {
                if ($path){
                    $path = getcwd()."/data/migrations/".$path;
                } else {
                    $path = getcwd()."/data/migrations/". $now->format('YmdHis') . '.sql';
                }
            }
        }
        return $path;
    }
}
