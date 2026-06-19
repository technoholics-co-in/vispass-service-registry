<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Shared\Validation;

use Technoholics\ServiceRegistry\Scope\Exceptions\InvalidScopeException;

/**
 * Validates explicit service scope strings (no wildcards).
 */
final class ScopeFormatValidator
{
    private const SCOPE_PATTERN = '/^[a-z][a-z0-9]*(\.[a-z][a-z0-9_-]*)+$/';

    public static function isValid(string $scope): bool
    {
        if (str_contains($scope, '*')) {
            return false;
        }

        return (bool) preg_match(self::SCOPE_PATTERN, $scope);
    }

    public static function assertValid(string $scope): void
    {
        if (!self::isValid($scope)) {
            throw new InvalidScopeException($scope);
        }
    }
}
