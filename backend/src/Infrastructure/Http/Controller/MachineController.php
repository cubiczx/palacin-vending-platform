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
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Model\Coin;
use App\Domain\Model\ProductSku;
use App\Infrastructure\Http\Dto\CoinsResponse;
use App\Infrastructure\Http\Dto\ErrorResponse;
use App\Infrastructure\Http\Dto\InsertCoinRequest;
use App\Infrastructure\Http\Dto\MachineStateResponse;
use App\Infrastructure\Http\Dto\VendingResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/machine')]
#[OA\Tag(name: 'Machine')]
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
    #[OA\Get(
        summary: 'It obtains the current state of the machine',
        description: 'Returns the product catalog (with stock availability) and the currently entered amount.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Machine status',
                content: new OA\JsonContent(ref: new Model(type: MachineStateResponse::class)),
            ),
        ],
    )]
    public function state(GetMachineStateQueryHandler $handler): JsonResponse
    {
        $view = $handler(new GetMachineStateQuery(self::MACHINE_ID));

        return $this->json(MachineStateResponse::fromView($view));
    }

    #[Route('/coins', methods: ['POST'])]
    #[OA\Post(
        summary: 'Insert a coin into the machine',
        description: 'Only the denominations 5, 10, 25 and 100 cents are accepted.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: InsertCoinRequest::class)),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Accepted currency; total amount entered after the transaction',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'insertedAmount', type: 'number', format: 'float', example: 0.25)],
                ),
            ),
            new OA\Response(
                response: 400,
                description: 'Malformed request body (code INVALID_REQUEST_BODY), or invalid currency denomination (code INVALID_COIN)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class)),
            ),
        ],
    )]
    public function insertCoin(Request $request, InsertCoinCommandHandler $handler): JsonResponse
    {
        /** @var InsertCoinRequest $body */
        $body = $this->serializer->deserialize($request->getContent(), InsertCoinRequest::class, 'json');
        $coin = Coin::fromCentsOrFail($body->cents);

        $insertedAmount = $handler(new InsertCoinCommand(self::MACHINE_ID, $coin));

        return $this->json(['insertedAmount' => $insertedAmount->toEuros()]);
    }

    #[Route('/select/{sku}', methods: ['POST'])]
    #[OA\Post(
        summary: 'Select a product to buy it',
        description: 'Deduct the price from the entered amount, deliver the product, and return the change if applicable.',
        parameters: [
            new OA\Parameter(
                name: 'sku',
                in: 'path',
                required: true,
                description: 'Product identifier',
                schema: new OA\Schema(type: 'string', enum: [ProductSku::WATER, ProductSku::JUICE, ProductSku::SODA]),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product delivered, with the exchange returned (if applicable)',
                content: new OA\JsonContent(ref: new Model(type: VendingResponse::class)),
            ),
            new OA\Response(
                response: 400,
                description: 'Unclassified domain error (error code DOMAIN_ERROR)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class)),
            ),
            new OA\Response(
                response: 402,
                description: 'Insufficient funds (error code INSUFFICIENT_FUNDS)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class)),
            ),
            new OA\Response(
                response: 404,
                description: 'SKU not recognized, or product not available in this machine\'s catalog (error code PRODUCT_NOT_FOUND)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class)),
            ),
            new OA\Response(
                response: 409,
                description: 'Product out of stock, or the machine cannot return exact change (error codes OUT_OF_STOCK / EXACT_CHANGE_UNAVAILABLE)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class)),
            ),
        ],
    )]
    public function selectProduct(string $sku, SelectProductCommandHandler $handler): JsonResponse
    {
        $productSku = ProductSku::tryFrom(strtoupper($sku))
            ?? throw ProductNotFoundException::forUnknownSku($sku);

        $result = $handler(new SelectProductCommand(self::MACHINE_ID, $productSku));

        return $this->json(VendingResponse::fromResult($result));
    }

    #[Route('/return', methods: ['POST'])]
    #[OA\Post(
        summary: 'Returns all inserted coins',
        description: 'Cancel the ongoing operation and return the change of coins for the amount inserted so far.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Breakdown of returned coins',
                content: new OA\JsonContent(ref: new Model(type: CoinsResponse::class)),
            ),
        ],
    )]
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
