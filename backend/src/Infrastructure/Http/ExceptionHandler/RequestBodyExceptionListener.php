<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\ExceptionHandler;

use App\Infrastructure\Http\Dto\ErrorResponse;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class RequestBodyExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof SerializerExceptionInterface) {
            return;
        }

        $event->setResponse(new JsonResponse(
            new ErrorResponse(error: 'INVALID_REQUEST_BODY', message: 'The request body is malformed or contains invalid types.'),
            400,
        ));
    }
}
