<?php

namespace Tests\App\Repository;

use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Tests\App\Controller\ControllerTestTrait;

class TransactionRepositoryTest extends WebTestCase
{
    use ControllerTestTrait;

    /**
     * @var TransactionRepository
     */
    private $transactionRepository;

    public function testGetTotalAmountCurrentYearByEmail(): void
    {
        // Update all transactions to start of this year
        // so we can test by counting the amount of all transactions
        $this
            ->manager
            ->createQueryBuilder()
            ->update(Transaction::class, 't')
            ->set('t.payboxDateTime', ':donatedAt')
            ->setParameter('donatedAt', new \DateTime('first day of january this year'), 'datetime')
            ->getQuery()
            ->execute()
        ;

        static::assertSame(31000, $this->transactionRepository->getTotalAmountInCentsByEmail('jacques.picard@en-marche.fr'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->init();

        $this->transactionRepository = $this->getTransactionRepository();
    }

    protected function tearDown(): void
    {
        $this->kill();

        $this->transactionRepository = null;

        parent::tearDown();
    }
}
