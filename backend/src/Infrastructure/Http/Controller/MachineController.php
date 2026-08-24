<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Command\InsertCoinCommand;
use App\Application\Command\InsertCoinCommandHandler;
use App\Application\Command\ReturnCoinsCommand;
use App\Application\Command\ReturnCoinsCommandHandler;
use App\Application\Command\SelectProductCommand;
use App\Application\Command\SelectProductCommandHandler;
use App\Application\Query\GetMachineStateQuery;
use App\Application\Query\GetMachineStateQueryHandler;
use App\Domain\Model\Coin;
use App\Domain\Model\ProductSku;
use App\Infrastructure\Http\Dto\CoinsResponse;
use App\Infrastructure\Http\Dto\InsertCoinRequest;
use App\Infrastructure\Http\Dto\MachineStateResponse;
use App\Infrastructure\Http\Dto\VendingResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/machine')]
final class MachineController
{
    // This challenge models a single physical machine. The identifier is
    // hardcoded here — at the HTTP boundary — rather than in Domain or
    // Application, so that supporting multiple machines later only means
    // reading this ID from the route/request instead of a constant; no
    // business logic would need to change.
    private const string MACHINE_ID = 'machine-01';

    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {
    }

    #[Route('/state', methods: ['GET'])]
    public function state(GetMachineStateQueryHandler $handler): JsonResponse
    {
        $view = $handler(new GetMachineStateQuery(self::MACHINE_ID));

        return $this->json(MachineStateResponse::fromView($view));
    }

    #[Route('/coins', methods: ['POST'])]
    public function insertCoin(Request $request, InsertCoinCommandHandler $handler): JsonResponse
    {
        /** @var InsertCoinRequest $body */
        $body = $this->serializer->deserialize($request->getContent(), InsertCoinRequest::class, 'json');
        $coin = Coin::fromCentsOrFail($body->cents);

        $insertedAmount = $handler(new InsertCoinCommand(self::MACHINE_ID, $coin));

        return $this->json(['insertedAmount' => $insertedAmount->toEuros()]);
    }

    #[Route('/select/{sku}', methods: ['POST'])]
    public function selectProduct(string $sku, SelectProductCommandHandler $handler): JsonResponse
    {
        $productSku = ProductSku::from(strtoupper($sku));

        $result = $handler(new SelectProductCommand(self::MACHINE_ID, $productSku));

        return $this->json(VendingResponse::fromResult($result));
    }

    #[Route('/return', methods: ['POST'])]
    public function returnCoins(ReturnCoinsCommandHandler $handler): JsonResponse
    {
        $returned = $handler(new ReturnCoinsCommand(self::MACHINE_ID));

        return $this->json(CoinsResponse::fromCents($returned));
    }

    private function json(mixed $data): JsonResponse
    {
        return new JsonResponse($this->serializer->serialize($data, 'json'), json: true);
    }
}
