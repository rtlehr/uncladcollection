# File Manifest

## Replacement files

- `.env.example`
- `app/Models/Asset.php`
- `app/Models/AssetFile.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/AssetStorageService.php`
- `config/asset-media.php`

## New files

- `app/Contracts/AssetVirusScanner.php`
- `app/Data/AssetVirusScanResult.php`
- `app/Data/ResolvedAssetUpload.php`
- `app/Events/AssetCreated.php`
- `app/Events/AssetFileAdded.php`
- `app/Events/AssetFileRemoved.php`
- `app/Events/AssetFileProcessingCompleted.php`
- `app/Events/AssetFileProcessingFailed.php`
- `app/Jobs/ProcessAssetFile.php`
- `app/Observers/AssetObserver.php`
- `app/Observers/AssetFileObserver.php`
- `app/Services/AssetFileRoleResolver.php`
- `app/Services/AssetFileTypeResolver.php`
- `app/Services/AssetMetadataService.php`
- `app/Services/AssetProcessingService.php`
- `app/Services/AssetService.php`
- `app/Services/AssetValidationService.php`
- `app/Services/AssetZipInspectionService.php`
- `app/Services/NullAssetVirusScanner.php`
- `tests/Feature/Assets/AssetInfrastructureTest.php`
- Package documentation files
