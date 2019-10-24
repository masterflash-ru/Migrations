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
//use Exception;
use Symfony\Component\Console\Exception;


class Version extends AbstractCommand
{

    protected static $defaultName = 'version';


    protected function configure()
    {

        $this
            ->setDescription($this->translator->translate('Manually add and delete migration versions from the version table.'))
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                $this->translator->translate('The version to add or delete.'),
                null
            )
            ->addOption(
                'add',
                null,
                InputOption::VALUE_NONE,
                $this->translator->translate('Add the specified version.')
            )
            ->addOption(
                'delete',
                null,
                InputOption::VALUE_NONE,
                $this->translator->translate('Delete the specified version.')
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                $this->translator->translate('Apply to all the versions.')
            )
            /*->addOption(
                'range-from',
                null,
                InputOption::VALUE_OPTIONAL,
                'Apply from specified version.'
            )
            ->addOption(
                'range-to',
                null,
                InputOption::VALUE_OPTIONAL,
                'Apply to specified version.'
            )*/
            ->setHelp(<<<EOT
The <info>%command.name%</info> command allows you to manually add, delete or synchronize migration versions from the version table:

    <info>%command.full_name% YYYYMMDDHHMMSS --add</info>

If you want to delete a version you can use the <comment>--delete</comment> option:

    <info>%command.full_name% YYYYMMDDHHMMSS --delete</info>

If you want to synchronize by adding or deleting all migration versions available in the version table you can use the <comment>--all</comment> option:

    <info>%command.full_name% --add --all</info>
    <info>%command.full_name% --delete --all</info>

If you want to synchronize by adding or deleting some range of migration versions available in the version table you can use the <comment>--range-from/--range-to</comment> option:

    <info>%command.full_name% --add --range-from=YYYYMMDDHHMMSS --range-to=YYYYMMDDHHMMSS</info>
    <info>%command.full_name% --delete --range-from=YYYYMMDDHHMMSS --range-to=YYYYMMDDHHMMSS</info>

You can also execute this command without a warning message which you need to interact with:

    <info>%command.full_name% --no-interaction</info>
EOT
            );

        parent::configure();

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('add') === false && $input->getOption('delete') === false) {
            throw new Exception\InvalidOptionException($this->translator->translate('You must specify whether you want to --add or --delete the specified version.'));
        }
        $affectedVersion = $input->getArgument('version');
        $allOption       = $input->getOption('all');
        
        if ($affectedVersion && $allOption) {
            throw new Exception\InvalidOptionException($this->translator->translate('Parameter conflict: migration version and --all key'));
        }

        $question = $this->translator->translate('WARNING! You are about to add, delete or synchronize migration versions from the version table that could result in data lost. Are you sure you wish to continue? (y/n)');;

        if (! $this->canExecute($question, $input, $output)) {
            throw new Exception\RuntimeException($this->translator->translate('Migration cancelled!'));
            return 1;
        }
        $migrations=$this->searchMigrations(null,(int)$input->getOption('delete'),$affectedVersion);
        if (count($migrations)==0) {
            throw new Exception\RuntimeException($this->translator->translate('Migration not found!'));
            return 1;
        }
        
        if ($input->getOption('add')){
            //добавление
            foreach ($migrations as $m){
                //запишем в таблицу загрузку
                $this->rs->AddNew();
                $this->rs->Fields->Item["version"]->Value=$m["version"];
                $this->rs->Fields->Item["namespace"]->Value=$m["namespace"];
                $this->rs->Fields->Item["executed_at"]->Value=date("Y-m-d H:i:s"); 
                $this->rs->Fields->Item["description"]->Value=$m["description"];
                $this->rs->Update();
                $output->writeln('<info>'.$m["version"].' - OK</info>');
            }
        } else {
            //удаление
            $this->rs->Filter="";
            foreach ($migrations as $m){
                $this->connection->Execute("delete from migration_versions where version='{$m["version"]}'");
                $output->writeln('<info>'.$m["version"].' - OK</info>');
            }
        }

    }
    
}