#!/usr/bin/env pwsh
# Create Labels for NeoCore Repository

param(
    [string]$Owner = "neonextechnologies",
    [string]$Repo = "neophpframework"
)

Write-Host "🏷️  Creating Labels for $Owner/$Repo" -ForegroundColor Cyan
Write-Host "====================================" -ForegroundColor Cyan

# Check if GitHub CLI is installed
if (-not (Get-Command gh -ErrorAction SilentlyContinue)) {
    Write-Host "❌ GitHub CLI not found. Install from https://cli.github.com/" -ForegroundColor Red
    exit 1
}

# Priority Labels
$priorityLabels = @(
    @{name="priority:critical"; color="d73a4a"; description="🔴 Critical priority - Must be done ASAP"},
    @{name="priority:high"; color="fbca04"; description="🟡 High priority - Should be done soon"},
    @{name="priority:medium"; color="0e8a16"; description="🟢 Medium priority - Nice to have"},
    @{name="priority:low"; color="1d76db"; description="🔵 Low priority - Future consideration"}
)

# Phase Labels
$phaseLabels = @(
    @{name="phase-1-auth"; color="7057ff"; description="Phase 1: Authentication & Security"},
    @{name="phase-2-storage"; color="d876e3"; description="Phase 2: File Storage & Media"},
    @{name="phase-3-cache"; color="f9d0c4"; description="Phase 3: Caching & Performance"},
    @{name="phase-4-email"; color="c2e0c6"; description="Phase 4: Email & Notifications"},
    @{name="phase-5-api"; color="fef2c0"; description="Phase 5: API Enhancements"},
    @{name="phase-6-utils"; color="bfdadc"; description="Phase 6: Developer Utilities"},
    @{name="phase-7-advanced"; color="c5def5"; description="Phase 7: Advanced Features"},
    @{name="phase-8-cms"; color="d4c5f9"; description="Phase 8: CMS Features"},
    @{name="phase-9-testing"; color="ededed"; description="Phase 9: Testing & Quality"},
    @{name="phase-10-enterprise"; color="0052cc"; description="Phase 10: Enterprise Features"}
)

# Epic Labels
$epicLabels = @(
    @{name="epic:authentication"; color="5319e7"; description="User Authentication System"},
    @{name="epic:authorization"; color="5319e7"; description="Authorization System"},
    @{name="epic:security"; color="b60205"; description="Security Enhancements"},
    @{name="epic:storage"; color="d93f0b"; description="File Storage Abstraction"},
    @{name="epic:media"; color="d93f0b"; description="Media Manager"},
    @{name="epic:caching"; color="0e8a16"; description="Cache Abstraction Layer"},
    @{name="epic:orm-cache"; color="0e8a16"; description="Query & ORM Caching"},
    @{name="epic:email"; color="1d76db"; description="Email System"},
    @{name="epic:notifications"; color="1d76db"; description="Notification System"},
    @{name="epic:api-resources"; color="fbca04"; description="API Resources"},
    @{name="epic:pagination"; color="fbca04"; description="Pagination"},
    @{name="epic:api-auth"; color="fbca04"; description="API Authentication"},
    @{name="epic:api-versioning"; color="fbca04"; description="API Versioning"},
    @{name="epic:collection"; color="c5def5"; description="Collection Class"},
    @{name="epic:helpers"; color="c5def5"; description="String & Array Helpers"},
    @{name="epic:http-client"; color="c5def5"; description="HTTP Client"},
    @{name="epic:logging"; color="e99695"; description="Logging System"},
    @{name="epic:i18n"; color="bfd4f2"; description="Localization"},
    @{name="epic:scheduler"; color="d4c5f9"; description="Task Scheduler"},
    @{name="epic:soft-deletes"; color="c2e0c6"; description="Soft Deletes"}
)

# Type Labels
$typeLabels = @(
    @{name="type:feature"; color="0e8a16"; description="New feature"},
    @{name="type:bug"; color="d73a4a"; description="Bug fix"},
    @{name="type:docs"; color="1d76db"; description="Documentation"},
    @{name="type:enhancement"; color="fbca04"; description="Enhancement"}
)

$allLabels = $priorityLabels + $phaseLabels + $epicLabels + $typeLabels

$created = 0
$errors = 0

foreach ($label in $allLabels) {
    Write-Host "Creating label: $($label.name)" -NoNewline
    
    $result = gh label create $label.name `
        --repo "$Owner/$Repo" `
        --description $label.description `
        --color $label.color `
        2>&1
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host " ✅" -ForegroundColor Green
        $created++
    } elseif ($result -like "*already exists*") {
        Write-Host " ⏭️  (already exists)" -ForegroundColor Yellow
    } else {
        Write-Host " ❌" -ForegroundColor Red
        $errors++
    }
}

Write-Host ""
Write-Host "====================================" -ForegroundColor Cyan
Write-Host "📊 Summary" -ForegroundColor Cyan
Write-Host "====================================" -ForegroundColor Cyan
Write-Host "✅ Created: $created" -ForegroundColor Green
Write-Host "❌ Errors: $errors" -ForegroundColor Red
Write-Host ""
Write-Host "🎉 Done!" -ForegroundColor Green
