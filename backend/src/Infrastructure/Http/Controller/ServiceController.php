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
use App\Domain\Exception\InvalidProductFilterException;
use App\Domain\Exception\InvalidProductPriceException;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Model\Money;
use App\Domain\Model\ProductSku;
use App\Infrastructure\Http\Dto\ErrorResponse;
use App\Infrastructure\Http\Dto\FullMachineStateResponse;
use App\Infrastructure\Http\Dto\RestockProductRequest;
use App\Infrastructure\Http\Dto\SetChangeInventoryRequest;
use App\Infrastructure\Http\Dto\TransactionHistoryResponse;
use App\Infrastructure\Http\Dto\UpdateProductPriceRequest;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/service')]
#[OA\Tag(name: 'Service')]
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
    #[OA\Get(
        summary: 'Get the machine\'s full operational state',
        description: 'Unlike GET /api/machine/state, this exposes exact stock counts and the full change inventory breakdown — service/admin-facing, not intended for end customers.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Full machine state',
                content: new OA\JsonContent(ref: new Model(type: FullMachineStateResponse::class)),
            ),
        ],
    )]
    public function state(GetFullMachineStateQueryHandler $handler): JsonResponse
    {
        $view = $handler(new GetFullMachineStateQuery(self::MACHINE_ID));

        return $this->json(FullMachineStateResponse::fromView($view));
    }

    #[Route('/products/{sku}/restock', methods: ['POST'])]
    #[OA\Post(
        summary: 'Restock a product',
        description: 'Adds the given quantity to the product\'s current stock.',
        parameters: [
            new OA\Parameter(
                name: 'sku',
                in: 'path',
                required: true,
                description: 'Product identifier',
                schema: new OA\Schema(type: 'string', enum: [ProductSku::WATER, ProductSku::JUICE, ProductSku::SODA]),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: RestockProductRequest::class)),
        ),
        responses: [
            new OA\Response(response: 204, description: 'Stock updated'),
            new OA\Response(
                response: 400,
                description: 'Malformed request body (INVALID_REQUEST_BODY), or a negative restock quantity (INVALID_RESTOCK_QUANTITY)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class)),
            ),
            new OA\Response(
                response: 404,
                description: 'Unrecognized SKU, or a product not present in this machine\'s catalog (PRODUCT_NOT_FOUND)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class)),
            ),
        ],
    )]
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
    #[OA\Patch(
        summary: 'Update a product\'s price',
        description: 'Replaces the product\'s current price. Zero is a valid price (a free item); negative values are rejected.',
        parameters: [
            new OA\Parameter(
                name: 'sku',
                in: 'path',
                required: true,
                description: 'Product identifier',
                schema: new OA\Schema(type: 'string', enum: [ProductSku::WATER, ProductSku::JUICE, ProductSku::SODA]),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UpdateProductPriceRequest::class)),
        ),
        responses: [
            new OA\Response(response: 204, description: 'Price updated'),
            new OA\Response(
                response: 400,
                description: 'Malformed request body (INVALID_REQUEST_BODY), or a negative price (INVALID_PRODUCT_PRICE)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class)),
            ),
            new OA\Response(
                response: 404,
                description: 'Unrecognized SKU, or a product not present in this machine\'s catalog (PRODUCT_NOT_FOUND)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class)),
            ),
        ],
    )]
    public function updatePrice(string $sku, Request $request, UpdateProductPriceCommandHandler $handler): JsonResponse
    {
        $productSku = ProductSku::tryFrom(strtoupper($sku))
            ?? throw ProductNotFoundException::forUnknownSku($sku);

        /** @var UpdateProductPriceRequest $body */
        $body = $this->serializer->deserialize($request->getContent(), UpdateProductPriceRequest::class, 'json');

        if ($body->price < 0) {
            throw InvalidProductPriceException::forNegativePrice($body->price);
        }

        $handler(new UpdateProductPriceCommand(
            self::MACHINE_ID,
            $productSku,
            Money::fromEuros($body->price),
        ));

        return new JsonResponse(null, 204);
    }

    #[Route('/change', methods: ['POST'])]
    #[OA\Post(
        summary: 'Replace the machine\'s change inventory',
        description: 'Overwrites the full change inventory with the given per-denomination counts. Denominations omitted from the payload are reset to zero.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: SetChangeInventoryRequest::class)),
        ),
        responses: [
            new OA\Response(response: 204, description: 'Change inventory replaced'),
            new OA\Response(
                response: 400,
                description: 'Malformed request body (INVALID_REQUEST_BODY), or a negative coin count (INVALID_CHANGE_QUANTITY)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class)),
            ),
        ],
    )]
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
    #[OA\Get(
        summary: 'List past sales transactions',
        description: 'Returns a paginated, optionally product-filtered history of completed sales.',
        parameters: [
            new OA\Parameter(
                name: 'product',
                in: 'query',
                required: false,
                description: 'Filter by product SKU',
                schema: new OA\Schema(type: 'string', enum: [ProductSku::WATER, ProductSku::JUICE, ProductSku::SODA]),
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number (1-based)',
                schema: new OA\Schema(type: 'integer', default: 1, example: 1),
            ),
            new OA\Parameter(
                name: 'perPage',
                in: 'query',
                required: false,
                description: 'Items per page',
                schema: new OA\Schema(type: 'integer', default: 20, example: 20),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated transaction history',
                content: new OA\JsonContent(ref: new Model(type: TransactionHistoryResponse::class)),
            ),
            new OA\Response(
                response: 400,
                description: 'The "product" query parameter is not a recognized SKU (INVALID_PRODUCT_FILTER)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class)),
            ),
        ],
    )]
    public function transactions(Request $request, GetTransactionHistoryQueryHandler $handler): JsonResponse
    {
        $productParam = $request->query->get('product');

        $product = null;
        if ($productParam !== null) {
            $product = ProductSku::tryFrom(strtoupper($productParam))
                ?? throw InvalidProductFilterException::forUnknownSku($productParam);
        }

        // Date-range filtering (?from=&to=) is part of GetTransactionHistoryQuery
        // and already supported by the repository/query layer, but parsing
        // those query params into DateTimeImmutable is intentionally left out
        // here to keep the initial scope focused. Wiring them up is a small,
        // isolated change confined to this method.
        $result = $handler(new GetTransactionHistoryQuery(
            from: null,
            to: null,
            product: $product,
            page: (int) $request->query->get('page', '1'),
            perPage: (int) $request->query->get('perPage', '20'),
        ));

        return $this->json(TransactionHistoryResponse::fromResult($result));
    }

    private function json(mixed $data): JsonResponse
    {
        return new JsonResponse($this->serializer->serialize($data, 'json'), json: true);
    }
}
