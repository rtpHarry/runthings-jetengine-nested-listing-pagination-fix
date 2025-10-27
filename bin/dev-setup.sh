#!/bin/bash

# Development setup script for Lodge Availability Plugin
# Run this after pulling changes or adding new PHP classes

set -e  # Exit on any error

PLUGIN_DIR="$(pwd)"
SCRIPT_DIR="$(dirname "$0")"

# Check if we're in the plugin root directory
if [[ ! -f "${PLUGIN_DIR}/runthings-lodge-availability.php" ]]; then
  echo "Error: This script should be run from the plugin root directory."
  echo "Current directory: ${PLUGIN_DIR}"
  exit 1
fi

echo "🔄 Setting up development environment..."

# Regenerate autoloader
echo "📦 Regenerating Composer autoloader..."
if ! composer dump-autoload; then
  echo "❌ Failed to regenerate autoloader"
  exit 1
fi

# Build Angular app if directory exists
if [[ -d "angular-app" ]]; then
  echo "🅰️  Building Angular application..."
  cd angular-app
  
  # Check if node_modules exists, if not run npm install
  if [[ ! -d "node_modules" ]]; then
    echo "📦 Installing npm dependencies..."
    npm install
  fi
  
  # Build the Angular app
  if ! npm run build; then
    echo "❌ Failed to build Angular application"
    exit 1
  fi
  
  cd ..
else
  echo "ℹ️  Angular app directory not found, skipping build"
fi

echo "✅ Development setup complete!"
echo ""
echo "Next steps:"
echo "- Configure API credentials in WordPress admin"
echo "- Test plugin functionality"
echo "- Run './bin/build-zip.sh' when ready to create release package"
