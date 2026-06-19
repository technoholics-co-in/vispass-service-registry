<?php

declare(strict_types=1);

use DI\Container;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Setup;
use Psr\SimpleCache\CacheInterface;
use Slim\Factory\AppFactory;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Technoholics\Logger\FileLogger;
use Technoholics\Logger\FileLoggerConfigProvider;
use Technoholics\Psr15Middleware\Exception\ExceptionLoggingMiddleware;
use Technoholics\Psr15Middleware\Exception\ExceptionToResponseMiddleware;
use Technoholics\Psr15Middleware\Http\Context\HeaderExtractorMiddleware;
use Technoholics\Psr15Middleware\Http\SanitizeMiddleware;
use Technoholics\ServiceRegistry\Auth\Controllers\JwksController;
use Technoholics\ServiceRegistry\Auth\Controllers\ServiceTokenController;
use Technoholics\ServiceRegistry\Auth\Repositories\AuditLogRepository;
use Technoholics\ServiceRegistry\Auth\Repositories\SigningKeyRepository;
use Technoholics\ServiceRegistry\Auth\Security\ServiceJwtIssuer;
use Technoholics\ServiceRegistry\Auth\Security\SigningKeyService;
use Technoholics\ServiceRegistry\Auth\Services\AuditLogService;
use Technoholics\ServiceRegistry\Auth\Services\ServiceTokenService;
use Technoholics\ServiceRegistry\Credential\Repositories\ServiceCredentialRepository;
use Technoholics\ServiceRegistry\Middleware\MtlsGateMiddleware;
use Technoholics\ServiceRegistry\Health\Controllers\HealthController;
use Technoholics\ServiceRegistry\Health\Services\HealthCheckService;
use Technoholics\ServiceRegistry\Scope\Controllers\ServiceScopeController;
use Technoholics\ServiceRegistry\Scope\Repositories\ServiceScopeRepository;
use Technoholics\ServiceRegistry\Scope\Services\ServiceScopeService;
use Technoholics\ServiceRegistry\Service\Controllers\RegisteredServiceController;
use Technoholics\ServiceRegistry\Service\Repositories\RegisteredServiceRepository;
use Technoholics\ServiceRegistry\Service\Services\RegisteredServiceService;
use Technoholics\ServiceRegistry\Shared\Cache\RedisSimpleCache;
use Technoholics\ServiceRegistry\TrustRule\Controllers\ServiceTrustRuleController;
use Technoholics\ServiceRegistry\TrustRule\Repositories\ServiceTrustRuleRepository;
use Technoholics\ServiceRegistry\TrustRule\Services\ServiceTrustRuleService;

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    (new Dotenv())->bootEnv(__DIR__ . '/../.env');
}

$settings = require __DIR__ . '/../config/settings.php';

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

$container->set('settings', static fn (): array => require __DIR__ . '/../config/settings.php');

$container->set(EntityManager::class, static function () use ($settings): EntityManager {
    $config = Setup::createAttributeMetadataConfiguration(
        $settings['settings']['doctrine']['meta']['entity_path'],
        $settings['settings']['doctrine']['meta']['auto_generate_proxies'],
        $settings['settings']['doctrine']['meta']['proxy_dir'],
        $settings['settings']['doctrine']['meta']['cache'] ?? null
    );

    return EntityManager::create($settings['settings']['doctrine']['connection'], $config);
});

$container->set(EntityManagerInterface::class, static fn ($c): EntityManagerInterface => $c->get(EntityManager::class));

$container->set(\Predis\Client::class, static function (): \Predis\Client {
    $parameters = [
        'host' => $_ENV['REDIS_HOST'] ?? 'redis',
        'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
        'database' => (int) ($_ENV['REDIS_DATABASE'] ?? 0),
    ];
    if (!empty($_ENV['REDIS_PASSWORD'])) {
        $parameters['password'] = $_ENV['REDIS_PASSWORD'];
    }

    return new \Predis\Client($parameters);
});

$container->set(CacheInterface::class, static fn ($c): CacheInterface => new RedisSimpleCache(
    $c->get(\Predis\Client::class)
));

$container->set(FileLogger::class, static function ($c): FileLogger {
    $settings = $c->get('settings')['settings'];
    $loggerConfig = FileLoggerConfigProvider::get($settings['logger']);

    return new FileLogger(
        $settings['logger']['service_name'],
        $loggerConfig
    );
});

$container->set(ValidatorInterface::class, static fn (): ValidatorInterface => Validation::createValidatorBuilder()
    ->enableAttributeMapping()
    ->getValidator());

$container->set(HeaderExtractorMiddleware::class, static fn (): HeaderExtractorMiddleware => new HeaderExtractorMiddleware());

$container->set(RegisteredServiceRepository::class, static function ($c): RegisteredServiceRepository {
    $em = $c->get(EntityManager::class);

    return $em->getRepository(
        \Technoholics\ServiceRegistry\Service\Entities\RegisteredService::class
    );
});

$container->set(RegisteredServiceService::class, static function ($c): RegisteredServiceService {
    $settings = $c->get('settings')['settings'];

    return new RegisteredServiceService(
        $c->get(EntityManagerInterface::class),
        $c->get(RegisteredServiceRepository::class),
        $c->get(CacheInterface::class),
        $c->get(FileLogger::class),
        $c->get(AuditLogService::class),
        $settings['cache']['prefix'] ?? 'service-registry:',
        (int) ($settings['cache']['ttl'] ?? 300)
    );
});

$container->set(RegisteredServiceController::class, static fn ($c): RegisteredServiceController => new RegisteredServiceController(
    $c->get(RegisteredServiceService::class),
    $c->get(ValidatorInterface::class),
    $c->get(FileLogger::class)
));

$container->set(ServiceScopeRepository::class, static function ($c): ServiceScopeRepository {
    $em = $c->get(EntityManager::class);

    return $em->getRepository(\Technoholics\ServiceRegistry\Scope\Entities\ServiceScope::class);
});

$container->set(ServiceScopeService::class, static function ($c): ServiceScopeService {
    $settings = $c->get('settings')['settings'];

    return new ServiceScopeService(
        $c->get(EntityManagerInterface::class),
        $c->get(RegisteredServiceRepository::class),
        $c->get(ServiceScopeRepository::class),
        $c->get(CacheInterface::class),
        $c->get(FileLogger::class),
        $c->get(AuditLogService::class),
        $settings['cache']['prefix'] ?? 'service-registry:',
        (int) ($settings['cache']['ttl'] ?? 300)
    );
});

$container->set(ServiceScopeController::class, static fn ($c): ServiceScopeController => new ServiceScopeController(
    $c->get(ServiceScopeService::class),
    $c->get(ValidatorInterface::class),
    $c->get(FileLogger::class)
));

$container->set(ServiceTrustRuleRepository::class, static function ($c): ServiceTrustRuleRepository {
    $em = $c->get(EntityManager::class);

    return $em->getRepository(\Technoholics\ServiceRegistry\TrustRule\Entities\ServiceTrustRule::class);
});

$container->set(ServiceTrustRuleService::class, static function ($c): ServiceTrustRuleService {
    $settings = $c->get('settings')['settings'];

    return new ServiceTrustRuleService(
        $c->get(EntityManagerInterface::class),
        $c->get(RegisteredServiceRepository::class),
        $c->get(ServiceTrustRuleRepository::class),
        $c->get(CacheInterface::class),
        $c->get(FileLogger::class),
        $c->get(AuditLogService::class),
        $settings['cache']['prefix'] ?? 'service-registry:',
        (int) ($settings['cache']['ttl'] ?? 300)
    );
});

$container->set(ServiceTrustRuleController::class, static fn ($c): ServiceTrustRuleController => new ServiceTrustRuleController(
    $c->get(ServiceTrustRuleService::class),
    $c->get(ValidatorInterface::class),
    $c->get(FileLogger::class)
));

$container->set(HealthCheckService::class, static fn ($c): HealthCheckService => new HealthCheckService(
    $c->get(EntityManagerInterface::class),
    new \Technoholics\ServiceRegistry\Shared\Redis\RedisPingProbe($c->get(\Predis\Client::class))
));

$container->set(HealthController::class, static fn ($c): HealthController => new HealthController(
    $c->get(HealthCheckService::class),
    $c->get(FileLogger::class)
));

$container->set(AuditLogRepository::class, static function ($c): AuditLogRepository {
    $em = $c->get(EntityManager::class);

    return $em->getRepository(\Technoholics\ServiceRegistry\Auth\Entities\AuditLog::class);
});

$container->set(SigningKeyRepository::class, static function ($c): SigningKeyRepository {
    $em = $c->get(EntityManager::class);

    return $em->getRepository(\Technoholics\ServiceRegistry\Auth\Entities\SigningKey::class);
});

$container->set(ServiceCredentialRepository::class, static function ($c): ServiceCredentialRepository {
    $em = $c->get(EntityManager::class);

    return $em->getRepository(\Technoholics\ServiceRegistry\Credential\Entities\ServiceCredential::class);
});

$container->set(AuditLogService::class, static fn ($c): AuditLogService => new AuditLogService(
    $c->get(AuditLogRepository::class)
));

$container->set(SigningKeyService::class, static fn ($c): SigningKeyService => new SigningKeyService(
    $c->get(SigningKeyRepository::class),
    $c->get(AuditLogService::class)
));

$container->set(ServiceJwtIssuer::class, static fn ($c): ServiceJwtIssuer => new ServiceJwtIssuer(
    $c->get(SigningKeyService::class)
));

$container->set(ServiceTokenService::class, static fn ($c): ServiceTokenService => new ServiceTokenService(
    $c->get(RegisteredServiceRepository::class),
    $c->get(ServiceCredentialRepository::class),
    $c->get(ServiceScopeRepository::class),
    $c->get(ServiceTrustRuleRepository::class),
    $c->get(ServiceJwtIssuer::class),
    $c->get(AuditLogService::class)
));

$container->set(ServiceTokenController::class, static fn ($c): ServiceTokenController => new ServiceTokenController(
    $c->get(ServiceTokenService::class),
    $c->get(ValidatorInterface::class),
    $c->get(FileLogger::class)
));

$container->set(JwksController::class, static fn ($c): JwksController => new JwksController(
    $c->get(SigningKeyService::class),
    $c->get(FileLogger::class)
));

$app->addRoutingMiddleware();
$app->add(HeaderExtractorMiddleware::class);
$app->add(new MtlsGateMiddleware());
$app->addBodyParsingMiddleware();
$app->add(new SanitizeMiddleware());
$app->add(new ExceptionLoggingMiddleware($container->get(FileLogger::class)));
$app->add(new ExceptionToResponseMiddleware(
    $app->getResponseFactory(),
    $settings['settings']['displayErrorDetails']
));

(require __DIR__ . '/../routes/health.php')($app);
(require __DIR__ . '/../routes/services.php')($app);
(require __DIR__ . '/../routes/scopes.php')($app);
(require __DIR__ . '/../routes/trust_rules.php')($app);
(require __DIR__ . '/../routes/auth.php')($app);
(require __DIR__ . '/../routes/jwks.php')($app);

$app->run();
