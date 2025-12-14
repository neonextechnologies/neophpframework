#!/usr/bin/env php
<?php
/**
 * Add GitHub Issues to Project Board
 * This script adds Phase 1 issues to the GitHub Project and sets them to "Ready" status
 */

$owner = 'neonextechnologies';
$repo = 'neophpframework';
$projectNumber = 8;

// Phase 1 Task IDs
$phase1Tasks = [
    'AUTH-001', 'AUTH-002', 'AUTH-003', 'AUTH-004', 'AUTH-005', 'AUTH-006', 'AUTH-007', 'AUTH-008',
    'AUTH-101', 'AUTH-102', 'AUTH-103', 'AUTH-104', 'AUTH-105', 'AUTH-106',
    'SEC-001', 'SEC-002', 'SEC-003', 'SEC-004'
];

echo "🚀 Adding Phase 1 Issues to GitHub Project\n";
echo str_repeat("=", 50) . "\n";
echo "Project: https://github.com/users/{$owner}/projects/{$projectNumber}\n\n";

// Get project ID
echo "📋 Getting project information...\n";
$projectCmd = "gh project view {$projectNumber} --owner {$owner} --format json";
$projectJson = shell_exec($projectCmd);
$project = json_decode($projectJson, true);

if (!$project) {
    echo "❌ Could not get project information\n";
    exit(1);
}

echo "✅ Project: {$project['title']}\n\n";

// Get all issues with phase-1 label
echo "📝 Getting Phase 1 issues...\n";
$issuesCmd = "gh issue list --repo {$owner}/{$repo} --label phase-1 --limit 100 --json number,title --jq '.[] | \"\\(.number)|\\(.title)\"'";
$issuesOutput = shell_exec($issuesCmd);
$issues = array_filter(explode("\n", trim($issuesOutput)));

echo "Found " . count($issues) . " Phase 1 issues\n\n";

$added = 0;
$errors = 0;

foreach ($issues as $issue) {
    list($number, $title) = explode('|', $issue, 2);
    
    echo "📌 Adding issue #{$number}: {$title}\n";
    
    // Add issue to project
    $addCmd = "gh project item-add {$projectNumber} --owner {$owner} --url https://github.com/{$owner}/{$repo}/issues/{$number} 2>&1";
    $result = shell_exec($addCmd);
    
    if (strpos($result, 'Added item') !== false) {
        echo "   ✅ Added to project\n";
        $added++;
    } else {
        echo "   ⚠️  {$result}\n";
        if (strpos($result, 'already exists') === false) {
            $errors++;
        } else {
            $added++;
        }
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 Summary\n";
echo str_repeat("=", 50) . "\n";
echo "✅ Added: {$added}\n";
echo "❌ Errors: {$errors}\n";
echo "\n🎉 Done! View project: https://github.com/users/{$owner}/projects/{$projectNumber}\n";
