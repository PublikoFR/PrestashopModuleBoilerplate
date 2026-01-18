#!/bin/bash
set -e

# =============================================================================
# Configuration - À MODIFIER pour chaque nouveau module
# =============================================================================
PRESTASHOP_PATH="/home/riderfx3/webdev/projects/MDE Prestashop"  # Chemin vers PrestaShop local
DOCKER_CONTAINER="mde_prestashop"                                 # Nom du conteneur Docker
MODULE_NAME="publikomoduleboilerplate"                            # Nom technique du module (sans espaces)
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SOURCE_DIR="${SCRIPT_DIR}/${MODULE_NAME}"
TARGET_DIR="${PRESTASHOP_PATH}/modules/${MODULE_NAME}"
VERSION=$(grep "this->version" "${SOURCE_DIR}/${MODULE_NAME}.php" 2>/dev/null | head -1 | grep -oP "'[0-9]+\.[0-9]+\.[0-9]+'" | tr -d "'" || echo "1.0.0")

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
DIM='\033[2m'
NC='\033[0m'

# =============================================================================
# Fonctions utilitaires
# =============================================================================
success_msg() {
    echo -e "${GREEN}✓${NC} $1"
}

error_msg() {
    echo -e "${RED}✗ Erreur:${NC} $1"
    exit 1
}

info_msg() {
    echo -e "${BLUE}→${NC} $1"
}

check_prerequisites() {
    [[ ! -d "${SOURCE_DIR}" ]] && error_msg "Dossier source ${MODULE_NAME}/ non trouvé"
    [[ ! -d "${PRESTASHOP_PATH}" ]] && error_msg "PrestaShop non trouvé: ${PRESTASHOP_PATH}"
    docker ps --format '{{.Names}}' | grep -q "^${DOCKER_CONTAINER}$" || error_msg "Conteneur Docker '${DOCKER_CONTAINER}' non actif"
}

# =============================================================================
# Actions de base
# =============================================================================
sync_files() {
    info_msg "Synchronisation des fichiers..."
    mkdir -p "${TARGET_DIR}"
    cp -r "${SOURCE_DIR}/"* "${TARGET_DIR}/"
    success_msg "Fichiers copiés vers ${TARGET_DIR}"
}

delete_files() {
    info_msg "Suppression des fichiers du module..."
    if [[ -d "${TARGET_DIR}" ]]; then
        rm -rf "${TARGET_DIR:?}/"*
        success_msg "Fichiers supprimés"
    else
        info_msg "Dossier inexistant, rien à supprimer"
    fi
}

clear_cache() {
    info_msg "Vidage du cache..."
    if [[ -x "${PRESTASHOP_PATH}/rmcache.sh" ]]; then
        "${PRESTASHOP_PATH}/rmcache.sh" >/dev/null 2>&1
        success_msg "Cache vidé"
    else
        docker exec ${DOCKER_CONTAINER} rm -rf /var/www/html/var/cache/* 2>/dev/null || true
        success_msg "Cache vidé (via Docker)"
    fi
}

exec_module_action() {
    local action=$1
    docker exec ${DOCKER_CONTAINER} php -r "
        require_once '/var/www/html/config/config.inc.php';
        require_once '/var/www/html/init.php';
        \$module = Module::getInstanceByName('${MODULE_NAME}');
        if (!\$module) { echo 'Module non trouvé'; exit(1); }
        ${action}
    " 2>/dev/null
}

do_install() {
    info_msg "Installation du module..."
    exec_module_action "
        if (\$module->install()) {
            echo 'OK';
        } else {
            echo 'FAIL: ' . implode(', ', \$module->getErrors());
            exit(1);
        }
    " || error_msg "Installation échouée"
    success_msg "Module installé"
}

do_uninstall() {
    info_msg "Désinstallation du module..."
    exec_module_action "
        if (\$module->uninstall()) {
            echo 'OK';
        } else {
            echo 'FAIL';
            exit(1);
        }
    " 2>/dev/null || info_msg "Module déjà désinstallé ou non trouvé"
    success_msg "Module désinstallé"
}

# =============================================================================
# Actions composées
# =============================================================================
action_install_reinstall() {
    echo ""
    echo -e "${BOLD}Installer / Réinstaller${NC}"
    echo -e "${DIM}─────────────────────────${NC}"
    sync_files
    do_install
    clear_cache
    echo ""
    success_msg "Terminé !"
}

action_uninstall() {
    echo ""
    echo -e "${BOLD}Désinstaller${NC}"
    echo -e "${DIM}─────────────────────────${NC}"
    do_uninstall
    clear_cache
    echo ""
    success_msg "Terminé !"
}

action_uninstall_reinstall() {
    echo ""
    echo -e "${BOLD}Désinstaller puis Réinstaller${NC}"
    echo -e "${DIM}─────────────────────────${NC}"
    do_uninstall
    sync_files
    do_install
    clear_cache
    echo ""
    success_msg "Terminé !"
}

action_delete_reinstall() {
    echo ""
    echo -e "${BOLD}Supprimer puis Réinstaller${NC}"
    echo -e "${DIM}─────────────────────────${NC}"
    do_uninstall
    delete_files
    sync_files
    do_install
    clear_cache
    echo ""
    success_msg "Terminé !"
}

action_build_zip() {
    echo ""
    echo -e "${BOLD}Build ZIP${NC}"
    echo -e "${DIM}─────────────────────────${NC}"

    local zip_name="${MODULE_NAME}_v${VERSION}.zip"
    local temp_dir=$(mktemp -d)

    rm -f "${SCRIPT_DIR}/${zip_name}"

    info_msg "Copie des fichiers..."
    cp -r "${SOURCE_DIR}" "${temp_dir}/${MODULE_NAME}"

    info_msg "Nettoyage..."
    find "${temp_dir}/${MODULE_NAME}" -name ".git*" -exec rm -rf {} + 2>/dev/null || true
    find "${temp_dir}/${MODULE_NAME}" -name ".claude*" -exec rm -rf {} + 2>/dev/null || true
    find "${temp_dir}/${MODULE_NAME}" -name ".grepai*" -exec rm -rf {} + 2>/dev/null || true
    find "${temp_dir}/${MODULE_NAME}" -name "CLAUDE.md" -exec rm -f {} + 2>/dev/null || true
    find "${temp_dir}/${MODULE_NAME}" -name "TODO.md" -exec rm -f {} + 2>/dev/null || true
    find "${temp_dir}/${MODULE_NAME}" -name "*.zip" -exec rm -f {} + 2>/dev/null || true
    find "${temp_dir}/${MODULE_NAME}" -name ".DS_Store" -exec rm -f {} + 2>/dev/null || true
    find "${temp_dir}/${MODULE_NAME}" -name "*.swp" -exec rm -f {} + 2>/dev/null || true
    find "${temp_dir}/${MODULE_NAME}" -name "*~" -exec rm -f {} + 2>/dev/null || true
    rm -rf "${temp_dir}/${MODULE_NAME}/vendor" 2>/dev/null || true
    rm -rf "${temp_dir}/${MODULE_NAME}/node_modules" 2>/dev/null || true

    info_msg "Création de l'archive..."
    cd "${temp_dir}"
    zip -rq "${SCRIPT_DIR}/${zip_name}" "${MODULE_NAME}"
    cd "${SCRIPT_DIR}"

    rm -rf "${temp_dir}"

    local zip_size=$(du -h "${SCRIPT_DIR}/${zip_name}" | cut -f1)
    echo ""
    success_msg "Terminé !"
    echo -e "  → ${CYAN}${zip_name}${NC} (${zip_size})"
}

# =============================================================================
# Menu interactif
# =============================================================================
MENU_OPTIONS=(
    "Installer / Réinstaller"
    "Désinstaller"
    "Désinstaller puis Réinstaller"
    "Supprimer puis Réinstaller"
    "Build ZIP"
    "Quitter"
)

print_menu() {
    local selected=$1

    echo ""
    echo -e "${CYAN}╔══════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║${NC}  ${BOLD}${MODULE_NAME}${NC} v${YELLOW}${VERSION}${NC}"
    echo -e "${CYAN}╚══════════════════════════════════════════════╝${NC}"
    echo ""

    for i in "${!MENU_OPTIONS[@]}"; do
        if [[ $i -eq $selected ]]; then
            echo -e "  ${GREEN}▸${NC} ${BOLD}${MENU_OPTIONS[$i]}${NC}"
        else
            echo -e "    ${DIM}${MENU_OPTIONS[$i]}${NC}"
        fi
    done

    echo ""
    echo -e "${DIM}  ↑↓ Naviguer  ⏎ Valider  q Quitter${NC}"
}

run_menu() {
    local selected=0
    local key

    # Cacher le curseur
    tput civis 2>/dev/null || true

    # Restaurer le curseur à la sortie
    trap 'tput cnorm 2>/dev/null || true; exit' EXIT INT TERM

    while true; do
        # Effacer l'écran et afficher le menu
        clear
        print_menu $selected

        # Lire une touche
        IFS= read -rsn1 key

        case "$key" in
            $'\x1b')  # Séquence d'échappement (flèches)
                read -rsn2 -t 0.1 key
                case "$key" in
                    '[A')  # Flèche haut
                        ((selected > 0)) && ((selected--))
                        ;;
                    '[B')  # Flèche bas
                        ((selected < ${#MENU_OPTIONS[@]} - 1)) && ((selected++))
                        ;;
                esac
                ;;
            '')  # Entrée
                tput cnorm 2>/dev/null || true
                clear

                case $selected in
                    0) action_install_reinstall ;;
                    1) action_uninstall ;;
                    2) action_uninstall_reinstall ;;
                    3) action_delete_reinstall ;;
                    4) action_build_zip ;;
                    5) echo -e "${DIM}Au revoir !${NC}"; exit 0 ;;
                esac

                echo ""
                echo -e "${DIM}Appuyez sur une touche pour continuer...${NC}"
                read -rsn1
                tput civis 2>/dev/null || true
                ;;
            'q'|'Q')  # Quitter
                tput cnorm 2>/dev/null || true
                clear
                echo -e "${DIM}Au revoir !${NC}"
                exit 0
                ;;
            'k')  # vim: haut
                ((selected > 0)) && ((selected--))
                ;;
            'j')  # vim: bas
                ((selected < ${#MENU_OPTIONS[@]} - 1)) && ((selected++))
                ;;
        esac
    done
}

show_help() {
    echo -e "${CYAN}Usage:${NC} ./install.sh [option]"
    echo ""
    echo -e "Sans option : lance le menu interactif"
    echo ""
    echo -e "Options CLI :"
    echo -e "  ${GREEN}--install${NC}      Installer / Réinstaller"
    echo -e "  ${GREEN}--uninstall${NC}    Désinstaller"
    echo -e "  ${GREEN}--reinstall${NC}    Désinstaller puis Réinstaller"
    echo -e "  ${GREEN}--reset${NC}        Supprimer puis Réinstaller"
    echo -e "  ${GREEN}--zip${NC}          Build le zip"
    echo -e "  ${GREEN}--help${NC}         Affiche cette aide"
    echo ""
}

# =============================================================================
# Main
# =============================================================================
cd "${SCRIPT_DIR}"
check_prerequisites

case "${1:-}" in
    --install)     action_install_reinstall ;;
    --uninstall)   action_uninstall ;;
    --reinstall)   action_uninstall_reinstall ;;
    --reset)       action_delete_reinstall ;;
    --zip)         action_build_zip ;;
    --help|-h)     show_help ;;
    "")            run_menu ;;
    *)             error_msg "Option inconnue: $1. Utilisez --help pour l'aide." ;;
esac
