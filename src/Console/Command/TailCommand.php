<?php

namespace StreamerTail\Console\Command;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TailCommand extends Command
{
    const DISPLAY_INLINE = 'inline';
    const DISPLAY_TABLE = 'table';
    const DISPLAY_LIST = 'list';

    protected function configure()
    {
        $this
            ->setName('tail')
            ->setDescription('Display the last part of a data which fetched by specified SQL query')
            ->addArgument(
                'query',
                InputArgument::REQUIRED,
                'SQL query to tail'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conn = DriverManager::getConnection([
            'url' => 'mysql://root@localhost',
        ]);
        $query = (string)$input->getArgument('query');
        $countQuery = 'SELECT COUNT(*) FROM '.preg_replace('/^SELECT.+?FROM/i', '', $query);
        $limitQuery = preg_replace('/LIMIT.+?$/i', '', $query).' LIMIT :offset, :limit';

        $count = 0;
        $limit = 3;
        $isFirstLoop = true;
        $sleepSeconds = 2.0; // in seconds

        while (true) {
            $previousCount = $count;
            $count = (int)$conn->fetchColumn($countQuery);
            // Check whether is file truncated
            if ($count < $previousCount) {
                $output->writeln('Table was truncated.');
                continue;
            }

            $incrementCount = $count - $previousCount;
            $offset = $isFirstLoop ? $count - $limit : $previousCount;
            $limit = $isFirstLoop ? $limit : $incrementCount;

            $rows = $conn->fetchAll($limitQuery, [
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

            $display = self::DISPLAY_INLINE;
            switch ($display) {
                case self::DISPLAY_INLINE:
                    // Print headers:
                    if ($isFirstLoop) {
                        $headlines = array_keys($rows[0]);
                        $line = implode("\t", $headlines);
                        $output->writeln(sprintf('<options=bold>%s</>', $line));
                    }

                    foreach ($rows as $row) {
                        $line = implode("\t", $row);
                        $output->writeln($line);
                    }
                break;

                case self::DISPLAY_TABLE:
                    // Print headers:
                    if ($isFirstLoop) {
                        $headlines = array_keys($rows[0]);
                        $line = implode("\t", $headlines);
                        $output->writeln(sprintf('<options=bold>%s</>', $line));
                    }

                    foreach ($rows as $name => $row) {
                        $line = implode("\t", $row);
                        $output->writeln($line);
                    }
                break;

                case self::DISPLAY_LIST:
                    foreach ($rows as $row) {
                        foreach ($row as $name => $column) {;
                            $output->writeln(sprintf('<options=bold>%s: %s</>', $name, $column));
                        }
                        $output->writeln('=====================');
                    }
                break;
            }

            $isFirstLoop = false;
            sleep($sleepSeconds);
        }
    }
}
