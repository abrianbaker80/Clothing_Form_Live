$ErrorActionPreference = 'Stop'
$pluginFile = Join-Path (Split-Path -Parent $PSScriptRoot) "preowned-clothing-form.php"
$changelogFile = Join-Path (Split-Path -Parent $PSScriptRoot) "CHANGELOG.md"

Write-Host "Enter new version number (e.g., 2.8.1.2): " -NoNewline
$newVersion = Read-Host

$today = Get-Date -Format "yyyy-MM-dd"
Write-Host "Updating to version $newVersion (date: $today)"

# Read the plugin file content
$pluginContent = Get-Content $pluginFile -Raw
$oldVersion = [regex]::Match($pluginContent, "Version: ([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)").Groups[1].Value

Write-Host "Updating from version $oldVersion to $newVersion"

# Update plugin header version
$pluginContent = $pluginContent -replace "Version: [0-9]+\.[0-9]+\.[0-9]+\.[0-9]+", "Version: $newVersion"

# Update PCF_VERSION constant
$pluginContent = $pluginContent -replace "define\('PCF_VERSION', '[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+'\)", "define('PCF_VERSION', '$newVersion')"

# Write back to plugin file
Set-Content -Path $pluginFile -Value $pluginContent

# Read the changelog content
$changelogContent = Get-Content $changelogFile -Raw

# Create new changelog entry
$newEntry = "## [$newVersion] - $today`n### Fixed`n- Version update`n`n"

# Add entry after the header
$updatedChangelog = $changelogContent -replace "(?<=# Changelog[\s\S]*?)(?=## \[)", "`$0$newEntry"

# Write back to changelog file
Set-Content -Path $changelogFile -Value $updatedChangelog

# Git operations
Write-Host "Enter commit message: " -NoNewline
$commitMsg = Read-Host

Write-Host "Enter tag message: " -NoNewline
$tagMsg = Read-Host

git add .
git commit -m "$commitMsg"
git tag -a "v$newVersion" -m "$tagMsg"
git push origin main
git push origin "v$newVersion"

Write-Host "Version updated to $newVersion and tag created successfully!" -ForegroundColor Green
