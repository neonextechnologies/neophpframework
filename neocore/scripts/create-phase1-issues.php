<?php

/**
 * Create GitHub Issues for Phase 1 Only (Authentication & Security)
 * 
 * สร้างแค่ Phase 1 (17 tasks) เพื่อเริ่มงานได้เลย
 * 
 * Prerequisites:
 * 1. Install GitHub CLI: winget install --id GitHub.cli
 * 2. Authenticate: gh auth login
 * 3. Run: php scripts/create-phase1-issues.php
 */

$owner = 'neonextechnologies';
$repo = 'neophpframework';
$csvFile = __DIR__ . '/../backlog.csv';
$dryRun = false; // เปลี่ยนเป็น false เพื่อสร้างจริง

// Check if CSV file exists
if (!file_exists($csvFile)) {
    die("Error: backlog.csv not found!\n");
}

// Check if GitHub CLI is installed
exec('gh --version 2>&1', $output, $returnCode);
if ($returnCode !== 0) {
    die("Error: GitHub CLI not installed. Run: winget install --id GitHub.cli\n");
}

echo "🔐 Creating Phase 1 Issues: Authentication & Security\n";
echo "====================================================\n";
echo "Repository: {$owner}/{$repo}\n";
echo "Dry Run: " . ($dryRun ? 'YES (change \$dryRun to false to create)' : 'NO') . "\n\n";

// Parse CSV
$csv = array_map('str_getcsv', file($csvFile));
$headers = array_shift($csv);

$created = 0;
$errors = 0;

foreach ($csv as $row) {
    $task = array_combine($headers, $row);
    
    // Filter: Phase 1 only
    if (strpos($task['Phase'], 'Phase 1') === false) {
        continue;
    }
    
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

### Acceptance Criteria
- [ ] Code implemented
- [ ] Tests written
- [ ] Documentation updated
- [ ] Code reviewed
- [ ] Merged to main

### Status
{$task['Status']}

---
*Auto-generated from BACKLOG.md - Phase 1*
MARKDOWN;

    echo "📝 {$taskId}: {$task['Title']}\n";
    
    if ($dryRun) {
        echo "   [DRY RUN] Labels: {$labels}\n\n";
        $created++;
        continue;
    }
    
    // Create issue using GitHub CLI
    $titleEscaped = str_replace('"', '\\"', $title);
    $bodyEscaped = str_replace('"', '\\"', $body);
    
    $command = sprintf(
        'gh issue create --repo %s/%s --title "%s" --body "%s" --label "%s" 2>&1',
        $owner,
        $repo,
        $titleEscaped,
        $bodyEscaped,
        $labels
    );
    
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "   ✅ Created!\n\n";
        $created++;
    } else {
        echo "   ❌ Failed: " . implode("\n", $output) . "\n\n";
        $errors++;
    }
    
    // Sleep to avoid rate limiting
    sleep(1);
}

echo "====================================================\n";
echo "📊 Summary\n";
echo "====================================================\n";
echo "✅ Created: {$created}\n";
echo "❌ Errors: {$errors}\n\n";

if ($dryRun) {
    echo "💡 This was a DRY RUN.\n";
    echo "   Set \$dryRun = false in the script to actually create issues.\n\n";
} else {
    echo "🎉 Done! Phase 1 issues created.\n";
    echo "   Next: Go to GitHub → Projects → New project → Add these issues\n\n";
}
