<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Document\VendingMachineDocument;
use App\Domain\Repository\TransactionLogRepositoryInterface;
use App\Domain\Repository\VendingMachineRepositoryInterface;
use App\Infrastructure\Persistence\Mongo\Document\TransactionLogDocument;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class FunctionalTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected VendingMachineRepositoryInterface $machines;
    protected TransactionLogRepositoryInterface $transactionLogs;
    protected DocumentManager $documentManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = $this->client->getContainer();

        $machines = $container->get(VendingMachineRepositoryInterface::class);
        assert($machines instanceof VendingMachineRepositoryInterface);
        $this->machines = $machines;

        $transactionLogs = $container->get(TransactionLogRepositoryInterface::class);
        assert($transactionLogs instanceof TransactionLogRepositoryInterface);
        $this->transactionLogs = $transactionLogs;

        $documentManager = $container->get(DocumentManager::class);
        assert($documentManager instanceof DocumentManager);
        $this->documentManager = $documentManager;

        $this->documentManager->getDocumentCollection(VendingMachineDocument::class)->deleteMany([]);
        $this->documentManager->getDocumentCollection(TransactionLogDocument::class)->deleteMany([]);
    }

    /** @return array<string, mixed> */
    protected function decodeJson(): array
    {
        $content = $this->client->getResponse()->getContent();
        assert(is_string($content));

        $decoded = json_decode($content, true);
        assert(is_array($decoded));

        return $decoded;
    }
}
