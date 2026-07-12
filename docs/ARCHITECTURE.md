# Asset Infrastructure Architecture

## Request flow

```text
Controller/Form Request
    -> AssetService
        -> AssetValidationService
            -> AssetFileTypeResolver
            -> AssetZipInspectionService
        -> AssetStorageService
        -> AssetFile record
        -> ProcessAssetFile job
            -> AssetVirusScanner
            -> AssetMetadataService
            -> processing events
```

Controllers should not write files, calculate checksums, inspect archives, or extract metadata directly.

## Service boundaries

- `AssetService`: domain orchestration and transactions.
- `AssetStorageService`: physical storage and checksums.
- `AssetValidationService`: security and format acceptance.
- `AssetProcessingService`: scan/metadata state machine.
- `AssetMetadataService`: technical metadata extraction.
- Resolvers: consistent media-type and role vocabulary.

## Processing statuses

```text
pending -> processing -> ready
                     -> failed
```

Virus scanning is independent:

```text
pending -> clean
        -> not_required
        -> rejected
        -> failed
```

A rejected or failed scan causes file processing to fail.

## Revision safety

`replaceFile()` creates a new row and soft deletes the previous row without removing the old physical file. This is intentional. Later licensed-file manifests may continue to reference the prior revision.

## Events

- `AssetCreated`
- `AssetFileAdded`
- `AssetFileRemoved`
- `AssetFileProcessingCompleted`
- `AssetFileProcessingFailed`

These are integration points for audit logging, preview generation, analytics, and notifications without expanding controllers.
