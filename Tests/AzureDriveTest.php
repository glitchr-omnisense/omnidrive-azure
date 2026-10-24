<?php

namespace Omnidrive\Azure\Tests;

use Omnidrive\Azure\AzureDriveFactory;
use Omnidrive\Exception\InvalidConfigException;
use Omnidrive\Exception\NotSupportedException;
use PHPUnit\Framework\TestCase;

final class AzureDriveTest extends TestCase
{
    private const KEY = 'a2V5LW9mLW5vLWFjY291bnQtYXQtYWxsLWp1c3QtZm9yLXRoZS10ZXN0cw==';

    public function testAPrivateContainerGivesSignedAddressesAndNoPublicOne(): void
    {
        $drive = (new AzureDriveFactory())->create(['container' => 'uploads', 'account_name' => 'example', 'account_key' => self::KEY, 'prefix' => 'site'], 'blobs');

        self::assertSame(['local' => false, 'publicUrls' => false, 'temporaryUrls' => true], $drive->capabilities()->toArray());
        $signed = $drive->temporaryUrl('a/b.pdf', new \DateTimeImmutable('+10 minutes'));
        self::assertStringStartsWith('https://example.blob.core.windows.net/uploads/site/a/b.pdf?', $signed);
        parse_str((string) parse_url($signed, \PHP_URL_QUERY), $sas);
        self::assertSame(['r', 'b'], [$sas['sp'] ?? null, $sas['sr'] ?? null], 'read-only, this blob');
        self::assertArrayHasKey('sig', $sas);

        $this->expectException(NotSupportedException::class);
        $drive->publicUrl('a/b.pdf'); // the adapter's would be a SAS of a thousand years
    }

    public function testAPublicContainerGivesTheBlobsOwnAddress(): void
    {
        $drive = (new AzureDriveFactory())->create(['container' => 'media', 'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=example;AccountKey='.self::KEY.';EndpointSuffix=core.windows.net', 'public_container' => true]);

        self::assertSame('https://example.blob.core.windows.net/media/a/b.pdf', $drive->publicUrl('a/b.pdf'));
    }

    public function testACdnInFrontIsThePublicAddress(): void
    {
        $drive = (new AzureDriveFactory())->create(['container' => 'media', 'account_name' => 'example', 'account_key' => self::KEY, 'public_url' => 'https://cdn.example.org']);

        self::assertSame('https://cdn.example.org/a/b.pdf', $drive->publicUrl('a/b.pdf'));
    }

    public function testTheContainerAndCredentialsAreRequired(): void
    {
        try {
            (new AzureDriveFactory())->create(['container' => 'media', 'account_name' => 'example']);
            self::fail('no key');
        } catch (InvalidConfigException $e) {
            self::assertStringContainsString('connection_string, or account_name and account_key', $e->getMessage());
        }
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The "azure" drive needs: container.');
        (new AzureDriveFactory())->create(['connection_string' => 'x']);
    }
}
