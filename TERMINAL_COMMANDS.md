# 🖥️ Terminal Commands Quick Reference

**WordPress Bootstrap Claude 3.0 - Git & CLI Commands**

---

## 🚀 Three Ways to Push to GitHub

### Method 1: Claude Code (RECOMMENDED) ⭐
```bash
cd wordpress-boostrap-claude
claude-code
```
Then say: `"Push to GitHub at https://github.com/coryhubbell/wordpress-boostrap-claude"`

**Why use this:** Fully automated, intelligent, zero friction. Claude Code handles authentication, error handling, and provides helpful feedback.

---

### Method 2: Automated Script ⚡
```bash
cd wordpress-boostrap-claude
./github-push.sh
```

**Why use this:** One command does everything - checks configuration, shows preview, handles errors, beautiful output.

---

### Method 3: Manual Commands 🔧
```bash
cd wordpress-boostrap-claude
git add -A
git commit -m "🚀 Release: WordPress Bootstrap Claude 3.0"
git push -u origin main
```

**Why use this:** Full control, see exactly what happens, traditional git workflow.

---

## Common Git Commands

### Repository Information
```bash
git status              # Current status
git log --oneline       # Commit history
git diff                # Show changes
git diff --stat         # Changes summary
git branch              # List branches
git remote -v           # Show remotes
```

### Branch Management
```bash
git branch -m master main          # Rename branch to main
git checkout -b feature-name       # Create new branch
git checkout main                  # Switch to main
git branch -d feature-name         # Delete branch
```

### Remote Management
```bash
git remote add origin URL          # Add remote
git remote remove origin           # Remove remote
git remote set-url origin URL      # Change remote URL
git remote show origin             # Show remote details
```

### Commit Operations
```bash
git add -A                         # Stage all files
git add file.php                   # Stage specific file
git commit -m "message"            # Commit with message
git commit --amend                 # Amend last commit
git commit -am "message"           # Stage and commit tracked files
```

### Push/Pull
```bash
git push origin main               # Push to main
git push -u origin main            # Push and set upstream
git push --force                   # Force push (DANGEROUS!)
git pull origin main               # Pull from main
git pull --rebase origin main      # Pull with rebase
```

### Undo Operations
```bash
git reset HEAD file.php            # Unstage file
git checkout -- file.php           # Discard changes
git reset --soft HEAD~1            # Undo last commit (keep changes)
git reset --hard HEAD~1            # Undo last commit (discard changes)
git revert <commit-hash>           # Create new commit that undoes changes
```

---

## Translation Bridge CLI Commands

### Basic Translation
```bash
# Make executable (first time only)
chmod +x wpbc

# Translate Bootstrap to DIVI
./wpbc translate bootstrap divi components/hero.html

# Translate Elementor to Bootstrap
./wpbc translate elementor bootstrap page.json

# Translate DIVI to Elementor
./wpbc translate divi elementor section.php
```

### Batch Operations
```bash
# Batch translate entire directory
./wpbc batch-translate bootstrap divi components/

# Create new component
./wpbc create component pricing-table

# Show version info
./wpbc version

# Show help
./wpbc help
```

---

## Project Navigation

```bash
# Navigate to project
cd wordpress-boostrap-claude
cd ~/wordpress-boostrap-claude          # From home directory

# List files
ls -la                             # Show all files including hidden
ls -lh                             # Human-readable file sizes
tree                               # Show directory tree (if installed)
tree -L 2                          # Show 2 levels deep

# View file contents
cat README.md                      # Show entire file
less README.md                     # View with pager (q to quit)
head -n 20 functions.php           # First 20 lines
tail -n 20 functions.php           # Last 20 lines
tail -f error.log                  # Follow log file (Ctrl+C to exit)
```

---

## File Operations

```bash
# Find files
find . -name "*.php"               # Find all PHP files
find . -type f -name "*.md"        # Find all markdown files
grep -r "function_name" .          # Search for text in files
grep -rn "TODO" .                  # Search with line numbers

# File permissions
chmod +x script.sh                 # Make executable
chmod 644 file.php                 # Set standard file permissions
chmod 755 directory/               # Set directory permissions

# Copy and move
cp file.php backup.php             # Copy file
cp -r directory/ backup/           # Copy directory recursively
mv old-name.php new-name.php       # Rename/move file

# Create and delete
mkdir new-directory                # Create directory
mkdir -p path/to/nested/dir        # Create nested directories
rm file.php                        # Delete file
rm -rf directory/                  # Delete directory (CAREFUL!)
```

---

## GitHub Setup (First Time)

### Configure Git
```bash
git config --global user.name "Cory Hubbell"
git config --global user.email "your-email@example.com"
git config --global init.defaultBranch main
git config --list                  # View configuration
```

### Initialize Repository
```bash
git init                           # Initialize git repo
git add -A                         # Stage all files
git commit -m "Initial commit"     # First commit
```

### Add Remote Repository
```bash
git remote add origin https://github.com/coryhubbell/wordpress-boostrap-claude.git
git branch -M main                 # Ensure branch is named main
git push -u origin main            # Push and set upstream
```

### Authentication Setup

**Option 1: Personal Access Token (Recommended)**
```bash
# When prompted for password, use your Personal Access Token
# Get token from: https://github.com/settings/tokens
# Required scopes: repo, workflow
```

**Option 2: SSH Keys**
```bash
# Generate SSH key
ssh-keygen -t ed25519 -C "your-email@example.com"

# Copy public key
cat ~/.ssh/id_ed25519.pub

# Add to GitHub: https://github.com/settings/keys

# Test connection
ssh -T git@github.com

# Use SSH remote
git remote set-url origin git@github.com:coryhubbell/wordpress-boostrap-claude.git
```

---

## Environment Setup

### Check Prerequisites
```bash
git --version                      # Check Git installed
which git                          # Git location
node --version                     # Check Node (if needed)
php --version                      # Check PHP
wp --info                          # Check WP-CLI
```

### Install Git (if needed)
```bash
# macOS
brew install git

# Ubuntu/Debian
sudo apt-get update
sudo apt-get install git

# Windows
# Download from git-scm.com
```

### Install Claude Code
```bash
# macOS/Linux
npm install -g @anthropic-ai/claude-code

# Verify installation
claude-code --version

# Login to Claude
claude-code login
```

---

## Useful Aliases

Add to `~/.bashrc` or `~/.zshrc`:

```bash
# Git shortcuts
alias gs='git status'
alias ga='git add -A'
alias gc='git commit -m'
alias gp='git push'
alias gl='git log --oneline'
alias gd='git diff'
alias gb='git branch'
alias gco='git checkout'

# Project navigation
alias wpbc='cd ~/wordpress-boostrap-claude'
alias wpbclog='tail -f ~/wordpress-boostrap-claude/debug.log'

# Translation Bridge
alias translate='./wpbc translate'
alias wpbc-version='./wpbc version'
```

After adding aliases, reload:
```bash
source ~/.bashrc    # or
source ~/.zshrc
```

---

## Quick Problem Solvers

### "Permission denied" Error
```bash
# Check file permissions
ls -la github-push.sh

# Make script executable
chmod +x github-push.sh
chmod +x wpbc
```

### "fatal: not a git repository"
```bash
# Check if in correct directory
pwd
ls -la .git

# Initialize git if needed
git init
```

### "fatal: remote origin already exists"
```bash
# Remove and re-add remote
git remote remove origin
git remote add origin https://github.com/coryhubbell/wordpress-boostrap-claude.git
```

### "Authentication failed"
```bash
# Use Personal Access Token from GitHub
# Settings → Developer Settings → Personal Access Tokens → Generate new token
# Required scopes: repo, workflow
# Use token as password when pushing
```

### "rejected - non-fast-forward"
```bash
# Pull latest changes first
git pull origin main --rebase

# Or force push (DANGEROUS - only if you're sure)
git push --force origin main
```

### "refusing to merge unrelated histories"
```bash
git pull origin main --allow-unrelated-histories
```

---

## Emergency Recovery

### Save Current Work Before Risky Operations
```bash
# Create backup branch
git branch backup-$(date +%Y%m%d-%H%M%S)

# Or stash changes
git stash save "Work in progress"
git stash list
git stash pop                      # Restore latest stash
```

### Completely Reset Local Repository (DANGEROUS!)
```bash
# WARNING: This deletes ALL local changes!
git fetch origin
git reset --hard origin/main
git clean -fd                      # Remove untracked files
```

### Restore Deleted File
```bash
# Find file in history
git log -- path/to/file.php

# Restore from specific commit
git checkout <commit-hash> -- path/to/file.php
```

### Undo Last Push (VERY DANGEROUS!)
```bash
# Only do this if you're the only one using the repository
git reset --hard HEAD~1
git push --force origin main
```

---

## Success Indicators

### Verify Successful Push
```bash
git log --oneline -1               # Shows your latest commit
git remote show origin             # Shows push succeeded
git ls-remote origin               # Lists remote branches
```

### Check Repository Online
```bash
# Visit your repository
open https://github.com/coryhubbell/wordpress-boostrap-claude

# Or use GitHub CLI (if installed)
gh repo view
gh repo view --web
```

---

## Advanced Git Operations

### Interactive Rebase
```bash
# Edit last 3 commits
git rebase -i HEAD~3

# Squash commits
# In editor, change 'pick' to 'squash' for commits to combine
```

### Cherry-Pick Commits
```bash
# Apply specific commit from another branch
git cherry-pick <commit-hash>
```

### View File History
```bash
# Show all changes to a file
git log -p path/to/file.php

# Show who changed what
git blame path/to/file.php
```

### Tags and Releases
```bash
# Create tag
git tag -a v3.0.0 -m "WordPress Bootstrap Claude 3.0"

# Push tags
git push origin --tags

# List tags
git tag -l
```

---

## Claude Code Natural Language Commands

When using `claude-code`, you can say:

```bash
claude-code
```

Then use natural language:
- "Push to GitHub at https://github.com/coryhubbell/wordpress-boostrap-claude"
- "Commit with message: Fixed accessibility issues"
- "Show me uncommitted changes"
- "Create a new branch called feature-dark-mode"
- "Help me fix authentication error"
- "What files have changed?"
- "Undo my last commit but keep the changes"
```

---

## Getting Help

### Git Help
```bash
git help                           # General help
git help commit                    # Help for specific command
git help -a                        # List all commands
man git                            # Git manual
```

### Project Help
```bash
./wpbc help                        # Translation Bridge CLI help
cat README.md                      # Read project README
cat .claude-code/README.md         # Claude Code integration guide
```

### Online Resources
- Git Documentation: https://git-scm.com/doc
- GitHub Docs: https://docs.github.com
- WordPress Bootstrap Claude: https://github.com/coryhubbell/wordpress-boostrap-claude

### Ask Claude Code
```bash
claude-code
# Then ask questions like:
# "How do I resolve merge conflicts?"
# "What's the difference between rebase and merge?"
# "Help me understand this git error: [paste error]"
```

---

## Quick Workflow Examples

### Daily Development Workflow
```bash
# Start of day - get latest changes
git pull origin main

# Make changes, test locally
# ...

# Stage and commit
git add -A
git commit -m "Added new feature X"

# Push to GitHub
git push origin main
```

### Feature Branch Workflow
```bash
# Create feature branch
git checkout -b feature-new-translator

# Make changes, commit
git add -A
git commit -m "Implemented DIVI to Gutenberg translation"

# Push feature branch
git push -u origin feature-new-translator

# Create pull request on GitHub
# After approval, merge to main
```

### Hotfix Workflow
```bash
# Create hotfix branch from main
git checkout main
git checkout -b hotfix-critical-bug

# Fix bug, commit
git add -A
git commit -m "Fixed critical accessibility issue"

# Push and merge immediately
git push -u origin hotfix-critical-bug
git checkout main
git merge hotfix-critical-bug
git push origin main
```

---

## Performance Tips

### Speed Up Git Operations
```bash
# Enable parallel index preload
git config --global core.preloadindex true

# Enable filesystem cache
git config --global core.fscache true

# Increase HTTP buffer
git config --global http.postBuffer 524288000
```

### Clean Up Repository
```bash
# Remove old branches
git remote prune origin

# Garbage collection
git gc --aggressive --prune=now

# Check repository size
du -sh .git
```

---

## Troubleshooting Checklist

- [ ] In correct directory? (`pwd`)
- [ ] Git initialized? (`ls -la .git`)
- [ ] Remote configured? (`git remote -v`)
- [ ] Branch correct? (`git branch`)
- [ ] Uncommitted changes? (`git status`)
- [ ] Authentication working? (test with simple push)
- [ ] Latest changes pulled? (`git pull`)

---

*Quick Reference for WordPress Bootstrap Claude 3.0*
*For automated push, use: `./github-push.sh`*
*For AI-assisted push, use: `claude-code`*
