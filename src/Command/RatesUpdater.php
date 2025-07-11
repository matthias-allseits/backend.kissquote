<?php

namespace App\Command;

use App\Entity\Position;
use App\Entity\Stockrate;
use App\Service\SwissquoteService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class RatesUpdater extends Command
{
    private EntityManagerInterface $entityManager;
    private bool $verbose;
    private SwissquoteService $swissquoteService;

    public function __construct(EntityManagerInterface $entityManager, SwissquoteService $swissquoteService)
    {
        $this->entityManager = $entityManager;
        $this->swissquoteService = $swissquoteService;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('kissquote:rates-update')
            ->setDescription('Checks and updates rates for a given position')
            ->setHelp('Checks and updates rates for a given position')
            ->addArgument('positionId', InputArgument::REQUIRED, 'Position ID')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Forces the flush')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->verbose = $input->getOption('verbose');
        $positionId = $input->getArgument('positionId');
        if ($input->getOption('force')) {
            $force = true;
        } else {
            $force = false;
        }

        $position = $this->entityManager->getRepository(Position::class)->find($positionId);
        $output->writeln($position);
        $startDate = $position->getActiveFrom();

        $existingRates = $this->entityManager->getRepository(Stockrate::class)->findBy(
            [
                'currencyName' => $position->getShare()->getCurrency()->getName(),
                'isin' => $position->getShare()->getIsin(),
                'marketplace' => $position->getShare()->getMarketplace(),
            ]
        );
        $output->writeln('existing rates count: ' . count($existingRates));
        $existingCollection = [];
        foreach ($existingRates as $existingRate) {
            $existingCollection[$existingRate->getDate()->format('Y-m-d')] = $existingRate;
        }

        $swissquoteRates = $this->swissquoteService->getQuotesByShare($position->getShare());
        $output->writeln('swissquote rates count: ' . count($swissquoteRates));

        foreach($swissquoteRates as $swissquoteRate) {
            if ($swissquoteRate->getDate() > $startDate && !isset($existingCollection[$swissquoteRate->getDate()->format('Y-m-d')])) {
                $output->writeln('missing rate: ' . $swissquoteRate->getDate()->format('Y-m-d'));
                $newRate = new Stockrate();
                $newRate->setDate($swissquoteRate->getDate());
                $newRate->setRate($swissquoteRate->getRate());
                $newRate->setHigh($swissquoteRate->getHigh());
                $newRate->setLow($swissquoteRate->getLow());
                $newRate->setIsin($position->getShare()->getIsin());
                $newRate->setMarketplace($position->getShare()->getMarketplace());
                $newRate->setCurrencyName($position->getShare()->getCurrency()->getName());
                $this->entityManager->persist($newRate);
                if ($force) {
                    $this->entityManager->flush();
                }
            }
        }

        return Command::SUCCESS;
    }

}
