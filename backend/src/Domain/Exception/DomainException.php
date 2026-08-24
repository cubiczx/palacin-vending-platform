<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Marker base class for all business-rule violations. The HTTP layer
 * catches this type to translate domain failures into well-formed error
 * responses, without the domain layer knowing anything about HTTP.
 */
abstract class DomainException extends \RuntimeException
{
}
