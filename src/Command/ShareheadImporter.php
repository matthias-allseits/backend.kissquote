<?php


namespace App\Command;

use App\Entity\Share;
use App\Entity\ShareheadShare;
use Doctrine\ORM\EntityManagerInterface;
use mysqli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;


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




        // todo: this must work as a updater!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!




        $servername = "localhost";
        $username = "stathead";
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
            $output->writeln("id = " . $row['id'] . ", symbol = " . $row['symbol']);
            $dbCheck = $this->entityManager->getRepository(ShareheadShare::class)->findBy(['isin' => $row['isin']]);
            if (count($dbCheck) > 0) {
                $output->writeln('<info>already existing in kissquote-db</info>');
                continue;
            }
            $share = new ShareheadShare();
            $share->setName($row['name']);
            $share->setShortname($row['symbol'] !== null ? $row['symbol'] : '');
            $share->setIsin($row['isin']);
            $share->setCurrency($currencies[$row['currency_id']]);
            $this->entityManager->persist($share);
        }

        if ($force) {
            $this->entityManager->flush();
        }
    }
}
