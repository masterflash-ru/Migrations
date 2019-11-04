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
use DateTimeImmutable;
use Exception as ADOException;


class Execute extends AbstractCommand
{


    protected static $defaultName = 'execute';


    protected function configure()
    {

        $this
            ->setDescription(
                $this->translator->translate('Execute a single migration version up or down manually.')
            )
            ->addArgument(
                'version',
                InputArgument::REQUIRED,
                'The version to execute.',
                null
            )
            ->addOption(
                'write-sql',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->translator->translate('The path to output the migration SQL file instead of executing it. Defaults to current working directory.'),
                false
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
                'up',
                null,
                InputOption::VALUE_NONE,
                $this->translator->translate('Execute the migration up.')
            )
            ->addOption(
                'down',
                null,
                InputOption::VALUE_NONE,
                $this->translator->translate('Execute the migration down.')
            )
            ->setHelp(<<<EOT
The <info>%command.name%</info> command executes a single migration version up or down manually:

    <info>%command.full_name% YYYYMMDDHHMMSS</info>

If no <comment>--up</comment> or <comment>--down</comment> option is specified it defaults to up:

    <info>%command.full_name% YYYYMMDDHHMMSS --down</info>

You can also execute the migration as a <comment>--dry-run</comment>:

    <info>%command.full_name% YYYYMMDDHHMMSS --dry-run</info>

You can output the would be executed SQL statements to a file with <comment>--write-sql</comment>:

    <info>%command.full_name% YYYYMMDDHHMMSS --write-sql</info>
EOT
        );

        parent::configure();

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version        = $input->getArgument('version');
        $ns             = $input->getOption('namespace');
        $dryRun         = (bool) $input->getOption('dry-run');
        $path           = $input->getOption('write-sql');
        $direction      = $input->getOption('down') !== false
            ? $applied=1    //выбрать только загруженные, для down
            : $applied=0;   //выбрать только не загруженные, для up
        $path=$this->doPatch($path);

        $question = $this->translator->translate('WARNING! You are about to execute a database migration that could result in schema changes and data lost. Are you sure you wish to continue? (y/n)');;
        if (!$dryRun && !$path){
            if (! $this->canExecute($question, $input, $output)) {
                throw new Exception\RuntimeException($this->translator->translate('Migration cancelled!'));
                return 1;
            }
        }
        
        //загрузка миграций и выполнение
        $migrations=$this->searchMigrations($ns,$applied,$version);
        
        //ищем тип базы данных, по умолчанию это Mysql
        $db_type="mysql";
        foreach ($this->connection->Properties  as $p ){
            if($p->Name=="driver_name"){
                $db_type=strtolower($p->Value);
                break;
            }
        }

        if (count($migrations)==0) {
            throw new Exception\RuntimeException($this->translator->translate('Migration not found!'));
            return 1;
        }
        if (count($migrations)>1) {
            throw new Exception\RuntimeException($this->translator->translate('Multiple versions of migrations detected, use the --namespace option'));
            return 1;
        }
        
        try {
            $do_transact=false;
            foreach ($migrations as $m){
                $output->writeln(PHP_EOL."<info>".$this->translator->translate("NameSpace:")."  ".$m["namespace"]."</info>");
                $output->writeln("<info>SQL:</info>");
                $class_name=$m["namespace"]."\\".$m["class_name"];
                $class=new $class_name($db_type,$this->connection);
                if (!$class instanceof MigrationInterface){
                    continue;
                }
                if ($applied){
                    //откат
                    $sql_array=$class->getDownSql();
                } else {
                    //накат
                    $sql_array=$class->getUpSql();
                }
                //собственно выполнение SQL или запись в файл
                $to_file="";
                if (!$dryRun && !$path){
                    $this->connection->BeginTrans();
                    $do_transact=true;
                }
                foreach ($sql_array as $sql){
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
                    return 0 ;
                }
                if (!$dryRun && !$path){
                    if ($applied){
                        //откат
                        $this->connection->Execute("delete from migration_versions where version='{$m["version"]}' and namespace='{$m["namespace"]}'");
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
            $this->connection->RollbackTrans();
            throw new Exception\RuntimeException($e->getMessage());
        }
    }
    
}