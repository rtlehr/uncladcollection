param(
    [string]$ProjectPath = "C:\Development\uncladcollection",
    [string]$Branch = "main",
    [switch]$SkipTests
)

$ErrorActionPreference = "Stop"

Set-Location $ProjectPath

Write-Host "Unclad Collection pre-deployment"
Write-Host "Project: $ProjectPath"
Write-Host "Branch: $Branch"

$currentBranch = git branch --show-current
if ($currentBranch -ne $Branch) {
    throw "Expected branch '$Branch' but current branch is '$currentBranch'."
}

if (-not $SkipTests) {
    php artisan test
}

npm run build

if (-not (Test-Path "public\build\manifest.json")) {
    throw "public\build\manifest.json was not generated."
}

git add -f public/build
git add .htaccess deployment

Write-Host ""
Write-Host "Review staged files:"
git status --short

Write-Host ""
Write-Host "Next commands:"
Write-Host 'git commit -m "Prepare Hostinger deployment"'
Write-Host "git push origin $Branch"
