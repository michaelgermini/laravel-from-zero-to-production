#!/bin/bash

# Laravel Book Website Deployment Script
# This script deploys the website to various hosting platforms

set -e  # Exit on any error

# Configuration
WEBSITE_DIR="."
BUILD_DIR="dist"
DEPLOY_BRANCH="main"
REMOTE_NAME="origin"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if we're in the right directory
check_directory() {
    if [ ! -f "index.html" ]; then
        log_error "index.html not found. Please run this script from the website directory."
        exit 1
    fi
}

# Build the website
build_website() {
    log_info "Building website..."
    
    # Create build directory
    rm -rf $BUILD_DIR
    mkdir -p $BUILD_DIR
    
    # Copy all files
    cp -r * $BUILD_DIR/
    
    # Minify CSS and JS (if tools are available)
    if command -v uglifyjs &> /dev/null; then
        log_info "Minifying JavaScript..."
        find $BUILD_DIR -name "*.js" -exec uglifyjs {} -o {} \;
    fi
    
    if command -v cleancss &> /dev/null; then
        log_info "Minifying CSS..."
        find $BUILD_DIR -name "*.css" -exec cleancss {} -o {} \;
    fi
    
    log_success "Website built successfully!"
}

# Deploy to GitHub Pages
deploy_github_pages() {
    log_info "Deploying to GitHub Pages..."
    
    # Check if git is initialized
    if [ ! -d ".git" ]; then
        log_error "Git repository not found. Please initialize git first."
        exit 1
    fi
    
    # Create gh-pages branch
    git checkout -b gh-pages 2>/dev/null || git checkout gh-pages
    
    # Copy built files
    cp -r $BUILD_DIR/* .
    
    # Add and commit
    git add .
    git commit -m "Deploy website $(date)" || true
    
    # Push to GitHub
    git push origin gh-pages
    
    # Return to original branch
    git checkout $DEPLOY_BRANCH
    
    log_success "Deployed to GitHub Pages! Your site will be available at: https://yourusername.github.io/yourrepo"
}

# Deploy to Netlify
deploy_netlify() {
    log_info "Deploying to Netlify..."
    
    if ! command -v netlify &> /dev/null; then
        log_error "Netlify CLI not found. Please install it first: npm install -g netlify-cli"
        exit 1
    fi
    
    # Deploy to Netlify
    netlify deploy --dir=$BUILD_DIR --prod
    
    log_success "Deployed to Netlify!"
}

# Deploy to Vercel
deploy_vercel() {
    log_info "Deploying to Vercel..."
    
    if ! command -v vercel &> /dev/null; then
        log_error "Vercel CLI not found. Please install it first: npm install -g vercel"
        exit 1
    fi
    
    # Deploy to Vercel
    vercel --prod $BUILD_DIR
    
    log_success "Deployed to Vercel!"
}

# Deploy to AWS S3
deploy_s3() {
    log_info "Deploying to AWS S3..."
    
    if ! command -v aws &> /dev/null; then
        log_error "AWS CLI not found. Please install it first."
        exit 1
    fi
    
    # Check if bucket name is provided
    if [ -z "$S3_BUCKET" ]; then
        log_error "S3_BUCKET environment variable not set. Please set it to your bucket name."
        exit 1
    fi
    
    # Sync files to S3
    aws s3 sync $BUILD_DIR s3://$S3_BUCKET --delete
    
    # Set cache headers
    aws s3 cp s3://$S3_BUCKET s3://$S3_BUCKET --recursive --metadata-directive REPLACE --cache-control max-age=31536000,public
    
    log_success "Deployed to AWS S3!"
}

# Deploy to FTP
deploy_ftp() {
    log_info "Deploying via FTP..."
    
    if [ -z "$FTP_HOST" ] || [ -z "$FTP_USER" ] || [ -z "$FTP_PASS" ]; then
        log_error "FTP credentials not set. Please set FTP_HOST, FTP_USER, and FTP_PASS environment variables."
        exit 1
    fi
    
    # Use lftp for FTP deployment
    if command -v lftp &> /dev/null; then
        lftp -c "set ssl:verify-certificate no; open -u $FTP_USER,$FTP_PASS $FTP_HOST; mirror -R $BUILD_DIR /"
    else
        log_error "lftp not found. Please install it for FTP deployment."
        exit 1
    fi
    
    log_success "Deployed via FTP!"
}

# Create a simple server for testing
test_local() {
    log_info "Starting local server for testing..."
    
    cd $BUILD_DIR
    
    # Try different methods to start a local server
    if command -v python3 &> /dev/null; then
        python3 -m http.server 8000
    elif command -v python &> /dev/null; then
        python -m SimpleHTTPServer 8000
    elif command -v php &> /dev/null; then
        php -S localhost:8000
    elif command -v node &> /dev/null; then
        npx serve -s . -l 8000
    else
        log_error "No suitable server found. Please install Python, PHP, or Node.js."
        exit 1
    fi
}

# Show usage
show_usage() {
    echo "Usage: $0 [OPTION]"
    echo ""
    echo "Options:"
    echo "  build           Build the website"
    echo "  github          Deploy to GitHub Pages"
    echo "  netlify         Deploy to Netlify"
    echo "  vercel          Deploy to Vercel"
    echo "  s3              Deploy to AWS S3"
    echo "  ftp             Deploy via FTP"
    echo "  test            Start local server for testing"
    echo "  all             Build and deploy to all platforms"
    echo ""
    echo "Environment variables:"
    echo "  S3_BUCKET       AWS S3 bucket name"
    echo "  FTP_HOST        FTP host"
    echo "  FTP_USER        FTP username"
    echo "  FTP_PASS        FTP password"
    echo ""
    echo "Examples:"
    echo "  $0 build"
    echo "  $0 github"
    echo "  S3_BUCKET=my-bucket $0 s3"
}

# Main script
main() {
    check_directory
    
    case "${1:-}" in
        "build")
            build_website
            ;;
        "github")
            build_website
            deploy_github_pages
            ;;
        "netlify")
            build_website
            deploy_netlify
            ;;
        "vercel")
            build_website
            deploy_vercel
            ;;
        "s3")
            build_website
            deploy_s3
            ;;
        "ftp")
            build_website
            deploy_ftp
            ;;
        "test")
            build_website
            test_local
            ;;
        "all")
            build_website
            deploy_github_pages
            deploy_netlify
            deploy_vercel
            ;;
        *)
            show_usage
            exit 1
            ;;
    esac
}

# Run main function
main "$@"
