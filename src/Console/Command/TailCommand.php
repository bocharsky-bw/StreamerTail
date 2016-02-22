<?php

namespace StreamerTail\Console\Command;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TailCommand extends Command
{
    const DISPLAY_TABLE = 'table';
    const DISPLAY_COMPACT = 'compact';
    const DISPLAY_LIST = 'list';

    protected function configure()
    {
        $this
            ->setName('tail')
            ->setDescription('Display the last part of a data which fetched by specified SQL query.')
            ->addArgument(
                'query',
                InputArgument::REQUIRED,
                'SQL query to tail.'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Wait for additional data to be appended to the input.'
            )
            ->addOption(
                'url',
                'u',
                InputOption::VALUE_OPTIONAL,
                'Database config URL.',
                'mysql://root@localhost'
            )
            ->addOption(
                'sleep',
                's',
                InputOption::VALUE_OPTIONAL,
                'Number in seconds to refresh data.',
                1
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Number of rows to limit.',
                3
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $query = (string)$input->getArgument('query');
        $url = (string)$input->getOption('url');
        $sleep = (int)$input->getOption('sleep');
        $limit = (int)$input->getOption('limit');
        $isWatched = (bool)$input->getOption('force');

        $conn = DriverManager::getConnection([
            'url' => $url,
        ]);
        $countQuery = 'SELECT COUNT(*) FROM '.preg_replace('/^SELECT.+?FROM/i', '', $query);
        $limitQuery = preg_replace('/LIMIT.+?$/i', '', $query).' LIMIT :offset, :limit';

        $count = 0;
        $isFirstLoop = true;
        do {
            $previousCount = $count;
            $count = (int)$conn->fetchColumn($countQuery);
            // Check whether is file truncated
            if ($count < $previousCount) {
                $output->writeln('Table was truncated.');
                continue;
            }

            $incrementCount = $count - $previousCount;
            $offset = $isFirstLoop ? $count - $limit : $previousCount;
            if (0 > $offset) {
                $offset = 0;
            }
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

            $display = self::DISPLAY_TABLE;
            switch ($display) {
                case self::DISPLAY_TABLE:
                    $table = new Table($output);
                    if ($isFirstLoop) {
                        $headlines = array_keys($rows[0]);
                        $table->setHeaders($headlines);
                    }
                    $table->setRows($rows);
                    $table->render();
                break;

                case self::DISPLAY_COMPACT:
                    $table = new Table($output);
                    if ($isFirstLoop) {
                        $headlines = array_keys($rows[0]);
                        $table->setHeaders($headlines);
                    }
                    $table->setRows($rows);
                    $table->setStyle('compact');
                    $table->render();
                break;

                case self::DISPLAY_LIST:
                    $table = new Table($output);
                    foreach ($rows as $row) {
                        foreach ($row as $name => $column) {;
//                            $table->addRow([
//                                sprintf('<options=bold>%s:</> %s', $name, $column),
//                            ]);
                            $table->addRow([
                                sprintf('<options=bold>%s</>', $name),
                                $column,
                            ]);
                        }
                        $table->addRow(['---']);
                    }
                    $table->setStyle('compact');
                    $table->render();
                break;
            }

            $isFirstLoop = false;
            if ($isWatched) {
                sleep($sleep);
            }
        } while ($isWatched);
    }
}
