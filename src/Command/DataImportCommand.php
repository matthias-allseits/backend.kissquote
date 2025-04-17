<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class DataImportCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('kissquote:dataimport')
            ->setDescription('Imports the live data from the last backup')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = '/home/allseits/Dropbox/backup/kissquote.sql';
        if (is_file($file)) {
            $output->writeln('<info>file found: ' . $file . '</info>');
        } else {
            $output->writeln('<error>file not found: ' . $file . '</error>');
            $output->writeln('<error>Abort</error>');
            die;
        }
        exec('mysql -u stathead -phalvar kissquote < ~/Dropbox/backup/kissquote.sql', $execOutput);
        foreach($execOutput as $op) {
            $output->writeln('<info>' . $op . '</info>');
        }
        $output->writeln('<info>import finished</info>');

        return Command::SUCCESS;
    }

}
