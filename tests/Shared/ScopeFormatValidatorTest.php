<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Tests\Shared;

use PHPUnit\Framework\TestCase;
use Technoholics\ServiceRegistry\Scope\Exceptions\InvalidScopeException;
use Technoholics\ServiceRegistry\Shared\Validation\ScopeFormatValidator;

final class ScopeFormatValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testAcceptsExplicitScopes(): void
    {
        $this->assertTrue(ScopeFormatValidator::isValid('storage.upload'));
        $this->assertTrue(ScopeFormatValidator::isValid('client.read'));
    }

    public function testRejectsWildcards(): void
    {
        $this->assertFalse(ScopeFormatValidator::isValid('storage.*'));
        $this->assertFalse(ScopeFormatValidator::isValid('client.*'));
    }

    public function testAssertValidThrowsForInvalidScope(): void
    {
        $this->expectException(InvalidScopeException::class);
        ScopeFormatValidator::assertValid('storage.*');
    }
}
