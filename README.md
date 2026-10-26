# omnidrive/azure

A container of Azure Blob Storage as an [omnidrive](https://github.com/glitchr-omnisense/omnidrive)
drive, through azure-oss/storage-blob-flysystem - the adapter The PHP League names in place of its
own league/flysystem-azure-blob-storage, abandoned with Microsoft's retired PHP SDK.

```sh
composer require omnisense/omnidrive omnidrive/azure
```

```php
use Omnidrive\Azure\AzureDriveFactory;
use Omnidrive\Registry;

$drives = new Registry([new AzureDriveFactory()], [
    'uploads' => ['factory' => 'azure', 'options' => ['container' => 'uploads', 'connection_string' => getenv('AZURE_STORAGE_CONNECTION_STRING'), 'prefix' => 'site']],
]);
```

| Option | Default | |
|---|---|---|
| `container` | required | |
| `connection_string` | | the portal's: a shared key or a SAS |
| `account_name`, `account_key` | | instead of a connection string |
| `endpoint` | `https://<account>.blob.core.windows.net` | with an account name: another cloud, Azurite |
| `prefix` | | every path under it |
| `public_container` | false | the container's blobs are readable anonymously: a file's own address is its public one |
| `public_url` | | a CDN in front of it |
| `visibility`, `directory_visibility`, `retain_visibility` | | as every drive |

`capabilities()`: not local; public addresses only with `public_container` or `public_url` - the
adapter's own, for a private container, is a SAS valid a thousand years, which this drive does not
hand out as "public"; temporary addresses: a read-only SAS signed with the account key. Blob Storage
keeps no visibility per file (reading or changing one is a `DriveException`), and its namespace is
flat: `createDirectory()` makes nothing, a directory exists while it holds a blob. A stream written
may be closed by the client once sent. Nothing is sent until the first operation.

## Verified, and not

- Verified (2026-10-24, omnidrive's harness, PHP 8.4): the contract's tests against Azurite
  (Microsoft's emulator, its development account) - its directories as a flat namespace has them;
  a SAS address signed and fetched, the blob's plain address refused; in plain PHP (`bare --live`).
  Offline: the SAS's address, permission (`r`) and resource (`b`), the public address of a public
  container and behind a CDN, no public address otherwise.
- Not verified: Azure Blob Storage itself (Azurite only); an account with a hierarchical namespace
  (Data Lake Gen2); a connection string with a SAS; Entra ID credentials (not mapped).

License: MIT. Unofficial; not affiliated with Microsoft, Azure-OSS or The PHP League.
