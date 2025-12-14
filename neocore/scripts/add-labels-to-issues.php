#!/usr/bin/env php
<?php
/**
 * Add Labels to GitHub Issues from Backlog
 * This script reads backlog.csv and adds appropriate labels to each issue
 */

$owner = 'neonextechnologies';
$repo = 'neophpframework';
$csvFile = __DIR__ . '/../backlog.csv';

echo "🚀 Adding Labels to GitHub Issues\n";
echo str_repeat("=", 50) . "\n\n";

if (!file_exists($csvFile)) {
    echo "❌ Error: backlog.csv not found at {$csvFile}\n";
    exit(1);
}

// Read CSV
$handle = fopen($csvFile, 'r');
$headers = fgetcsv($handle); // Skip header row

$processed = 0;
$errors = 0;

while (($row = fgetcsv($handle)) !== false) {
    $taskId = $row[0];
    $title = $row[1];
    $labels = $row[7]; // Labels column
    
    // Parse labels (comma-separated)
    $labelArray = array_map('trim', explode(',', $labels));
    
    echo "📝 Processing: {$taskId}\n";
    
    // Find issue by title
    $searchTitle = "[{$taskId}]";
    $searchCmd = "gh issue list --repo {$owner}/{$repo} --search \"in:title {$searchTitle}\" --json number --jq \".[0].number\" 2>&1";
    $issueNumber = trim(shell_exec($searchCmd));
    
    if (empty($issueNumber) || $issueNumber === 'null') {
        echo "   ⚠️  Issue not found\n";
        $errors++;
        continue;
    }
    
    echo "   Found issue #{$issueNumber}\n";
    
    // Add labels
    $labelsList = implode(',', $labelArray);
    $labelCmd = "gh issue edit {$issueNumber} --repo {$owner}/{$repo} --add-label \"{$labelsList}\" 2>&1";
    $result = shell_exec($labelCmd);
    
    if (strpos($result, 'https://github.com') !== false) {
        echo "   ✅ Labels added: {$labelsList}\n";
        $processed++;
    } else {
        echo "   ❌ Error: {$result}\n";
        $errors++;
    }
    
    // Rate limiting - wait a bit
    usleep(500000); // 0.5 second
}

fclose($handle);

echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 Summary\n";
echo str_repeat("=", 50) . "\n";
echo "✅ Processed: {$processed}\n";
echo "❌ Errors: {$errors}\n";
echo "\n🎉 Done!\n";
