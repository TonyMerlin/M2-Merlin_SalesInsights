<?php
declare(strict_types=1);

namespace Merlin\SalesInsights\Console\Command;

use Merlin\SalesInsights\Api\AggregatorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RebuildSalesInsightsCommand extends Command
{
    public function __construct(
        private readonly AggregatorInterface $aggregator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('merlin:salesinsights:rebuild')
            ->setDescription('Rebuild Merlin Sales Insights aggregates')
            ->addArgument('from', InputArgument::OPTIONAL, 'From date YYYY-MM-DD')
            ->addArgument('to', InputArgument::OPTIONAL, 'To date YYYY-MM-DD');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $from = $input->getArgument('from');
        $to = $input->getArgument('to');

        $output->writeln('<info>Rebuilding Merlin Sales Insights aggregates...</info>');
        $this->aggregator->rebuild($from ?: null, $to ?: null);
        $output->writeln('<info>Done.</info>');

        return Command::SUCCESS;
    }
}
