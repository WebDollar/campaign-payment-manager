<?php

namespace App\Command;

use App\Entity\Address;
use App\Entity\Campaign;
use App\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportFromCsvCommand extends Command
{
    protected static $defaultName = 'app:import-from-csv';

    /**
     * @var EntityManagerInterface
     */
    private $_oEM;

    public function __construct(EntityManagerInterface $oEM)
    {
        parent::__construct(NULL);

        $this->_oEM = $oEM;
    }

    protected function configure()
    {
        $this
            ->setDescription('Import from a CSV file command')
            ->addArgument('file', InputArgument::REQUIRED, 'File Path')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $csv = Reader::createFromPath($input->getArgument('file'), 'r');
        $csv->setDelimiter(',');
        $records = $csv->getRecords();

        $aAddresses = [];

        foreach ($records as $nKey => $record)
        {
            if ($nKey === 0)
            {
                continue;
            }

            if (!isset($aAddresses[$record[2]]))
            {
                $aAddresses[$record[2]] = 0;
            }

            $aAddresses[$record[2]] += (float) $record[1];
        }

        $oCampaign = $this->_oEM->getRepository(Campaign::class)->find(1);

        foreach ($aAddresses as $sAddress => $fAmount)
        {
            $oAddress = $this->_oEM->getRepository(Address::class)->findOneBy(['address' => $sAddress]);

            if ($oAddress === NULL)
            {
                $oAddress = new Address();
                $oAddress->setAddress($sAddress);
            }

            $oPayment = new Payment();
            $oPayment->setAddress($oAddress);
            $oPayment->setCampaign($oCampaign);
            $oPayment->setAmount($fAmount * 10000);

            $this->_oEM->persist($oPayment);
        }

        $this->_oEM->flush();

        $output->writeln(sprintf('Imported %s addresses', \count($aAddresses)));
    }
}
