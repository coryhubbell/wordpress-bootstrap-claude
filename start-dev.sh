#!/bin/bash
# =============================================================================
# WordPress Bootstrap Claude - Development Startup Script
# =============================================================================
# Starts the complete development environment with Docker
# Supports: macOS, Linux, Windows (Git Bash/WSL)
# =============================================================================

set -e

# Colors for output (disabled if not a terminal)
if [ -t 1 ]; then
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    BLUE='\033[0;34m'
    NC='\033[0m'
else
    RED=''
    GREEN=''
    YELLOW=''
    BLUE=''
    NC=''
fi

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  WordPress Bootstrap Claude${NC}"
echo -e "${BLUE}  Development Environment${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# -----------------------------------------------------------------------------
# Detect Platform
# -----------------------------------------------------------------------------
detect_platform() {
    case "$(uname -s)" in
        Darwin*)    echo "macos" ;;
        Linux*)     echo "linux" ;;
        MINGW*|MSYS*|CYGWIN*)    echo "windows" ;;
        *)          echo "unknown" ;;
    esac
}

PLATFORM=$(detect_platform)
echo -e "${YELLOW}Platform:${NC} $PLATFORM"

# -----------------------------------------------------------------------------
# Check Docker
# -----------------------------------------------------------------------------
echo -e "${YELLOW}Checking Docker...${NC}"

if ! command -v docker &> /dev/null; then
    echo -e "${RED}ERROR: Docker is not installed!${NC}"
    echo "Please install Docker Desktop from https://docker.com/get-started"
    exit 1
fi

if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}ERROR: Docker is not running!${NC}"
    echo "Please start Docker Desktop and try again."
    exit 1
fi
echo -e "${GREEN}Docker is running${NC}"

# -----------------------------------------------------------------------------
# Setup .env File
# -----------------------------------------------------------------------------
if [ ! -f ".env" ]; then
    echo ""
    echo -e "${YELLOW}Creating .env file...${NC}"

    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo -e "${GREEN}Created .env from .env.example${NC}"

        # Check if keys are still placeholders
        if grep -q "put-your-unique-phrase-here" .env; then
            echo -e "${YELLOW}Generating security keys...${NC}"

            generate_key() {
                if command -v openssl &> /dev/null; then
                    openssl rand -base64 48 | tr -d '\n' | head -c 64
                elif [ -r /dev/urandom ]; then
                    head -c 48 /dev/urandom 2>/dev/null | base64 | tr -d '\n' | head -c 64
                else
                    date +%s%N 2>/dev/null | shasum -a 256 | head -c 64 || \
                    date +%s | md5sum | head -c 64
                fi
            }

            # Replace placeholder keys
            for KEY in AUTH_KEY SECURE_AUTH_KEY LOGGED_IN_KEY NONCE_KEY AUTH_SALT SECURE_AUTH_SALT LOGGED_IN_SALT NONCE_SALT; do
                NEW_VALUE=$(generate_key)
                if [[ "$PLATFORM" == "macos" ]]; then
                    sed -i '' "s|${KEY}='put-your-unique-phrase-here'|${KEY}='${NEW_VALUE}'|g" .env
                else
                    sed -i "s|${KEY}='put-your-unique-phrase-here'|${KEY}='${NEW_VALUE}'|g" .env
                fi
            done

            echo -e "${GREEN}Security keys generated${NC}"
        fi

        # Set Docker mode based on platform
        if [[ "$PLATFORM" == "linux" ]]; then
            if [[ "$PLATFORM" == "macos" ]]; then
                sed -i '' "s|VITE_DOCKER_MODE=false|VITE_DOCKER_MODE=true|g" .env
            else
                sed -i "s|VITE_DOCKER_MODE=false|VITE_DOCKER_MODE=true|g" .env
            fi
        fi
    else
        echo -e "${RED}ERROR: .env.example not found!${NC}"
        echo "Please run ./setup.sh first or ensure you have the complete repository."
        exit 1
    fi
else
    echo -e "${GREEN}.env file exists${NC}"
fi

# Load environment variables
set -a
source .env 2>/dev/null || true
set +a

# Use defaults if not set
WORDPRESS_PORT=${WORDPRESS_PORT:-8080}
PHPMYADMIN_PORT=${PHPMYADMIN_PORT:-8081}
VITE_PORT=${VITE_PORT:-3000}

# -----------------------------------------------------------------------------
# Start Docker Containers
# -----------------------------------------------------------------------------
echo ""
echo -e "${YELLOW}Starting Docker containers...${NC}"
docker-compose up -d

# Wait for WordPress
echo ""
echo -e "${YELLOW}Waiting for WordPress to be ready...${NC}"
WORDPRESS_URL="http://localhost:${WORDPRESS_PORT}"
MAX_WAIT=90
WAITED=0

until curl -s "$WORDPRESS_URL" > /dev/null 2>&1; do
    if [ $WAITED -ge $MAX_WAIT ]; then
        echo -e "${RED}ERROR: WordPress did not start within ${MAX_WAIT} seconds${NC}"
        echo "Check Docker logs: docker-compose logs wordpress"
        exit 1
    fi
    printf "  Waiting... (%ds)\r" $WAITED
    sleep 3
    WAITED=$((WAITED + 3))
done
echo ""
echo -e "${GREEN}WordPress is ready!${NC}"

# -----------------------------------------------------------------------------
# Check Node Modules
# -----------------------------------------------------------------------------
if [ -d "admin" ] && [ ! -d "admin/node_modules" ]; then
    echo ""
    echo -e "${YELLOW}Installing admin interface dependencies...${NC}"
    cd admin && npm install && cd ..
    echo -e "${GREEN}Admin dependencies installed${NC}"
fi

# -----------------------------------------------------------------------------
# Success Message
# -----------------------------------------------------------------------------
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Development Environment Ready!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}Services:${NC}"
echo "  WordPress:    http://localhost:${WORDPRESS_PORT}"
echo "  WP Admin:     http://localhost:${WORDPRESS_PORT}/wp-admin"
echo "  phpMyAdmin:   http://localhost:${PHPMYADMIN_PORT}"
echo "  REST API:     http://localhost:${WORDPRESS_PORT}/wp-json/wpbc/v2/"
echo ""
echo -e "${BLUE}Next Steps:${NC}"
echo "  1. Complete WordPress install: http://localhost:${WORDPRESS_PORT}"
echo "  2. Activate 'WordPress Bootstrap Claude' theme"
echo "  3. Start Visual Interface dev server:"
echo "     cd admin && npm run dev"
echo "  4. Access 'Visual Interface' in WordPress admin menu"
echo ""
echo -e "${BLUE}Useful Commands:${NC}"
echo "  make docker-down    # Stop containers"
echo "  make docker-logs    # View logs"
echo "  make test           # Run tests"
echo ""
echo -e "${GREEN}Happy coding!${NC}"
echo ""
