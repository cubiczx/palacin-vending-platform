<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Command\RestockProductCommand;
use App\Application\Command\RestockProductCommandHandler;
use App\Application\Command\SetChangeInventoryCommand;
use App\Application\Command\SetChangeInventoryCommandHandler;
use App\Application\Command\UpdateProductPriceCommand;
use App\Application\Command\UpdateProductPriceCommandHandler;
use App\Application\Query\GetFullMachineStateQuery;
use App\Application\Query\GetFullMachineStateQueryHandler;
use App\Application\Query\GetTransactionHistoryQuery;
use App\Application\Query\GetTransactionHistoryQueryHandler;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Model\Money;
use App\Domain\Model\ProductSku;
use App\Infrastructure\Http\Dto\FullMachineStateResponse;
use App\Infrastructure\Http\Dto\RestockProductRequest;
use App\Infrastructure\Http\Dto\SetChangeInventoryRequest;
use App\Infrastructure\Http\Dto\UpdateProductPriceRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/service')]
final class ServiceController
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
    public function state(GetFullMachineStateQueryHandler $handler): JsonResponse
    {
        $view = $handler(new GetFullMachineStateQuery(self::MACHINE_ID));

        return $this->json(FullMachineStateResponse::fromView($view));
    }

    #[Route('/products/{sku}/restock', methods: ['POST'])]
    public function restock(string $sku, Request $request, RestockProductCommandHandler $handler): JsonResponse
    {
        $productSku = ProductSku::tryFrom(strtoupper($sku))
            ?? throw ProductNotFoundException::forUnknownSku($sku);

        /** @var RestockProductRequest $body */
        $body = $this->serializer->deserialize($request->getContent(), RestockProductRequest::class, 'json');

        $handler(new RestockProductCommand(self::MACHINE_ID, $productSku, $body->quantity));

        return new JsonResponse(null, 204);
    }

    #[Route('/products/{sku}/price', methods: ['PATCH'])]
    public function updatePrice(string $sku, Request $request, UpdateProductPriceCommandHandler $handler): JsonResponse
    {
        $productSku = ProductSku::tryFrom(strtoupper($sku))
            ?? throw ProductNotFoundException::forUnknownSku($sku);

        /** @var UpdateProductPriceRequest $body */
        $body = $this->serializer->deserialize($request->getContent(), UpdateProductPriceRequest::class, 'json');

        $handler(new UpdateProductPriceCommand(
            self::MACHINE_ID,
            $productSku,
            Money::fromEuros($body->price),
        ));

        return new JsonResponse(null, 204);
    }

    #[Route('/change', methods: ['POST'])]
    public function setChangeInventory(Request $request, SetChangeInventoryCommandHandler $handler): JsonResponse
    {
        /** @var SetChangeInventoryRequest $body */
        $body = $this->serializer->deserialize($request->getContent(), SetChangeInventoryRequest::class, 'json');

        $counts = [];
        foreach ($body->counts as $cents => $quantity) {
            $counts[(int) $cents] = $quantity;
        }

        $handler(new SetChangeInventoryCommand(self::MACHINE_ID, $counts));

        return new JsonResponse(null, 204);
    }

    #[Route('/transactions', methods: ['GET'])]
    public function transactions(Request $request, GetTransactionHistoryQueryHandler $handler): JsonResponse
    {
        $productParam = $request->query->get('product');

        // Date-range filtering (?from=&to=) is part of GetTransactionHistoryQuery
        // and already supported by the repository/query layer, but parsing
        // those query params into DateTimeImmutable is intentionally left out
        // here to keep the initial scope focused. Wiring them up is a small,
        // isolated change confined to this method.
        $result = $handler(new GetTransactionHistoryQuery(
            from: null,
            to: null,
            product: $productParam !== null ? ProductSku::from(strtoupper($productParam)) : null,
            page: (int) $request->query->get('page', '1'),
            perPage: (int) $request->query->get('perPage', '20'),
        ));

        return $this->json($result);
    }

    private function json(mixed $data): JsonResponse
    {
        return new JsonResponse($this->serializer->serialize($data, 'json'), json: true);
    }
}
