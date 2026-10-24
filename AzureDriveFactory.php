<?php

namespace Omnidrive\Azure;

use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\BlobFlysystem\AzureBlobStorageAdapter;
use Omnidrive\Config;
use Omnidrive\Drive;
use Omnidrive\Exception\InvalidConfigException;
use Omnidrive\Flysystem\FlysystemDriveFactory;

/**
 * A container of Azure Blob Storage, through azure-oss/storage-blob-flysystem
 * (the adapter The PHP League names in place of its own
 * league/flysystem-azure-blob-storage, abandoned with Microsoft's retired SDK):
 *
 *   (new AzureDriveFactory())->create(['container' => 'uploads', 'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=...;AccountKey=...;EndpointSuffix=core.windows.net']);
 *   (new AzureDriveFactory())->create(['container' => 'uploads', 'account_name' => 'example', 'account_key' => '...']);
 *
 * Options: container (required); connection_string (the portal's, a shared
 * key or a SAS) or account_name and account_key (with endpoint, the Blob
 * service's address, for another cloud or Azurite - else
 * https://<account>.blob.core.windows.net); prefix (every path under it);
 * public_container (the container's blobs are readable anonymously: a file's
 * own address is its public one); public_url (a CDN in front of it).
 *
 * A public address is given only with public_container or public_url: the
 * adapter's own, for a private container, is a SAS valid a thousand years,
 * which this drive does not hand out as "public". temporaryUrl() signs a
 * read-only SAS with the account key. Blob Storage keeps no visibility per
 * file: reading or changing one is a DriveException. Nothing is sent until the
 * first operation.
 */
final class AzureDriveFactory extends FlysystemDriveFactory
{
    protected function populate(Config $c): void
    {
        $c->defaults([
            'omnidrive.factory_name' => 'azure',
            'omnidrive.factory_title' => 'A container of Azure Blob Storage',
            'omnidrive.required_options' => ['container'],
            'prefix' => '',
            'public_container' => false,
        ]);
    }

    protected function build(Config $c, string $name): Drive
    {
        $publicContainer = $c->bool('public_container');
        $adapter = new AzureBlobStorageAdapter(
            BlobServiceClient::fromConnectionString(self::connectionString($c))->getContainerClient((string) $c->string('container')),
            trim((string) $c->string('prefix'), '/'),
            null,
            AzureBlobStorageAdapter::ON_VISIBILITY_THROW_ERROR,
            $publicContainer,
        );

        return $this->drive($name, $adapter, $c, null, $publicContainer);
    }

    private static function connectionString(Config $c): string
    {
        if (null !== $given = $c->string('connection_string')) {
            return $given;
        }
        $account = $c->string('account_name');
        $key = $c->string('account_key');
        if (null === $account || null === $key) {
            throw new InvalidConfigException('The "azure" drive needs connection_string, or account_name and account_key.');
        }
        $endpoint = $c->string('endpoint') ?? "https://$account.blob.core.windows.net";

        return \sprintf('DefaultEndpointsProtocol=%s;AccountName=%s;AccountKey=%s;BlobEndpoint=%s', parse_url($endpoint, \PHP_URL_SCHEME) ?: 'https', $account, $key, $endpoint);
    }
}
