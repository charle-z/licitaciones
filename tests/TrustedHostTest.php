<?php

declare(strict_types=1);

use App\Http\HttpException;
use App\Http\TrustedHost;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TrustedHostTest extends TestCase
{
    #[DataProvider('acceptedAuthorities')]
    public function testAcceptsLocalAuthorities(string $authority): void
    {
        TrustedHost::validate($authority);
        $this->addToAssertionCount(1);
    }

    public static function acceptedAuthorities(): iterable
    {
        yield ['localhost:8091'];
        yield ['LOCALHOST:8091'];
        yield ['127.0.0.1:8091'];
        yield ['127.0.0.1:8080'];
    }

    #[DataProvider('rejectedAuthorities')]
    public function testRejectsUntrustedAuthorities(mixed $authority): void
    {
        try {
            TrustedHost::validate($authority);
            self::fail('Se aceptó un host que no estaba permitido.');
        } catch (HttpException $error) {
            self::assertSame(400, $error->status);
            self::assertSame('El host solicitado no está permitido.', $error->getMessage());
        }
    }

    public static function rejectedAuthorities(): iterable
    {
        foreach ([null, [], 8091, '', 'rebind.invalid:8091', 'localhost.evil:8091', 'evil-localhost:8091', 'localhost@evil:8091', 'http://localhost:8091', 'localhost:8091/path', 'localhost:8091?x', 'localhost:8091#x', 'localhost:8091\\x', 'localhost%3a8091', 'localhost:8091,127.0.0.1:8091', "localhost:8091\r\n", "localhost:8091\0", ' localhost:8091', 'localhost:8091 ', 'localhost.:8091', 'localhost', 'localhost:80', 'localhost:443', 'localhost:', 'localhost:0', 'localhost:65536', 'localhost:08091', 'localhost:+8091', 'localhost:-1', 'localhost:abc', '127.1:8091', '2130706433:8091', '0x7f000001:8091', '[::1]:8091', '::1', str_repeat('a', 260)] as $authority) {
            yield [$authority];
        }
    }

    public function testAcceptsOnlyExplicitlyConfiguredAuthorities(): void
    {
        TrustedHost::validate('APP.EXAMPLE:8443', ' app.example:8443, 127.0.0.1:8080 ');
        TrustedHost::validate('app.example', 'app.example');
        $this->expectException(HttpException::class);
        TrustedHost::validate('localhost:8091', 'app.example:8443,127.0.0.1:8080');
    }

    #[DataProvider('invalidConfigurations')]
    public function testRejectsInvalidConfiguration(string $configuration): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('La configuración de hosts permitidos no es válida.');
        TrustedHost::validate('localhost:8091', $configuration);
    }

    public static function invalidConfigurations(): iterable
    {
        foreach (['', ' ', ',', 'localhost:8091,', '*.example:8091', 'https://app.example', 'app.example.:443', 'app.example:0', 'app.example:65536', 'app.example:0443', 'app.example/path', 'app.example user', '[::1]:8080', str_repeat('a', 64) . '.example', str_repeat('a.', 127) . 'a'] as $configuration) {
            yield [$configuration];
        }
    }
}
