<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use ChatApp\Model\Repository;

$console = new Application('ChatApp', 'n/a');
$console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));
$console->setDispatcher($app['dispatcher']);
$console
    ->register('history:clear')
    ->setDefinition(array(
        new InputArgument('days', InputArgument::OPTIONAL, 'Number of days'),
    ))
    ->setDescription('The Command will delete old history data, including related files')
    ->setHelp('Usage: <info>./console.php history:clear [--days]</info>')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {

        if (!$days = $input->getArgument('days')) {
            $days = 60;
        }

        $date = new \DateTime();
        $date->sub(new \DateInterval('P'.intval($days).'D'));

        // delete messages
        $repository = new Repository\ChatMessageRepository($app['orm.em']);

        do {
            // get all messages from date
            $messages = $repository->search(array(
                'to_date' => $date
            ));

            foreach ($messages['result'] as $message) {
                // delete files
                foreach ($message->getFiles() as $file) {
                    if ($file && file_exists($app['media_dir'].$file)) {
                        unlink($app['media_dir'].$file);
                    }
                }

                // delete message
                $repository->delete($message);
            }
        } while ($messages['count'] > 0);

        // delete moments
        $repository = new Repository\MomentRepository($app['orm.em']);

        do {
            // get all moments from date
            $moments = $repository->search(array(
                'to_date' => $date
            ));

            foreach ($moments['result'] as $moment) {
                // delete files
                foreach ($moment->getImages() as $file) {
                    if ($file && file_exists($app['media_dir'].$file)) {
                        unlink($app['media_dir'].$file);
                    }
                }

                // delete message
                $repository->delete($moment);
            }
        } while ($moments['count'] > 0);
    })
;

return $console;
