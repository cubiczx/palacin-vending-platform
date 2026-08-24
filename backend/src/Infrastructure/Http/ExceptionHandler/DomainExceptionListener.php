<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\ExceptionHandler;

use App\Domain\Exception\DomainException;
use App\Domain\Exception\ExactChangeUnavailableException;
use App\Domain\Exception\InsufficientFundsException;
use App\Domain\Exception\InvalidCoinException;
use App\Domain\Exception\OutOfStockException;
use App\Domain\Exception\ProductNotFoundException;
use App\Infrastructure\Http\Dto\ErrorResponse;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class DomainExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof DomainException) {
            return;
        }

        $status = match (true) {
            $exception instanceof ProductNotFoundException => 404,
            $exception instanceof OutOfStockException => 409,
            $exception instanceof InsufficientFundsException => 402,
            $exception instanceof ExactChangeUnavailableException => 409,
            $exception instanceof InvalidCoinException => 400,
            default => 400,
        };

        $errorCode = match (true) {
            $exception instanceof ProductNotFoundException => 'PRODUCT_NOT_FOUND',
            $exception instanceof OutOfStockException => 'OUT_OF_STOCK',
            $exception instanceof InsufficientFundsException => 'INSUFFICIENT_FUNDS',
            $exception instanceof ExactChangeUnavailableException => 'EXACT_CHANGE_UNAVAILABLE',
            $exception instanceof InvalidCoinException => 'INVALID_COIN',
            default => 'DOMAIN_ERROR',
        };

        $event->setResponse(new JsonResponse(
            new ErrorResponse(error: $errorCode, message: $exception->getMessage()),
            $status,
        ));
    }
}
