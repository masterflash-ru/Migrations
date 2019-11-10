<?php
/**
 */

namespace Mf\Migrations\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Mf\Migrations\MigrationInterface;
use Symfony\Component\Console\Exception;




class Migrate extends AbstractCommand
{


    protected static $defaultName = 'migrate';


    protected function configure()
    {

        $this
            ->setDescription(
                $this->translator->translate('Execute a migration to a specified version or the latest available version.')
            )
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                'The version alias (first, prev, next, latest) to migrate to.',
                'latest'
            )
            ->addOption(
                'namespace',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->translator->translate('Namespace migration'),
                false
            )

            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
               $this->translator->translate('Execute the migration as a dry run.')
            )
            ->addOption(
                'write-sql',
                null,
                InputOption::VALUE_OPTIONAL,
              $this->translator->translate('The path to output the migration SQL file instead of executing it. Defaults to current working directory.'),
                false
            )

            /*->addOption(
                'allow-no-migration',
                null,
                InputOption::VALUE_NONE,
                'Do not throw an exception if no migration is available.'
            )
            ->addOption(
                'all-or-nothing',
                null,
                InputOption::VALUE_OPTIONAL,
                'Wrap the entire migration in a transaction.',
                false
            )*/->setHelp(<<<EOT
The <info>%command.name%</info> command executes a migration to a specified version or the latest available version:

    <info>%command.full_name%</info>

You can specify the version you wish to migrate to using an alias:

    <info>%command.full_name% prev</info>
    <info>These alias are defined : first, latest, prev, and next</info>

You can also execute the migration as a <comment>--dry-run</comment>:

    <info>%command.full_name% YYYYMMDDHHMMSS --dry-run</info>

You can output the would be executed SQL statements to a file with <comment>--write-sql</comment>:

    <info>%command.full_name% YYYYMMDDHHMMSS --write-sql</info>
EOT
        );

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version          = (string) $input->getArgument('version');//(YYYYMMDDHHMMSS) or alias (first, prev, next, latest)
        $ns             = $input->getOption('namespace');
        $path             = $input->getOption('write-sql');
        //$allowNoMigration = (bool) $input->getOption('allow-no-migration');
       // $timeAllQueries   = (bool) $input->getOption('query-time');
        $dryRun           = (bool) $input->getOption('dry-run');
       // $allOrNothing     = $this->getAllOrNothing($input->getOption('all-or-nothing'));

        $path=$this->doPatch($path);
        $question = $this->translator->translate('WARNING! You are about to execute a database migration that could result in schema changes and data lost. Are you sure you wish to continue? (y/n)');;
        if (!$dryRun && !$path){
            if (! $this->canExecute($question, $input, $output)) {
                throw new Exception\RuntimeException($this->translator->translate('Migration cancelled!'));
                return 1;
            }
        }
        
        $migrations=$this->searchMigrations($ns);
        $current_version=0;      //ищем текущую версию, по applied=true
        $next_version=0; //следующая это первая из не загруженных
        $last_version=0; //вообще последняя из доступных
        $new=[];         //новые для загрузки
        foreach ($migrations as $m){
            if ($m['applied']){
                $current_version= $m["version"];
            }
            if (empty($next_version) && !$m['applied']){
                $next_version= $m["version"];
            }
            if (!$m['applied']){
                $new[]=$m;
            }
            $last_version=$m["version"];
        }

        //перебираем опять все что нашли и определяем что делать UP или DOWN или NONE
        switch ($version){
            case "latest":{
                //метим все не загруженные для UP, остальное NONE
                foreach ($migrations as &$m){
                    if ($m['applied']){
                        $m["action"]="NONE";
                    } else {
                        $m["action"]="UP";
                    }
                }
                break;
            }
            case 'first':{
                //переход к первой, отменяем все до первой загруженой
                $first=false;
                foreach ($migrations as &$m){
                    if ($m['applied']){
                        if (!$first){
                            $m["action"]="NONE";
                            $first=true;
                        } else {
                            $m["action"]="DOWN";
                        }
                    } else {
                        $m["action"]="NONE";
                    }
                }
                //меняем хронологию на обратную, откат идет от конца
                $migrations->uasort(function ($a, $b) {
                    if ($a['version'] == $b['version']) {
                        return 0;
                    }
                    return ($a['version'] < $b['version']) ? 1 : -1;
                });
                break;
            }
            case 'next':{
                $next=false;
                //ищем текущую загруженную, и метим следующую как UP все остальные пропускакем
                foreach ($migrations as &$m){
                    if ($next){
                        $m["action"]="UP";
                        $next=false;
                    } else {
                        $m["action"]="NONE";
                    }
                    if ($current_version==$m['version']){
                        $next=true;
                    }
                }
                break;
            }
            case 'prev':{
                $migrations=$this->searchMigrations($ns,1);
                if (count($migrations)>1){
                    //меняем хронологию на обратную, откат идет от конца
                    $migrations->uasort(function ($a, $b) {
                        if ($a['version'] == $b['version']) {
                            return 0;
                        }
                        return ($a['version'] < $b['version']) ? 1 : -1;
                    });
                    //ищем текущую загруженную, и метим следующую как DOWN все остальные пропускакем
                    foreach ($migrations as &$m){
                        $m["action"]="NONE";
                        if ($current_version==$m['version']){
                            $m["action"]="DOWN";
                        }
                    }
                } else {
                    if (count($migrations)) {
                        $migrations[0]["action"]="NONE";
                    }
                }
                break;
            }
            default:{
                throw new Exception\RuntimeException("Номер версии миграции не поддерживается");
            }
        }
        
        //ищем тип базы данных, по умолчанию это Mysql
        $db_type="mysql";
        foreach ($this->connection->Properties  as $p ){
            if($p->Name=="driver_name"){
                $db_type=strtolower($p->Value);
                break;
            }
        }
        try {
            $do_transact=false;
            $migrations->rewind();
            unset($m);      //костыли для глюков в PHP
            
            if (!$dryRun && !$path){
                $this->connection->BeginTrans();
                $do_transact=true;
            }

            foreach ($migrations as $m){
                $class_name=$m["namespace"]."\\".$m["class_name"];
                $class=new $class_name($db_type,$this->connection);
                if (!$class instanceof MigrationInterface){
                    continue;
                }
                //собственно выполнение SQL
                $sql_array=[];
                if ($m["action"]=="UP"){
                    $sql_array=$class->getUpSql();
                } elseif($m["action"]=="DOWN"){
                    $sql_array=$class->getDownSql();
                } else {
                    continue;
                }

                $output->writeln(PHP_EOL."<info>".$this->translator->translate("NameSpace:")."  ".$m["namespace"]."</info>");
                $output->writeln("<info>SQL:</info>");

                //собственно выполнение SQL или запись в файл
                $to_file="";
                foreach ($sql_array as $sql){
                    $sql=addcslashes($sql,'$\\');
                    $to_file.=$sql."\n";
                    $output->writeln($sql.PHP_EOL);
                    if (!$dryRun && !$path){
                        $this->connection->Execute($sql);
                    }
                }

                //записываем, если есть что
                if ($to_file && $path !== false){
                    if (!file_put_contents($path, $to_file)){
                         throw new Exception\RuntimeException($this->translator->translate('Unable to create file').": {$path}");
                    }
                    return 0;
                }

                if ($class->isStartMigrationSystem()){
                    $this->connection->CommitTrans();
                    //если у нас вообще старт системы миграции, то прерываем все и выводим что 
                    //нужно запустить процесс миграции вновь
                    $outputStyle = new OutputFormatterStyle('green', 'default', ['bold', 'blink']);
                    $output->getFormatter()->setStyle('startmigration', $outputStyle);
                    $output->writeln('<startmigration> => '.$this->translator->translate('The migration system is initialized, reload the migrations!').' <=</startmigration>');
                    exit ;
                }
                    if (!$dryRun && !$path){
                        if ($m["action"]=="DOWN"){
                            //откат
                            $ns1=str_replace('\\','\\\\',$m["namespace"]);
                            $this->connection->Execute("delete from migration_versions where version='{$m["version"]}' and namespace='{$ns1}'");
                        } else {
                            //накат
                            //запишем в таблицу загрузку
                            $this->rs->AddNew();
                            $this->rs->Fields->Item["version"]->Value=$m["version"];
                            $this->rs->Fields->Item["namespace"]->Value=$m["namespace"];
                            $this->rs->Fields->Item["executed_at"]->Value=date("Y-m-d H:i:s"); 
                            $this->rs->Fields->Item["description"]->Value=$m["description"];
                            $this->rs->Update();
                        }
                    }
                    $output->writeln('<info>'.$m["version"].' - OK</info>');
            }
            if (!$dryRun && !$path && $do_transact){
                $this->connection->CommitTrans();
            }
        } catch (ADOException $e){
            //если ошибка SQL откатываем все и продолжим исключение
            if (!$dryRun && !$path && $do_transact){
                $this->connection->RollbackTrans();
            }
            throw new Exception\RuntimeException($e->getMessage());
        }
    }
    
}