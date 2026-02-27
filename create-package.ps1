# ===============================================================================
# LocaGed Package Creator
# This script creates a clean deployment package for the LocaGed application
# ===============================================================================

$ErrorActionPreference = "Stop"

# Configuration
$ProjectRoot = "c:\Users\faisa\OneDrive\Desktop\M C\Latest\locaged-final-master"
$OutputPath = "c:\Users\faisa\OneDrive\Desktop\M C\Latest\locaged-package.zip"

Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host "  LocaGed Package Creator" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host ""

# Check if project directory exists
if (-not (Test-Path $ProjectRoot)) {
    Write-Host "ERROR: Project directory not found: $ProjectRoot" -ForegroundColor Red
    exit 1
}

# Remove existing package if it exists
if (Test-Path $OutputPath) {
    Write-Host "[1/4] Removing existing package..." -ForegroundColor Yellow
    Remove-Item $OutputPath -Force
}

Write-Host "[2/4] Preparing temporary directory..." -ForegroundColor Yellow

# Create temporary directory
$TempDir = Join-Path $env:TEMP "locaged-package-$(Get-Date -Format 'yyyyMMddHHmmss')"
New-Item -ItemType Directory -Path $TempDir -Force | Out-Null

try {
    Write-Host "[3/4] Copying project files (excluding unnecessary files)..." -ForegroundColor Yellow
    
    # Define what to copy (include these)
    $ItemsToInclude = @(
        "app",
        "bootstrap", 
        "config",
        "database",
        "lang",
        "public",
        "resources",
        "routes",
        ".env.example",
        ".editorconfig",
        ".gitignore",
        ".gitattributes",
        "artisan",
        "composer.json",
        "composer.lock",
        "package.json",
        "package-lock.json",
        "vite.config.js",
        "phpunit.xml",
        "install.sh",
        "restore.sh",
        "README.md"
    )
    
    # Copy each item
    foreach ($item in $ItemsToInclude) {
        $sourcePath = Join-Path $ProjectRoot $item
        if (Test-Path $sourcePath) {
            $destPath = Join-Path $TempDir $item
            Write-Host "  • Copying $item..." -ForegroundColor Gray
            
            if (Test-Path $sourcePath -PathType Container) {
                # It's a directory
                Copy-Item -Path $sourcePath -Destination $destPath -Recurse -Force
            } else {
                # It's a file
                Copy-Item -Path $sourcePath -Destination $destPath -Force
            }
        } else {
            Write-Host "  ⚠ Skipping $item (not found)" -ForegroundColor DarkYellow
        }
    }
    
    # Create storage directory structure (empty, with .gitignore files)
    Write-Host "  • Creating storage directory structure..." -ForegroundColor Gray
    $StoragePath = Join-Path $TempDir "storage"
    New-Item -ItemType Directory -Path "$StoragePath/app/public" -Force | Out-Null
    New-Item -ItemType Directory -Path "$StoragePath/framework/cache" -Force | Out-Null
    New-Item -ItemType Directory -Path "$StoragePath/framework/sessions" -Force | Out-Null
    New-Item -ItemType Directory -Path "$StoragePath/framework/testing" -Force | Out-Null
    New-Item -ItemType Directory -Path "$StoragePath/framework/views" -Force | Out-Null
    New-Item -ItemType Directory -Path "$StoragePath/logs" -Force | Out-Null
    
    # Create .gitignore files in storage subdirectories
    @"
*
!.gitignore
"@ | Out-File -FilePath "$StoragePath/app/.gitignore" -Encoding utf8 -Force
    
    @"
*
!.gitignore
"@ | Out-File -FilePath "$StoragePath/framework/cache/.gitignore" -Encoding utf8 -Force
    
    @"
*
!.gitignore
"@ | Out-File -FilePath "$StoragePath/framework/sessions/.gitignore" -Encoding utf8 -Force
    
    @"
*
!.gitignore
"@ | Out-File -FilePath "$StoragePath/framework/testing/.gitignore" -Encoding utf8 -Force
    
    @"
*
!.gitignore
"@ | Out-File -FilePath "$StoragePath/framework/views/.gitignore" -Encoding utf8 -Force
    
    @"
*
!.gitignore
"@ | Out-File -FilePath "$StoragePath/logs/.gitignore" -Encoding utf8 -Force
    
    # Clean up unwanted files/directories from the copied content
    Write-Host "  • Cleaning up excluded files..." -ForegroundColor Gray
    
    # Remove vendor if it exists
    $VendorPath = Join-Path $TempDir "vendor"
    if (Test-Path $VendorPath) {
        Remove-Item -Path $VendorPath -Recurse -Force
    }
    
    # Remove node_modules if it exists
    $NodeModulesPath = Join-Path $TempDir "node_modules"
    if (Test-Path $NodeModulesPath) {
        Remove-Item -Path $NodeModulesPath -Recurse -Force
    }
    
    # Remove .env if it was copied
    $EnvPath = Join-Path $TempDir ".env"
    if (Test-Path $EnvPath) {
        Remove-Item -Path $EnvPath -Force
    }
    
    # Remove .git directory if it was copied
    $GitPath = Join-Path $TempDir ".git"
    if (Test-Path $GitPath) {
        Remove-Item -Path $GitPath -Recurse -Force
    }
    
    # Remove public/build if it exists
    $PublicBuildPath = Join-Path $TempDir "public/build"
    if (Test-Path $PublicBuildPath) {
        Remove-Item -Path $PublicBuildPath -Recurse -Force
    }
    
    Write-Host "[4/4] Creating ZIP package..." -ForegroundColor Yellow
    
    # Create the ZIP file
    Compress-Archive -Path "$TempDir\*" -DestinationPath $OutputPath -Force
    
    # Get file size
    $FileSize = (Get-Item $OutputPath).Length / 1MB
    
    Write-Host ""
    Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Green
    Write-Host "  Package created successfully!" -ForegroundColor Green
    Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Green
    Write-Host ""
    Write-Host "Package location: $OutputPath" -ForegroundColor Cyan
    Write-Host "Package size: $([math]::Round($FileSize, 2)) MB" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "  1. Transfer this ZIP file to your client's server" -ForegroundColor White
    Write-Host "  2. Extract and run the install.sh script on the server" -ForegroundColor White
    Write-Host "  3. Follow the deployment guide for configuration" -ForegroundColor White
    Write-Host ""
    
} finally {
    # Cleanup temporary directory
    if (Test-Path $TempDir) {
        Write-Host "Cleaning up temporary files..." -ForegroundColor Gray
        Remove-Item -Path $TempDir -Recurse -Force
    }
}
