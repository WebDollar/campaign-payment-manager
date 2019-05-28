<?php

namespace App\Tests\Unit\Component;

use App\Component\PaymentTransactionChecker;
use App\Component\WebDollarTransactionProvider;
use App\Contracts\Entity\IPayment;
use App\Contracts\Entity\IPaymentTransaction;
use App\Entity\Payment;
use App\Entity\PaymentTransaction;
use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\Test\TestLogger;
use WebDollar\Client\Model\Transaction as WebDollarTransaction;

/**
 * Class TransactionCheckerTest
 * @package App\Tests\Unit\Component
 */
class TransactionCheckerTest extends TestCase
{
    public function testCheckTransactionPendingConfirmation()
    {
        $oWebDollarTransactionProvider = $this->getMockBuilder(WebDollarTransactionProvider::class)->disableOriginalConstructor()->setMethods(['provideByHash'])->getMock();
        $oWebDollarTransactionProvider->method('provideByHash')->willReturnCallback(function() {
            return WebDollarTransaction::constructFrom([
                'hash'         => '1234',
                'createdAtUTC' => Carbon::now()->toDateTimeString(),
            ]);
        });

        $objectManager = $this->createMock(EntityManager::class);
        $oTransactionChecker = new PaymentTransactionChecker($oTestLogger = new TestLogger(), $oWebDollarTransactionProvider, $objectManager);

        $oTransaction = new PaymentTransaction();
        $oTransaction->setCreatedAt(Carbon::now());
        $oTransaction->setHash('1234');
        static::assertEquals(IPaymentTransaction::STATE_PENDING, $oTransaction->getState());

        $oTransactionChecker->checkPaymentTransaction($oTransaction, 5);

        static::assertTrue($oTestLogger->hasRecord('Checking transaction 1234', LogLevel::NOTICE));
        static::assertTrue($oTestLogger->hasRecord('Transaction 1234 status: Unconfirmed', LogLevel::NOTICE));
        static::assertTrue($oTestLogger->hasRecord('Transaction 1234 is unconfirmed. Nothing to do', LogLevel::NOTICE));
    }

    public function testCheckTransactionConfirmed()
    {
        $oWebDollarTransactionProvider = $this->getMockBuilder(WebDollarTransactionProvider::class)->disableOriginalConstructor()->setMethods(['provideByHash'])->getMock();
        $oWebDollarTransactionProvider->method('provideByHash')->willReturnCallback(function() {
            return WebDollarTransaction::constructFrom([
                'hash'         => '1234',
                'createdAtUTC' => Carbon::now()->toDateTimeString(),
                'block_id'     => 1,
                'fee_raw'      => 100000,
            ]);
        });

        $objectManager = $this->createMock(EntityManager::class);
        $oTransactionChecker = new PaymentTransactionChecker($oTestLogger = new TestLogger(), $oWebDollarTransactionProvider, $objectManager);

        $oTransaction = new PaymentTransaction();
        $oTransaction->setCreatedAt(Carbon::now());
        $oTransaction->setHash('1234');
        static::assertEquals(IPaymentTransaction::STATE_PENDING, $oTransaction->getState());

        $oTransactionChecker->checkPaymentTransaction($oTransaction, 5);

        static::assertTrue($oTestLogger->hasRecord('Checking transaction 1234', LogLevel::NOTICE));
        static::assertTrue($oTestLogger->hasRecord('Transaction 1234 status: Confirmed', LogLevel::NOTICE));
        static::assertFalse($oTestLogger->hasRecord('Transaction 1234 is unconfirmed. Nothing to do', LogLevel::NOTICE));
        static::assertTrue($oTestLogger->hasRecord('Transaction 1234 confirmations: 4', LogLevel::NOTICE));

        static::assertEquals(4, $oTransaction->getConfirmations());
    }

    public function testCheckTransactionConfirmAfterMinimumConfirmations()
    {
        $oWebDollarTransactionProvider = $this->getMockBuilder(WebDollarTransactionProvider::class)->disableOriginalConstructor()->setMethods(['provideByHash'])->getMock();
        $oWebDollarTransactionProvider->method('provideByHash')->willReturnCallback(function() {
            return WebDollarTransaction::constructFrom([
                'hash'         => '1234',
                'createdAtUTC' => Carbon::now()->toDateTimeString(),
                'block_id'     => 1,
                'fee_raw'      => 100000,
            ]);
        });

        $objectManager = $this->createMock(EntityManager::class);
        $oTransactionChecker = new PaymentTransactionChecker($oTestLogger = new TestLogger(), $oWebDollarTransactionProvider, $objectManager);

        $oTransaction = new PaymentTransaction();
        $oTransaction->setCreatedAt(Carbon::now());
        $oTransaction->setHash('1234');
        static::assertEquals(IPaymentTransaction::STATE_PENDING, $oTransaction->getState());

        $oPayment = new Payment();
        $oPayment->setState(IPayment::STATE_PENDING);
        $oTransaction->addPayment($oPayment);

        static::assertEquals(IPayment::STATE_PENDING, $oPayment->getState());

        $oTransactionChecker->checkPaymentTransaction($oTransaction, 7);

        static::assertEquals(IPaymentTransaction::STATE_CONFIRMED, $oTransaction->getState());

        foreach ($oTransaction->getPayments() as $oPayment)
        {
            static::assertEquals(IPayment::STATE_COMPLETED, $oPayment->getState());
        }

        static::assertTrue($oTestLogger->hasRecord('Checking transaction 1234', LogLevel::NOTICE));
        static::assertTrue($oTestLogger->hasRecord('Transaction 1234 status: Confirmed', LogLevel::NOTICE));
        static::assertFalse($oTestLogger->hasRecord('Transaction 1234 is unconfirmed. Nothing to do', LogLevel::NOTICE));
        static::assertTrue($oTestLogger->hasRecord('Transaction 1234 confirmations: 6', LogLevel::NOTICE));
        static::assertTrue($oTestLogger->hasRecord('Transaction 1234 settling', LogLevel::NOTICE));

        static::assertEquals(6, $oTransaction->getConfirmations());
    }

    public function testCheckTransactionNotFoundWebDollarTransaction()
    {
        $oWebDollarTransactionProvider = $this->getMockBuilder(WebDollarTransactionProvider::class)->disableOriginalConstructor()->setMethods(['provideByHash'])->getMock();
        $oWebDollarTransactionProvider->method('provideByHash')->willReturnCallback(function() {
            return NULL;
        });

        $objectManager = $this->createMock(EntityManager::class);
        $oTransactionChecker = new PaymentTransactionChecker($oTestLogger = new TestLogger(), $oWebDollarTransactionProvider, $objectManager);

        $oTransaction = new PaymentTransaction();
        $oTransaction->setCreatedAt(Carbon::now());
        $oTransaction->setHash('1234');
        static::assertEquals(IPaymentTransaction::STATE_PENDING, $oTransaction->getState());

        $oPayment = new Payment();
        $oPayment->setState(IPayment::STATE_PENDING);
        $oTransaction->addPayment($oPayment);

        $oTransactionChecker->checkPaymentTransaction($oTransaction, 7);

        foreach ($oTransaction->getPayments() as $oPayment)
        {
            static::assertEquals(IPayment::STATE_PENDING, $oPayment->getState());
        }

        static::assertEquals(IPaymentTransaction::STATE_PENDING, $oTransaction->getState());

        static::assertTrue($oTestLogger->hasRecord('Checking transaction 1234', LogLevel::NOTICE));
        static::assertTrue($oTestLogger->hasRecord('Transaction 1234 was not found', LogLevel::NOTICE));
        static::assertFalse($oTestLogger->hasRecord('Transaction 1234 exceeded the maximum wait time', LogLevel::NOTICE));
    }

    public function testCheckTransactionNotFoundWebDollarTransactionExpiredMaximumWaitTime()
    {
        $oWebDollarTransactionProvider = $this->getMockBuilder(WebDollarTransactionProvider::class)->disableOriginalConstructor()->setMethods(['provideByHash'])->getMock();
        $oWebDollarTransactionProvider->method('provideByHash')->willReturnCallback(function() {
            return NULL;
        });

        $objectManager = $this->createMock(EntityManager::class);
        $oTransactionChecker = new PaymentTransactionChecker($oTestLogger = new TestLogger(), $oWebDollarTransactionProvider, $objectManager);

        $oTransaction = new PaymentTransaction();
        $oTransaction->setCreatedAt(Carbon::now()->subHour());
        $oTransaction->setHash('1234');
        static::assertEquals(IPaymentTransaction::STATE_PENDING, $oTransaction->getState());

        $oPayment = new Payment();
        $oPayment->setState(IPayment::STATE_PENDING);
        $oTransaction->addPayment($oPayment);

        $aPayments = clone $oTransaction->getPayments();

        $oTransactionChecker->checkPaymentTransaction($oTransaction, 7);

        static::assertCount(0, $oTransaction->getPayments());
        foreach ($aPayments as $oPayment)
        {
            static::assertEquals(IPayment::STATE_READY_TO_PAY, $oPayment->getState());
            static::assertNull($oPayment->getPaymentTransaction());
        }

        static::assertEquals(IPaymentTransaction::STATE_PENDING, $oTransaction->getState());

        static::assertTrue($oTestLogger->hasRecord('Checking transaction 1234', LogLevel::NOTICE));
        static::assertTrue($oTestLogger->hasRecord('Transaction 1234 was not found', LogLevel::NOTICE));
        static::assertTrue($oTestLogger->hasRecord('Transaction 1234 exceeded the maximum wait time', LogLevel::NOTICE));
    }
}
