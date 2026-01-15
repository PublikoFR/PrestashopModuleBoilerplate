#!/bin/bash
#
# Build script for PrestaShop Publiko Module Boilerplate
# Generates a .zip file ready for installation
#
# INSTRUCTIONS:
# 1. Rename MODULE_NAME according to your module
# 2. Adapt copied files if necessary
#

set -e

# Colors for display
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MODULE_NAME="publikomoduleboilerplate"
BUILD_DIR="${SCRIPT_DIR}/build"
ZIP_NAME="${MODULE_NAME}.zip"

echo -e "${YELLOW}=== Building module ${MODULE_NAME} ===${NC}"
echo ""

# Clean build directory
if [ -d "${BUILD_DIR}" ]; then
    echo -e "Cleaning build directory..."
    rm -rf "${BUILD_DIR}"
fi

# Create build directory
mkdir -p "${BUILD_DIR}/${MODULE_NAME}"

echo -e "Copying module files..."

# Copy main files
cp "${SCRIPT_DIR}/${MODULE_NAME}.php" "${BUILD_DIR}/${MODULE_NAME}/"
cp "${SCRIPT_DIR}/index.php" "${BUILD_DIR}/${MODULE_NAME}/"
cp "${SCRIPT_DIR}/autoload.php" "${BUILD_DIR}/${MODULE_NAME}/"
cp "${SCRIPT_DIR}/config.xml" "${BUILD_DIR}/${MODULE_NAME}/"
cp "${SCRIPT_DIR}/composer.json" "${BUILD_DIR}/${MODULE_NAME}/"
cp "${SCRIPT_DIR}/logo.png" "${BUILD_DIR}/${MODULE_NAME}/" 2>/dev/null || true

# Copy directories
cp -r "${SCRIPT_DIR}/classes" "${BUILD_DIR}/${MODULE_NAME}/"
cp -r "${SCRIPT_DIR}/config" "${BUILD_DIR}/${MODULE_NAME}/"
cp -r "${SCRIPT_DIR}/controllers" "${BUILD_DIR}/${MODULE_NAME}/"
cp -r "${SCRIPT_DIR}/sql" "${BUILD_DIR}/${MODULE_NAME}/"
cp -r "${SCRIPT_DIR}/views" "${BUILD_DIR}/${MODULE_NAME}/"

# Copy src/ only if it exists and contains files
if [ -d "${SCRIPT_DIR}/src" ] && [ "$(ls -A "${SCRIPT_DIR}/src" 2>/dev/null | grep -v index.php)" ]; then
    cp -r "${SCRIPT_DIR}/src" "${BUILD_DIR}/${MODULE_NAME}/"
fi

# Remove unnecessary files
echo -e "Cleaning unnecessary files..."
find "${BUILD_DIR}/${MODULE_NAME}" -name ".git*" -exec rm -rf {} + 2>/dev/null || true
find "${BUILD_DIR}/${MODULE_NAME}" -name ".DS_Store" -exec rm -f {} + 2>/dev/null || true
find "${BUILD_DIR}/${MODULE_NAME}" -name "Thumbs.db" -exec rm -f {} + 2>/dev/null || true
find "${BUILD_DIR}/${MODULE_NAME}" -name "*.log" -exec rm -f {} + 2>/dev/null || true
find "${BUILD_DIR}/${MODULE_NAME}" -name ".grepai*" -exec rm -rf {} + 2>/dev/null || true
find "${BUILD_DIR}/${MODULE_NAME}" -name ".claude*" -exec rm -rf {} + 2>/dev/null || true

# Remove existing zip
if [ -f "${SCRIPT_DIR}/${ZIP_NAME}" ]; then
    rm -f "${SCRIPT_DIR}/${ZIP_NAME}"
fi

# Create zip
echo -e "Creating archive ${ZIP_NAME}..."
cd "${BUILD_DIR}"
zip -r "${SCRIPT_DIR}/${ZIP_NAME}" "${MODULE_NAME}" -q

# Cleanup
rm -rf "${BUILD_DIR}"

# Result
ZIP_SIZE=$(du -h "${SCRIPT_DIR}/${ZIP_NAME}" | cut -f1)
BUILD_DATE=$(date '+%Y-%m-%d %H:%M:%S')
echo ""
echo -e "${GREEN}=== Build completed ===${NC}"
echo -e "Build date: ${YELLOW}${BUILD_DATE}${NC}"
echo -e "Archive created: ${YELLOW}${ZIP_NAME}${NC} (${ZIP_SIZE})"
echo ""
echo -e "To install the module:"
echo -e "  1. Go to PrestaShop back-office"
echo -e "  2. Menu Modules > Module Manager"
echo -e "  3. Click 'Upload a module'"
echo -e "  4. Drag and drop the ${ZIP_NAME} file"
