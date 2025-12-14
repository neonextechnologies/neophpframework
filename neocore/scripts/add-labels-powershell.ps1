# Add Labels to GitHub Issues
# This script adds labels to all issues based on backlog.csv

$owner = "neonextechnologies"
$repo = "neophpframework"
$csvPath = Join-Path $PSScriptRoot "..\backlog.csv"

Write-Host "🚀 Adding Labels to GitHub Issues" -ForegroundColor Cyan
Write-Host "=" * 50
Write-Host ""

if (-not (Test-Path $csvPath)) {
    Write-Host "❌ Error: backlog.csv not found at $csvPath" -ForegroundColor Red
    exit 1
}

# Import CSV
$tasks = Import-Csv -Path $csvPath

$processed = 0
$errors = 0

foreach ($task in $tasks) {
    $taskId = $task.'Task ID'
    $labels = $task.Labels
    
    Write-Host "📝 Processing: $taskId" -ForegroundColor Yellow
    
    # Find issue by title
    $searchTitle = "[$taskId]"
    $issues = gh issue list --repo "$owner/$repo" --search "in:title $searchTitle" --json number,title | ConvertFrom-Json
    
    if ($issues.Count -eq 0) {
        Write-Host "   ⚠️  Issue not found" -ForegroundColor Yellow
        $errors++
        continue
    }
    
    $issueNumber = $issues[0].number
    Write-Host "   Found issue #$issueNumber" -ForegroundColor Gray
    
    # Split labels and add them
    $labelArray = $labels -split ',' | ForEach-Object { $_.Trim() }
    
    try {
        foreach ($label in $labelArray) {
            gh issue edit $issueNumber --repo "$owner/$repo" --add-label $label 2>&1 | Out-Null
        }
        Write-Host "   ✅ Labels added: $labels" -ForegroundColor Green
        $processed++
    }
    catch {
        Write-Host "   ❌ Error: $_" -ForegroundColor Red
        $errors++
    }
    
    # Rate limiting
    Start-Sleep -Milliseconds 300
}

Write-Host ""
Write-Host "=" * 50
Write-Host "📊 Summary" -ForegroundColor Cyan
Write-Host "=" * 50
Write-Host "✅ Processed: $processed" -ForegroundColor Green
Write-Host "❌ Errors: $errors" -ForegroundColor Red
Write-Host ""
Write-Host "🎉 Done!" -ForegroundColor Green
