#!/bin/bash
# =============================================================================
# WordPress Bootstrap Claude - Setup Script
# =============================================================================
# One-command setup for new contributors
# Supports: macOS, Linux, Windows (Git Bash/WSL)
# =============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  WordPress Bootstrap Claude Setup${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# -----------------------------------------------------------------------------
# Check PHP Version
# -----------------------------------------------------------------------------
echo -e "${YELLOW}Checking PHP version...${NC}"

if ! command -v php &> /dev/null; then
    echo -e "${RED}ERROR: PHP is not installed!${NC}"
    echo "Please install PHP 7.4 or higher."
    echo ""
    echo "Installation:"
    echo "  macOS:   brew install php"
    echo "  Ubuntu:  sudo apt install php php-cli php-mbstring php-xml php-curl"
    echo "  Windows: Download from https://windows.php.net/download/"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
PHP_MAJOR=$(php -r "echo PHP_MAJOR_VERSION;")
PHP_MINOR=$(php -r "echo PHP_MINOR_VERSION;")

if [ "$PHP_MAJOR" -lt 7 ] || ([ "$PHP_MAJOR" -eq 7 ] && [ "$PHP_MINOR" -lt 4 ]); then
    echo -e "${RED}ERROR: PHP 7.4+ required, found PHP $PHP_VERSION${NC}"
    exit 1
fi
echo -e "${GREEN}PHP $PHP_VERSION detected${NC}"

# -----------------------------------------------------------------------------
# Check Composer
# -----------------------------------------------------------------------------
echo -e "${YELLOW}Checking Composer...${NC}"

if ! command -v composer &> /dev/null; then
    echo -e "${RED}ERROR: Composer is not installed!${NC}"
    echo "Please install Composer 2.0 or higher."
    echo ""
    echo "Installation:"
    echo "  macOS:   brew install composer"
    echo "  Linux:   https://getcomposer.org/download/"
    echo "  Windows: https://getcomposer.org/doc/00-intro.md#installation-windows"
    exit 1
fi
echo -e "${GREEN}Composer detected${NC}"

# -----------------------------------------------------------------------------
# Create .env from .env.example
# -----------------------------------------------------------------------------
echo -e "${YELLOW}Setting up environment...${NC}"

if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo -e "${GREEN}Created .env from .env.example${NC}"

        # Generate security keys
        echo -e "${YELLOW}Generating security keys...${NC}"

        generate_key() {
            if command -v openssl &> /dev/null; then
                openssl rand -base64 48 | tr -d '\n' | head -c 64
            elif [ -r /dev/urandom ]; then
                head -c 48 /dev/urandom 2>/dev/null | base64 | tr -d '\n' | head -c 64
            else
                # Fallback
                date +%s%N | shasum -a 256 2>/dev/null | head -c 64 || \
                date +%s | md5sum | head -c 64
            fi
        }

        # Detect platform for sed compatibility
        if [[ "$OSTYPE" == "darwin"* ]]; then
            SED_INPLACE="sed -i ''"
        else
            SED_INPLACE="sed -i"
        fi

        # Replace placeholder keys
        for KEY in AUTH_KEY SECURE_AUTH_KEY LOGGED_IN_KEY NONCE_KEY AUTH_SALT SECURE_AUTH_SALT LOGGED_IN_SALT NONCE_SALT; do
            NEW_VALUE=$(generate_key)
            if [[ "$OSTYPE" == "darwin"* ]]; then
                sed -i '' "s|${KEY}='put-your-unique-phrase-here'|${KEY}='${NEW_VALUE}'|g" .env
            else
                sed -i "s|${KEY}='put-your-unique-phrase-here'|${KEY}='${NEW_VALUE}'|g" .env
            fi
        done

        echo -e "${GREEN}Security keys generated${NC}"
    else
        echo -e "${RED}ERROR: .env.example not found!${NC}"
        echo "Please ensure you have the complete repository."
        exit 1
    fi
else
    echo -e "${GREEN}.env already exists${NC}"
fi

# -----------------------------------------------------------------------------
# Install Composer Dependencies
# -----------------------------------------------------------------------------
echo -e "${YELLOW}Installing PHP dependencies...${NC}"

composer install --prefer-dist --no-progress --quiet
echo -e "${GREEN}PHP dependencies installed${NC}"

# -----------------------------------------------------------------------------
# Make CLI Executable
# -----------------------------------------------------------------------------
echo -e "${YELLOW}Setting up CLI tool...${NC}"

if [ -f "wpbc" ]; then
    chmod +x wpbc
    echo -e "${GREEN}CLI tool ready${NC}"
fi

# -----------------------------------------------------------------------------
# Create coverage directory
# -----------------------------------------------------------------------------
mkdir -p coverage

# -----------------------------------------------------------------------------
# Verify Installation
# -----------------------------------------------------------------------------
echo ""
echo -e "${YELLOW}Verifying installation...${NC}"

# Check if autoload exists
if [ ! -f "vendor/autoload.php" ]; then
    echo -e "${RED}ERROR: Composer autoload not found${NC}"
    exit 1
fi

# Quick PHP syntax check on key files
php -l functions.php > /dev/null 2>&1 || {
    echo -e "${RED}ERROR: PHP syntax error in functions.php${NC}"
    exit 1
}

echo -e "${GREEN}Installation verified${NC}"

# -----------------------------------------------------------------------------
# Success Message
# -----------------------------------------------------------------------------
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Setup Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "Next steps:"
echo ""
echo -e "  ${BLUE}1. Run tests:${NC}"
echo -e "     make test"
echo -e "     # or: composer test"
echo ""
echo -e "  ${BLUE}2. Try a translation:${NC}"
echo -e "     ./wpbc translate bootstrap divi examples/hero-bootstrap.html"
echo ""
echo -e "  ${BLUE}3. Start Docker (optional):${NC}"
echo -e "     make docker-up"
echo -e "     # WordPress: http://localhost:8080"
echo ""
echo -e "  ${BLUE}4. Read the docs:${NC}"
echo -e "     cat CONTRIBUTING.md"
echo ""
echo -e "${GREEN}Happy coding!${NC}"
echo ""
