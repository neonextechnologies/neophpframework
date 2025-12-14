<?php

/**
 * Sync NeoCore Backlog to GitHub Issues
 * 
 * Prerequisites:
 * 1. Install GitHub CLI: https://cli.github.com/
 * 2. Authenticate: gh auth login
 * 3. Set environment variables:
 *    - GITHUB_OWNER: Your GitHub username or organization
 *    - GITHUB_REPO: Repository name (e.g., neophpframework)
 * 
 * Usage:
 * php scripts/create-github-issues.php
 */

// Configuration
$owner = getenv('GITHUB_OWNER') ?: 'neonextechnologies';
$repo = getenv('GITHUB_REPO') ?: 'neophpframework';
$csvFile = __DIR__ . '/../backlog.csv';
$dryRun = false; // Set to false to actually create issues

// Check if CSV file exists
if (!file_exists($csvFile)) {
    die("Error: backlog.csv not found!\n");
}

// Check if GitHub CLI is installed
exec('gh --version', $output, $returnCode);
if ($returnCode !== 0) {
    die("Error: GitHub CLI not installed. Install from https://cli.github.com/\n");
}

echo "🚀 NeoCore Backlog to GitHub Issues\n";
echo "====================================\n";
echo "Repository: {$owner}/{$repo}\n";
echo "Dry Run: " . ($dryRun ? 'YES' : 'NO') . "\n\n";

// Parse CSV
$csv = array_map('str_getcsv', file($csvFile));
$headers = array_shift($csv);

$created = 0;
$skipped = 0;
$errors = 0;

foreach ($csv as $row) {
    $task = array_combine($headers, $row);
    
    // Prepare issue data
    $taskId = $task['Task ID'];
    $title = "[{$taskId}] {$task['Title']}";
    $labels = $task['Labels'];
    $body = <<<MARKDOWN
## 📋 Task: {$task['Task ID']}

### Description
{$task['Description']}

### Details
- **Phase:** {$task['Phase']}
- **Epic:** {$task['Epic']}
- **Priority:** {$task['Priority']}
- **Story Points:** {$task['Story Points']}
- **Estimated Days:** {$task['Estimated Days']}

### Files to Create/Modify
```
{$task['Files']}
```

### Status
{$task['Status']}

---
*Auto-generated from BACKLOG.md*
MARKDOWN;

    echo "📝 Processing: {$taskId} - {$task['Title']}\n";
    
    if ($dryRun) {
        echo "   [DRY RUN] Would create issue with labels: {$labels}\n";
        $created++;
        continue;
    }
    
    // Create issue using GitHub CLI
    $command = sprintf(
        'gh issue create --repo %s/%s --title %s --body %s --label %s',
        escapeshellarg($owner),
        escapeshellarg($repo),
        escapeshellarg($title),
        escapeshellarg($body),
        escapeshellarg($labels)
    );
    
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "   ✅ Created issue: {$title}\n";
        $created++;
    } else {
        echo "   ❌ Failed to create issue: {$title}\n";
        $errors++;
    }
    
    // Sleep to avoid rate limiting
    sleep(1);
}

echo "\n====================================\n";
echo "📊 Summary\n";
echo "====================================\n";
echo "✅ Created: {$created}\n";
echo "⏭️  Skipped: {$skipped}\n";
echo "❌ Errors: {$errors}\n";

if ($dryRun) {
    echo "\n💡 This was a DRY RUN. Set \$dryRun = false to actually create issues.\n";
} else {
    echo "\n🎉 Done! Check your repository issues.\n";
}
