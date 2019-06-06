<?php

namespace App\Command;

use App\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportPaymentsCommand extends Command
{
    protected static $defaultName = 'app:export-payments';

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
            ->setDescription('Add a short description for your command')
            ->addArgument('campaign', InputArgument::REQUIRED, 'Campaign ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $aRows = $this->_oEM->createQueryBuilder()
            ->select(['paymentTransaction.hash', 'address.address', 't.amount'])
            ->from(Payment::class, 't')
            ->innerJoin('t.address', 'address', 't.address_id = address.id')
            ->innerJoin('t.paymentTransaction', 'paymentTransaction', 't.paymentTransaction = paymentTransaction.id')
            ->getQuery()
            ->getArrayResult();

        $aTableRows = [];

        foreach ($aRows as $aRow)
        {
            $aTableRows[] = [$aRow['address'], number_format($aRow['amount'] / 10000, 2), $aRow['hash']];
        }

        $table = new Table($output);
        $table
            ->setHeaders(['Address', 'Amount', 'Tx'])
            ->setRows($aTableRows)
        ;
        $table->render();
    }
}
