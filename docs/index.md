# omnidrive/azure

A container of Azure Blob Storage as an
[omniscient/omnidrive](https://github.com/glitchr-studio/omnidrive) drive, through
azure-oss/storage-blob-flysystem (`AzureBlobStorageAdapter` over Azure-OSS's `BlobServiceClient`) -
the adapter The PHP League names in place of its own league/flysystem-azure-blob-storage,
abandoned with Microsoft's retired PHP SDK. The Blob service's address is the connection string's
`BlobEndpoint`, else `https://<account>.blob.core.windows.net`.

## Installation

```sh
composer require omniscient/omnidrive omnidrive/azure
```

Requires PHP 8.2, `omniscient/omnidrive` (1.x) and `azure-oss/storage-blob-flysystem` (^2.2).

## Credentials

The storage account's connection string (the portal's "Access keys"; a shared key, or a SAS), or
its name and key (`account_name`, `account_key`). Temporary addresses are signed with the account
key. Entra ID credentials are not mapped. Azurite, Microsoft's emulator, has a documented
development account (`devstoreaccount1`), which the harness uses.

## Options

`new AzureDriveFactory()`; factory name `azure`.

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `container` | string | - | yes | |
| `connection_string` | string | none | one of the two | the portal's |
| `account_name`, `account_key` | string | none | one of the two | instead of a connection string |
| `endpoint` | string | `https://<account>.blob.core.windows.net` | no | with an account name: another cloud, Azurite |
| `prefix` | string | `''` | no | every path under it |
| `public_container` | bool | `false` | no | the container's blobs readable anonymously: a blob's own address is its public one |
| `public_url` | string or list | none | no | the prefix files are served from, or several (sharded) |
| `visibility` | `public`/`private` | ignored | no | a write's visibility when it does not say |
| `directory_visibility` | `public`/`private` | ignored | no | the same for directories |
| `retain_visibility` | bool | `true` | no | whether a copy keeps its source's visibility |

## Capabilities

| Capability | |
|---|---|
| `local` | no |
| `publicUrls` | with `public_container` or `public_url` only |
| `temporaryUrls` | yes: a read-only SAS (`sp=r`, `sr=b`) signed with the account key |
| visibility per file | no: reading or changing one is a `DriveException` |
| checksum | any algorithm `hash()` knows (the blob downloaded and hashed) |

## Behaviour

The adapter's own public address, for a private container, is a SAS valid a thousand years: this
drive does not hand it out as "public" (the factory passes `$adapterPublicUrls: false`), and gives
a public address only with `public_container` (the blob's plain address) or `public_url`. The
namespace is flat: `createDirectory()` makes nothing, a directory exists while it holds a blob. A
stream written may be closed by the client once sent. The connection string is parsed when the
drive is built; nothing is sent until the first operation.

## Example

```php
<?php

require __DIR__.'/vendor/autoload.php';

use AzureOss\Storage\Blob\BlobServiceClient;
use Omnidrive\Azure\AzureDriveFactory;
use Omnidrive\Registry;

// Azurite's documented development account; on Azure, the portal's connection string.
$azurite = 'DefaultEndpointsProtocol=http;AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;BlobEndpoint=http://azurite:10000/devstoreaccount1';
$drives = new Registry([new AzureDriveFactory()], [
    'private' => ['factory' => 'azure', 'options' => ['container' => 'documents', 'connection_string' => $azurite, 'prefix' => 'site']],
    'behind-cdn' => ['factory' => 'azure', 'options' => ['container' => 'documents', 'connection_string' => $azurite, 'public_url' => 'https://cdn.example.org/documents']],
]);
BlobServiceClient::fromConnectionString($azurite)->getContainerClient('documents')->createIfNotExists();   // Azurite starts empty

$documents = $drives->get('private');
$documents->write('contracts/2026/C-12.pdf', '%PDF-1.4 ...');
echo json_encode($documents->capabilities()->toArray()), ' ', json_encode($drives->get('behind-cdn')->capabilities()->toArray()), "\n";
$url = $documents->temporaryUrl('contracts/2026/C-12.pdf', new DateTimeImmutable('+10 minutes'));
parse_str((string) parse_url($url, PHP_URL_QUERY), $sas);
echo strtok($url, '?'), ' sp=', $sas['sp'], ' sr=', $sas['sr'], "\n";
echo file_get_contents($url), "\n";
$documents->createDirectory('empty');
var_dump($documents->directoryExists('empty'), $documents->directoryExists('contracts'));
$documents->deleteDirectory('contracts');
```

```
{"local":false,"publicUrls":false,"temporaryUrls":true} {"local":false,"publicUrls":true,"temporaryUrls":true}
http://azurite:10000/devstoreaccount1/documents/site/contracts/2026/C-12.pdf sp=r sr=b
%PDF-1.4 ...
bool(false)
bool(true)
```

(run on 2026-10-25 in omnidrive's harness against Azurite: the SAS address served the blob; the
directory made empty does not exist, the one holding a blob does)

## Errors

The service's refusals are `DriveException`s; a missing blob read is a `FileNotFoundException`.
A missing `container`, or neither a connection string nor an account name and key, is an
`InvalidConfigException` (`The "azure" drive needs connection_string, or account_name and
account_key.`).

## Verified, and not

Verified (2026-10-24 and 2026-10-25, omnidrive's harness, PHP 8.4): the contract's tests against
Azurite, its directories as a flat namespace has them; a SAS address signed and fetched, the blob's
plain address refused; in plain PHP (`bare --live`); offline: the SAS's address, permission and
resource, the public address of a public container and behind a CDN, no public address otherwise.
Not verified: Azure Blob Storage itself (Azurite only); an account with a hierarchical namespace
(Data Lake Gen2); a connection string with a SAS; Entra ID credentials.

## Responsibility

An independent, unofficial client, not affiliated with Microsoft, Azure-OSS or The PHP League. Whoever runs it is responsible for the data it stores and the
access it gives (a public or temporary address serves the file to whoever holds it); see the core's
Responsibility section ([omniscient/omnidrive](https://github.com/glitchr-studio/omnidrive#responsibility)).
