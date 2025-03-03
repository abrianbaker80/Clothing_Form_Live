# Release Process for Preowned Clothing Form

This document outlines the steps to create a new release for the Preowned Clothing Form plugin.

## Automatic Release via GitHub Actions

1. **Update Version Number**: 
   - Open `preowned-clothing-form.php`
   - Update the version number in the plugin header comment

2. **Commit Changes**:
   ```bash
   git add preowned-clothing-form.php
   git commit -m "Bump version to X.Y.Z.W"
   ```

3. **Create and Push Tag**:
   ```bash
   git tag vX.Y.Z.W
   git push origin vX.Y.Z.W
   git push origin main
   ```
   
4. **Check Workflow**: 
   - Go to your GitHub repository
   - Click on "Actions" tab
   - You should see the "Auto Create Release" workflow running

## Manual Release Process (if automation fails)

1. **Go to GitHub**: Navigate to your repository on GitHub.com

2. **Create Release Manually**:
   - Click on "Releases" in the right sidebar
   - Click "Create a new release" button
   - Enter your tag version (e.g., `v2.3.1.6`)
   - Set "Version X.Y.Z.W" as the title
   - Add description with changelog
   - Click "Publish release"

## Troubleshooting

If the automatic release isn't working:

1. **Check Repository Permissions**:
   - Go to Settings > Actions > General
   - Make sure workflow permissions allow read and write

2. **Verify Workflow File**:
   - Ensure `.github/workflows/auto-release.yml` exists in your repository
   - Check for any syntax errors

3. **Test With Manual Workflow Dispatch**:
   - Add a manual trigger to the workflow file:
   ```yaml
   on:
     push:
       tags:
         - 'v*.*.*.*'
     workflow_dispatch:
   ```
   - Then try running it manually from the Actions tab
