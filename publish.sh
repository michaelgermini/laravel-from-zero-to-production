#!/bin/bash

# 🚀 Laravel From Zero to Production - GitHub Publisher
# This script automates the process of publishing the project to GitHub

echo "🚀 Starting GitHub publication process..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Git is installed
if ! command -v git &> /dev/null; then
    print_error "Git is not installed. Please install Git first."
    exit 1
fi

# Check if we're in a Git repository
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    print_error "Not in a Git repository. Please run 'git init' first."
    exit 1
fi

# Check if we have commits
if ! git log --oneline -1 &> /dev/null; then
    print_error "No commits found. Please make an initial commit first."
    exit 1
fi

print_status "Checking Git status..."

# Check if there are uncommitted changes
if ! git diff-index --quiet HEAD --; then
    print_warning "You have uncommitted changes. Please commit them first."
    echo "Run: git add . && git commit -m 'your message'"
    exit 1
fi

print_success "Git repository is clean and ready."

# Set up remote repository
print_status "Setting up remote repository..."

# Check if remote already exists
if git remote get-url origin &> /dev/null; then
    print_warning "Remote 'origin' already exists."
    echo "Current remote URL: $(git remote get-url origin)"
    read -p "Do you want to update it? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        git remote remove origin
    else
        print_error "Please update the remote URL manually."
        exit 1
    fi
fi

# Add remote repository
GITHUB_REPO="https://github.com/michaelgermini/laravel-from-zero-to-production.git"
git remote add origin "$GITHUB_REPO"

print_success "Remote repository added: $GITHUB_REPO"

# Rename branch to main (if not already)
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "main" ]; then
    print_status "Renaming branch from '$CURRENT_BRANCH' to 'main'..."
    git branch -M main
    print_success "Branch renamed to 'main'"
fi

# Push to GitHub
print_status "Pushing to GitHub..."

if git push -u origin main; then
    print_success "Successfully pushed to GitHub!"
else
    print_error "Failed to push to GitHub. Please check your credentials and repository URL."
    exit 1
fi

# Display next steps
echo
print_success "🎉 Repository published successfully!"
echo
echo "📋 Next steps:"
echo "1. Visit: https://github.com/michaelgermini/laravel-from-zero-to-production"
echo "2. Configure repository settings:"
echo "   - Add description and topics"
echo "   - Enable Issues and Discussions"
echo "   - Set up GitHub Pages (optional)"
echo "3. Create your first release"
echo "4. Share with the community!"
echo
echo "📖 For detailed instructions, see: publish-to-github.md"
echo
print_success "Happy coding! 🚀"
