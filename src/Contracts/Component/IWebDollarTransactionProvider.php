<?php

namespace App\Contracts\Component;

use Psr\Log\LoggerAwareInterface;
use WebDollar\Client\Model\Transaction;

/**
 * Interface IWebDollarTransactionProvider
 * @package App\Contracts\Component
 */
interface IWebDollarTransactionProvider extends LoggerAwareInterface
{
    /**
     * @param string $hash
     *
     * @return Transaction|null
     */
    public function provideByHash(string $hash): ?Transaction;
}
