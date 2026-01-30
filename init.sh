#!/bin/bash
set -e

# =============================================================================
# Publiko Module Initializer
# Transforms the boilerplate into a new module with the given name
# =============================================================================

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
DIM='\033[2m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Boilerplate original names (to be replaced)
OLD_HUMAN="Publiko Module Boilerplate"
OLD_TECHNICAL="publikomoduleboilerplate"
OLD_CLASS="PublikoModuleBoilerplate"
OLD_SMARTY="publiko_module_boilerplate"
OLD_TWIG="Publikomoduleboilerplate"
OLD_COMPOSER="publiko/moduleboilerplate"

# =============================================================================
# Utility functions
# =============================================================================
success_msg() {
    echo -e "${GREEN}✓${NC} $1"
}

error_msg() {
    echo -e "${RED}✗ Error:${NC} $1"
    exit 1
}

info_msg() {
    echo -e "${BLUE}→${NC} $1"
}

# =============================================================================
# Name generation functions
# =============================================================================

# "Publiko Last Order" -> "publikolastorder"
to_technical() {
    echo "$1" | tr '[:upper:]' '[:lower:]' | tr -d ' '
}

# "Publiko Last Order" -> "PublikoLastOrder"
to_class() {
    echo "$1" | sed 's/ //g'
}

# "Publiko Last Order" -> "publiko_last_order"
to_smarty() {
    echo "$1" | tr '[:upper:]' '[:lower:]' | sed 's/ /_/g'
}

# "Publiko Last Order" -> "Publikolastorder"
to_twig() {
    local result=$(echo "$1" | tr -d ' ')
    echo "${result^}"
}

# "Publiko Last Order" -> "publiko/lastorder"
to_composer() {
    local words=($1)
    local first=$(echo "${words[0]}" | tr '[:upper:]' '[:lower:]')
    local rest=""
    for ((i=1; i<${#words[@]}; i++)); do
        rest+=$(echo "${words[$i]}" | tr '[:upper:]' '[:lower:]')
    done
    echo "${first}/${rest}"
}

# =============================================================================
# Main
# =============================================================================

clear
echo ""
echo -e "${CYAN}╔══════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║${NC}  ${BOLD}Publiko Module Initializer${NC}"
echo -e "${CYAN}║${NC}  ${DIM}Transform boilerplate into your new module${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════════╝${NC}"
echo ""

# Check if boilerplate folder exists
if [[ ! -d "${SCRIPT_DIR}/${OLD_TECHNICAL}" ]]; then
    error_msg "Boilerplate folder '${OLD_TECHNICAL}' not found. Already initialized?"
fi

# Ask for module name
echo -e "${BOLD}Enter your module name (human readable):${NC}"
echo -e "${DIM}Example: \"Publiko Last Order\" or \"Siret Verif\"${NC}"
echo ""
read -p "> " MODULE_HUMAN

# Validate input
if [[ -z "$MODULE_HUMAN" ]]; then
    error_msg "Module name cannot be empty"
fi

# Check format (should have at least one uppercase letter to ensure proper case)
if [[ ! "$MODULE_HUMAN" =~ [A-Z] ]]; then
    echo ""
    echo -e "${YELLOW}Warning:${NC} Name should use Title Case (e.g., \"My Module\")"
    read -p "Continue anyway? [y/N] " confirm
    if [[ "$confirm" != "y" && "$confirm" != "Y" ]]; then
        echo "Aborted."
        exit 0
    fi
fi

# Generate all variants
NEW_TECHNICAL=$(to_technical "$MODULE_HUMAN")
NEW_CLASS=$(to_class "$MODULE_HUMAN")
NEW_SMARTY=$(to_smarty "$MODULE_HUMAN")
NEW_TWIG=$(to_twig "$MODULE_HUMAN")
NEW_COMPOSER=$(to_composer "$MODULE_HUMAN")

# Display preview
echo ""
echo -e "${CYAN}╔══════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║${NC}  ${BOLD}Preview of transformations${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════════╝${NC}"
echo ""
printf "  %-25s ${DIM}→${NC} %s\n" "Human name:" "${BOLD}${MODULE_HUMAN}${NC}"
printf "  %-25s ${DIM}→${NC} %s\n" "Technical (folder/file):" "${GREEN}${NEW_TECHNICAL}${NC}"
printf "  %-25s ${DIM}→${NC} %s\n" "Class/Namespace:" "${GREEN}${NEW_CLASS}${NC}"
printf "  %-25s ${DIM}→${NC} %s\n" "Smarty (mod='...'):" "${GREEN}${NEW_SMARTY}${NC}"
printf "  %-25s ${DIM}→${NC} %s\n" "Twig domain:" "${GREEN}${NEW_TWIG}${NC}"
printf "  %-25s ${DIM}→${NC} %s\n" "Composer package:" "${GREEN}${NEW_COMPOSER}${NC}"
echo ""

# Check if target folder already exists
if [[ -d "${SCRIPT_DIR}/${NEW_TECHNICAL}" ]]; then
    error_msg "Folder '${NEW_TECHNICAL}' already exists!"
fi

# Confirm
echo -e "${YELLOW}This will rename the boilerplate folder and update all files.${NC}"
read -p "Proceed? [y/N] " confirm
if [[ "$confirm" != "y" && "$confirm" != "Y" ]]; then
    echo "Aborted."
    exit 0
fi

echo ""

# Step 1: Rename folder
info_msg "Renaming folder..."
mv "${SCRIPT_DIR}/${OLD_TECHNICAL}" "${SCRIPT_DIR}/${NEW_TECHNICAL}"
success_msg "Folder renamed: ${OLD_TECHNICAL} → ${NEW_TECHNICAL}"

# Step 2: Rename main PHP file
info_msg "Renaming main PHP file..."
if [[ -f "${SCRIPT_DIR}/${NEW_TECHNICAL}/${OLD_TECHNICAL}.php" ]]; then
    mv "${SCRIPT_DIR}/${NEW_TECHNICAL}/${OLD_TECHNICAL}.php" "${SCRIPT_DIR}/${NEW_TECHNICAL}/${NEW_TECHNICAL}.php"
    success_msg "Main file renamed: ${OLD_TECHNICAL}.php → ${NEW_TECHNICAL}.php"
fi

# Step 3: Replace in all files
info_msg "Replacing occurrences in files..."

# Count replacements
count=0

# Find all text files and replace (excluding .git, images, etc.)
while IFS= read -r -d '' file; do
    # Skip binary files
    if file "$file" | grep -q "text"; then
        changed=false

        # Replace all variants (order matters: longest first to avoid partial replacements)
        if grep -q "$OLD_CLASS" "$file" 2>/dev/null; then
            sed -i "s|$OLD_CLASS|$NEW_CLASS|g" "$file"
            changed=true
        fi
        if grep -q "$OLD_COMPOSER" "$file" 2>/dev/null; then
            sed -i "s|$OLD_COMPOSER|$NEW_COMPOSER|g" "$file"
            changed=true
        fi
        if grep -q "$OLD_SMARTY" "$file" 2>/dev/null; then
            sed -i "s|$OLD_SMARTY|$NEW_SMARTY|g" "$file"
            changed=true
        fi
        if grep -q "$OLD_TWIG" "$file" 2>/dev/null; then
            sed -i "s|$OLD_TWIG|$NEW_TWIG|g" "$file"
            changed=true
        fi
        if grep -q "$OLD_TECHNICAL" "$file" 2>/dev/null; then
            sed -i "s|$OLD_TECHNICAL|$NEW_TECHNICAL|g" "$file"
            changed=true
        fi
        if grep -q "$OLD_HUMAN" "$file" 2>/dev/null; then
            sed -i "s|$OLD_HUMAN|$MODULE_HUMAN|g" "$file"
            changed=true
        fi

        if [[ "$changed" == true ]]; then
            ((count++))
        fi
    fi
done < <(find "${SCRIPT_DIR}/${NEW_TECHNICAL}" -type f -print0 2>/dev/null)

success_msg "Updated ${count} files"

# Step 4: Update .env.install if exists
if [[ -f "${SCRIPT_DIR}/.env.install" ]]; then
    info_msg "Updating .env.install..."
    sed -i "s|MODULE_NAME=\"${OLD_TECHNICAL}\"|MODULE_NAME=\"${NEW_TECHNICAL}\"|g" "${SCRIPT_DIR}/.env.install"
    sed -i "s|NAME=\"${OLD_TECHNICAL}\"|NAME=\"${NEW_TECHNICAL}\"|g" "${SCRIPT_DIR}/.env.install"
    success_msg ".env.install updated"
fi

# Step 5: Update .env.install.example if exists
if [[ -f "${SCRIPT_DIR}/.env.install.example" ]]; then
    info_msg "Updating .env.install.example..."
    sed -i "s|${OLD_TECHNICAL}|${NEW_TECHNICAL}|g" "${SCRIPT_DIR}/.env.install.example"
    success_msg ".env.install.example updated"
fi

# Done
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}  ${BOLD}Initialization complete!${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  Your module is ready: ${BOLD}${NEW_TECHNICAL}/${NC}"
echo ""
echo -e "  ${DIM}Next steps:${NC}"
echo -e "  1. Review the generated files"
echo -e "  2. Run ${CYAN}./install.sh${NC} to install the module"
echo -e "  3. Start developing!"
echo ""
