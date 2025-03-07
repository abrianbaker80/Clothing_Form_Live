# GitHub Troubleshooting Guide

## Fixing "Permission Denied" Errors

If you're encountering "permission denied" errors when trying to push to GitHub, try these solutions in order:

### 1. Check Your Authentication Method

GitHub has discontinued password authentication for git operations. Make sure you're using either:
- Personal access token (for HTTPS)
- SSH key authentication

### 2. For HTTPS URLs (Recommended)

If your repository URL starts with `https://github.com/`:

1. **Create a Personal Access Token (PAT)**:
   - Go to [GitHub Settings > Developer settings > Personal access tokens](https://github.com/settings/tokens)
   - Click "Generate new token"
   - Select "repo" permissions
   - Generate and **copy the token immediately** (it won't be shown again)

2. **Store Credentials**:
   ```
   git config --global credential.helper store
   ```

3. **Push Again**:
   ```
   git push
   ```
   
   When prompted, enter your GitHub username and use your personal access token as the password.

### 3. For SSH URLs

If your repository URL starts with `git@github.com:`:

1. **Check if you have an SSH key**:
   ```
   ls -al ~/.ssh
   ```

2. **Generate an SSH key** (if needed):
   ```
   ssh-keygen -t ed25519 -C "your_email@example.com"
   ```

3. **Add SSH key to the ssh-agent**:
   ```
   eval "$(ssh-agent -s)"
   ssh-add ~/.ssh/id_ed25519
   ```

4. **Add the SSH key to GitHub**:
   - Copy the SSH key: `clip < ~/.ssh/id_ed25519.pub` (Windows) or `cat ~/.ssh/id_ed25519.pub` (Mac/Linux)
   - Go to [GitHub Settings > SSH and GPG keys](https://github.com/settings/keys)
   - Click "New SSH key" and paste your key

5. **Test connection**:
   ```
   ssh -T git@github.com
   ```

### 4. Check Repository Access

Make sure you have appropriate permissions for the repository:

1. **Verify you're a collaborator**:
   - Go to the repository on GitHub
   - Click "Settings" > "Collaborators and teams"
   - Check if your account is listed

2. **If it's your repository, check ownership**:
   - Make sure you're signed in with the correct GitHub account

### 5. Verify Remote URL

1. **Check your remote URL**:
   ```
   git remote -v
   ```

2. **Update if needed**:
   - For HTTPS: `git remote set-url origin https://github.com/username/repository.git`
   - For SSH: `git remote set-url origin git@github.com:username/repository.git`

### 6. Windows-Specific Solutions

1. **Use GitHub Desktop**:
   - Download [GitHub Desktop](https://desktop.github.com/)
   - Add your repository and try committing/pushing through the GUI

2. **Git Credential Manager**:
   - Install [Git Credential Manager](https://github.com/GitCredentialManager/git-credential-manager)
   - Follow the installation instructions

3. **Fix File Permissions**: Right-click on the folder containing your Git repository, select "Properties" → "Security" tab, and make sure your user account has full control.

### 7. Clear Cached Credentials

If you've previously saved incorrect credentials:

**Windows**:
- Open Credential Manager in Control Panel
- Find any GitHub entries under "Windows Credentials"
- Remove them and try again

**Mac**:
```
git credential-osxkeychain erase
host=github.com
protocol=https
[Press Enter twice]
```

**All Systems**:
```
git config --global --unset credential.helper
```

### 8. GitHub Updater Plugin Specific

If you're using the GitHub Updater plugin, make sure:

1. Your token has the necessary `repo` scope
2. The repository is correctly formatted in the settings
3. Try refreshing/regenerating your Personal Access Token

### Need More Help?

If you're still experiencing issues:
- Check [GitHub's documentation](https://docs.github.com/en/authentication)
- Create a detailed issue in the repository including:
  - The exact error message
  - Your Git version (`git --version`)
  - Your operating system
