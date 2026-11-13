# Changelog

Every version of omnidrive/azure, newest first ([Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
[Semantic Versioning](https://semver.org/)). A release's notes are its section here: the release
workflow refuses a tag without one.

## [Unreleased]

## [1.0.0] - 2026-11-13

The first stable version. It requires `omnisense/omnidrive` `^1.0` with no stability flag, so `composer require omnidrive/azure` installs it in a project with Composer's default minimum-stability; the 1.x series follows semantic versioning. A tag `vX.Y.Z` is released by `.github/workflows/release.yml` once the package's CI passes on it.

Omnidrive Azure: a container of Azure Blob Storage as an omnidrive drive, through azure-oss/storage-blob-flysystem (the adapter The PHP League names in place of its own, abandoned) - a connection string, a prefix, public and signed (SAS) addresses.
