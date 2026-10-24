<?php

namespace Omnidrive\Azure\Tests;

use AzureOss\Storage\Blob\BlobServiceClient;
use Omnidrive\Azure\AzureDriveFactory;
use Omnidrive\Drive;
use Omnidrive\Testing\DriveContractTests;
use PHPUnit\Framework\TestCase;

/**
 * The contract against a real Blob service: the harness's Azurite
 * (docker compose run --rm omnidrive test), or the account
 * OMNIDRIVE_AZURE_CONNECTION_STRING names. Skipped when none is named. The
 * container (OMNIDRIVE_AZURE_CONTAINER, omnidrive) is made when it is not there.
 */
final class AzureLiveTest extends TestCase
{
    use DriveContractTests;

    protected function keepsVisibility(): bool
    {
        return false;
    }

    protected function drive(): Drive
    {
        $connection = getenv('OMNIDRIVE_AZURE_CONNECTION_STRING');
        if (false === $connection || '' === $connection) {
            self::markTestSkipped('No Blob service named (OMNIDRIVE_AZURE_CONNECTION_STRING).');
        }
        $container = getenv('OMNIDRIVE_AZURE_CONTAINER') ?: 'omnidrive';
        BlobServiceClient::fromConnectionString($connection)->getContainerClient($container)->createIfNotExists();

        return (new AzureDriveFactory())->create(['connection_string' => $connection, 'container' => $container, 'prefix' => 'live'], 'azure');
    }

    /**
     * Blob Storage's namespace is flat: a "directory" is the prefix of the blobs under it, so
     * createDirectory() makes nothing and a directory exists while it holds a file (an account with
     * a hierarchical namespace - Data Lake Gen2 - has real ones: not checked here). The contract's
     * test, as Azure has it.
     */
    public function testDirectoriesAreMadeAndDeleted(): void
    {
        $drive = $this->drive();
        $dir = 'omnidrive-dirs-'.bin2hex(random_bytes(3));

        $drive->createDirectory("$dir/made/inside");
        self::assertFalse($drive->directoryExists("$dir/made/inside"), 'an empty directory is nothing on a flat namespace');

        $drive->write("$dir/made/inside/file.txt", 'x');
        self::assertTrue($drive->directoryExists("$dir/made/inside"));
        self::assertFalse($drive->exists("$dir/made/inside"), 'a directory is not a file');
        $drive->deleteDirectory("$dir/made");
        self::assertFalse($drive->exists("$dir/made/inside/file.txt"));
        self::assertFalse($drive->directoryExists("$dir/made"));
    }

    public function testASignedAddressServesTheFileUntilItExpires(): void
    {
        $drive = $this->drive();
        $drive->write('signed/hello.txt', 'signed');

        try {
            self::assertSame('signed', @file_get_contents($drive->temporaryUrl('signed/hello.txt', new \DateTimeImmutable('+1 minute'))));
            $unsigned = explode('?', $drive->temporaryUrl('signed/hello.txt', new \DateTimeImmutable('+1 minute')), 2)[0];
            self::assertFalse(@file_get_contents($unsigned), 'the container is private: the blob\'s plain address serves nothing');
        } finally {
            $drive->deleteDirectory('signed');
        }
    }
}
