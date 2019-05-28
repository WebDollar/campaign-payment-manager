<?php

namespace App\Component;

use App\Contracts\Component\IWebDollarTransactionProvider;
use Graze\GuzzleHttp\JsonRpc\Exception\RequestException;
use Graze\GuzzleHttp\JsonRpc\Message\ResponseInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WebDollar\Client\Model\Transaction;
use WebDollar\WebDollarClientBundle\Contracts\WebDollarClientManagerInterface;

/**
 * Class WebDollarTransactionProvider
 * @package App\Component
 */
class WebDollarTransactionProvider implements IWebDollarTransactionProvider
{
    use LoggerAwareTrait;

    /**
     * @var \WebDollar\Client\WebDollarClient
     */
    private $_oWebDollarClient;

    public function __construct(LoggerInterface $oLogger, WebDollarClientManagerInterface $oWebDollarClientManager)
    {
        $this->setLogger($oLogger);
        $this->_oWebDollarClient = $oWebDollarClientManager->getOneClient();
    }

    public function provideByHash(string $hash): ?Transaction
    {
        $oWebDollarTransaction = NULL;

        try
        {
            $oWebDollarTransaction = $this->_oWebDollarClient->getTransactionByHash($hash)->getTransaction();
        }
        catch (RequestException $e)
        {
            $oResponse = $e->getResponse();

            if ($oResponse !== NULL && $oResponse instanceof ResponseInterface)
            {
                $this->logger->error(var_export($oResponse->getRpcErrorData(), TRUE));
            }

            $this->logger->error($e->getMessage());
        }
        catch (\Exception $e)
        {
            $this->logger->error($e->getMessage());
        }
        catch (\Throwable $e)
        {
            $this->logger->error($e->getMessage());
        }

        return $oWebDollarTransaction;
    }
}
