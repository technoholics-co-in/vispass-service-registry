<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Technoholics\Exception\ForbiddenException;

/**
 * Optional mTLS gate for service mesh / reverse-proxy terminated client certificates.
 *
 * When MTLS_REQUIRED=true, requests must include proof of verified client cert via
 * X-Forwarded-Client-Cert-Verify: SUCCESS or SSL_CLIENT_VERIFY: SUCCESS.
 */
final class MtlsGateMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isRequired()) {
            return $handler->handle($request);
        }

        if ($this->isClientVerified($request)) {
            return $handler->handle($request);
        }

        throw new ForbiddenException(
            message: 'mTLS client certificate verification required',
            businessCode: 'MTLS_REQUIRED'
        );
    }

    private function isRequired(): bool
    {
        $value = strtolower((string) ($_ENV['MTLS_REQUIRED'] ?? 'false'));

        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    private function isClientVerified(ServerRequestInterface $request): bool
    {
        $headers = [
            $request->getHeaderLine('X-Forwarded-Client-Cert-Verify'),
            $request->getHeaderLine('X-SSL-Client-Verify'),
            $request->getServerParams()['SSL_CLIENT_VERIFY'] ?? '',
        ];

        foreach ($headers as $header) {
            if (strtoupper(trim((string) $header)) === 'SUCCESS') {
                return true;
            }
        }

        return false;
    }
}
