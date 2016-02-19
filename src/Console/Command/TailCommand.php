<?php

namespace StreamerTail\Console\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TailCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('tail:table')
            ->setDescription('Tail a database')
            ->addArgument(
                'table',
                InputArgument::REQUIRED,
                'Table name to tail'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conn = \Doctrine\DBAL\DriverManager::getConnection([
            'url' => 'mysql://root@localhost/test_db',
        ]);
        $table = (string)$input->getArgument('table');

        $count = 0;
        $offset = 0;
        $limit = 3;
        $isFirstLoop = true;
        $sleepSeconds = 2.0; // in seconds

        while (true) {
            $previousCount = $count;
            $count = (int)$conn->fetchColumn("SELECT COUNT(*) FROM {$table}");
            // Check whether is file truncated
            if ($count < $previousCount) {
                $output->writeln('Table was truncated.');
                continue;
            }

            $incrementCount = $count - $previousCount;
            $offset = $isFirstLoop ? $count - $limit : $previousCount;
            $limit = $isFirstLoop ? $limit : $incrementCount;

            $rows = $conn->fetchAll("SELECT * FROM {$table} LIMIT :offset, :limit", [
                'offset' => $offset,
                'limit' => $limit,
            ], [
                'offset' => \PDO::PARAM_INT,
                'limit' => \PDO::PARAM_INT,
            ]);

            // Determine line length
            $lengthOfLines = [];
            foreach ($rows as $row) {
                foreach ($row as $name => $column) {
                    $length = isset($lengthOfLines[$name]) ? $lengthOfLines[$name] : 0;
                    if (strlen($column) > $length) {
                        $lengthOfLines[$name] = strlen($column);
                    }
                }
            }

            if (true) {
                // DISPLAY_TABLE

                // Print headers:
                if ($isFirstLoop) {
                    $headlines = array_keys($rows[0]);
                    $line = implode("\t", $headlines);
                    $output->writeln("<options=bold>{$line}</>");
                }
                foreach ($rows as $name => $row) {
                    $line = implode("\t", $row);
                    $output->writeln($line);
                }
            } elseif (true) {
                // DISPLAY_INLINE

                foreach ($rows as $row) {
                    $line = implode("\t", $row);
                    $output->writeln($line);
                }
            } else {
                // DISPLAY_LIST

                foreach ($rows as $row) {
                    foreach ($row as $name => $column) {
                        $output->write("<options=bold>{$name}: </>");
                        $output->writeln($column);
                    }
                    $output->writeln('=====================');
                }
            }

            $isFirstLoop = false;
            sleep($sleepSeconds);
        }
    }
}
