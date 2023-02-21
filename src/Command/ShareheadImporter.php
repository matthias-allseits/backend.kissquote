<?php


namespace App\Command;

use App\Entity\ShareheadShare;
use Doctrine\ORM\EntityManagerInterface;
use mysqli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class ShareheadImporter extends Command
{
    protected static $defaultName = 'kissquote:sharehead-import';
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Imports and updates all shares from sharehead')
            ->setHelp('Imports and updates all shares from sharehead')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Forces the flush')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('force')) {
            $force = true;
        } else {
            $force = false;
        }

        /** @var ShareheadShare[] $allExistingShares */
        $allExistingShares = $this->entityManager->getRepository(ShareheadShare::class)->findAll();
        $allExistingPointer = [];
        foreach($allExistingShares as $share) {
            $allExistingPointer[$share->getShareheadId()] = $share;
        }

        // sharehead data
        $servername = "localhost";
        $username = "root";
        $password = "halvar";
        // Create connection
        $conn = new mysqli($servername, $username, $password);
        $currencies = [];
        $result = $conn->query("SELECT * FROM sharehead.currency;");
        foreach($result as $row) {
            $currencies[$row['id']] = $row['name'];
        }

        $result = $conn->query("SELECT * FROM sharehead.share;");
        foreach ($result as $row) {
            $output->writeln("id = " . $row['id'] . ", name = " . $row['name']);
            $share = $this->entityManager->getRepository(ShareheadShare::class)->findOneBy(['shareheadId' => $row['id']]);
            if (null !== $share) {
                $output->writeln('<info>already existing in kissquote-db. update it</info>');
            } else {
                $output->writeln('<comment>new entry to create</comment>');
                $share = new ShareheadShare();
            }
            $share->setShareheadId($row['id']);
            $share->setName($row['name']);
            $share->setShortname($row['symbol'] !== null ? $row['symbol'] : '');
            $share->setIsin($row['isin']);
            $share->setCurrency($currencies[$row['currency_id']]);
            $this->entityManager->persist($share);

            unset($allExistingPointer[$row['id']]);
        }
        $output->writeln('---------------------------------');
        foreach($allExistingPointer as $toDelete) {
            $output->writeln('<comment>obsolete entry found:</comment>');
            $output->writeln($toDelete);
        }

        if ($force) {
            $this->entityManager->flush();
        }
    }
}
