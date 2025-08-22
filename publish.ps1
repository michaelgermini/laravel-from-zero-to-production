# 🚀 Laravel From Zero to Production - GitHub Publisher (PowerShell)
# This script automates the process of publishing the project to GitHub on Windows

Write-Host "🚀 Starting GitHub publication process..." -ForegroundColor Cyan

# Function to print colored output
function Write-Status {
    param([string]$Message)
    Write-Host "[INFO] $Message" -ForegroundColor Blue
}

function Write-Success {
    param([string]$Message)
    Write-Host "[SUCCESS] $Message" -ForegroundColor Green
}

function Write-Warning {
    param([string]$Message)
    Write-Host "[WARNING] $Message" -ForegroundColor Yellow
}

function Write-Error {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
}

# Check if Git is installed
try {
    $null = Get-Command git -ErrorAction Stop
} catch {
    Write-Error "Git is not installed. Please install Git first."
    exit 1
}

# Check if we're in a Git repository
try {
    $null = git rev-parse --git-dir 2>$null
} catch {
    Write-Error "Not in a Git repository. Please run 'git init' first."
    exit 1
}

# Check if we have commits
try {
    $null = git log --oneline -1 2>$null
} catch {
    Write-Error "No commits found. Please make an initial commit first."
    exit 1
}

Write-Status "Checking Git status..."

# Check if there are uncommitted changes
$uncommitted = git diff-index --quiet HEAD -- 2>$null
if ($LASTEXITCODE -ne 0) {
    Write-Warning "You have uncommitted changes. Please commit them first."
    Write-Host "Run: git add . && git commit -m 'your message'" -ForegroundColor White
    exit 1
}

Write-Success "Git repository is clean and ready."

# Set up remote repository
Write-Status "Setting up remote repository..."

# Check if remote already exists
try {
    $remoteUrl = git remote get-url origin 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Warning "Remote 'origin' already exists."
        Write-Host "Current remote URL: $remoteUrl" -ForegroundColor White
        $response = Read-Host "Do you want to update it? (y/n)"
        if ($response -eq 'y' -or $response -eq 'Y') {
            git remote remove origin
        } else {
            Write-Error "Please update the remote URL manually."
            exit 1
        }
    }
} catch {
    # Remote doesn't exist, continue
}

# Add remote repository
$GITHUB_REPO = "https://github.com/michaelgermini/laravel-from-zero-to-production.git"
git remote add origin $GITHUB_REPO

Write-Success "Remote repository added: $GITHUB_REPO"

# Rename branch to main (if not already)
$CURRENT_BRANCH = git branch --show-current
if ($CURRENT_BRANCH -ne "main") {
    Write-Status "Renaming branch from '$CURRENT_BRANCH' to 'main'..."
    git branch -M main
    Write-Success "Branch renamed to 'main'"
}

# Push to GitHub
Write-Status "Pushing to GitHub..."

git push -u origin main
if ($LASTEXITCODE -eq 0) {
    Write-Success "Successfully pushed to GitHub!"
} else {
    Write-Error "Failed to push to GitHub. Please check your credentials and repository URL."
    exit 1
}

# Display next steps
Write-Host ""
Write-Success "🎉 Repository published successfully!"
Write-Host ""
Write-Host "📋 Next steps:" -ForegroundColor Cyan
Write-Host "1. Visit: https://github.com/michaelgermini/laravel-from-zero-to-production" -ForegroundColor White
Write-Host "2. Configure repository settings:" -ForegroundColor White
Write-Host "   - Add description and topics" -ForegroundColor Gray
Write-Host "   - Enable Issues and Discussions" -ForegroundColor Gray
Write-Host "   - Set up GitHub Pages (optional)" -ForegroundColor Gray
Write-Host "3. Create your first release" -ForegroundColor White
Write-Host "4. Share with the community!" -ForegroundColor White
Write-Host ""
Write-Host "📖 For detailed instructions, see: publish-to-github.md" -ForegroundColor White
Write-Host ""
Write-Success "Happy coding! 🚀"
