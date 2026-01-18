#!/bin/bash
set -e

# =============================================================================
# Script info
# =============================================================================
SCRIPT_NAME="Publiko Module Installer"
SCRIPT_VERSION="1.2.0"

# URL du repo pour auto-update (raw GitHub)
UPDATE_REPO_URL="https://raw.githubusercontent.com/publiko/prestashop-module-installer/main"

# =============================================================================
# Configuration - Chargement depuis .env.install
# =============================================================================
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${SCRIPT_DIR}/.env.install"

if [[ -f "${ENV_FILE}" ]]; then
    source "${ENV_FILE}"
else
    echo -e "\033[0;31m✗ Erreur:\033[0m Fichier .env.install non trouvé"
    echo -e "  Copiez .env.install.example vers .env.install et configurez-le"
    exit 1
fi

# Validation des variables requises
[[ -z "${PRESTASHOP_PATH:-}" ]] && echo -e "\033[0;31m✗ Erreur:\033[0m PRESTASHOP_PATH non défini dans .env.install" && exit 1
[[ -z "${DOCKER_CONTAINER:-}" ]] && echo -e "\033[0;31m✗ Erreur:\033[0m DOCKER_CONTAINER non défini dans .env.install" && exit 1
[[ -z "${MODULE_NAME:-}" ]] && echo -e "\033[0;31m✗ Erreur:\033[0m MODULE_NAME non défini dans .env.install" && exit 1
# =============================================================================

SOURCE_DIR="${SCRIPT_DIR}/${MODULE_NAME}"
TARGET_DIR="${PRESTASHOP_PATH}/modules/${MODULE_NAME}"
BACKUP_DIR="${SCRIPT_DIR}/.backups"
MAX_BACKUPS=5
MODULE_VERSION=$(grep "this->version" "${SOURCE_DIR}/${MODULE_NAME}.php" 2>/dev/null | head -1 | grep -oP "'[0-9]+\.[0-9]+\.[0-9]+'" | tr -d "'" || echo "1.0.0")

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
    return 1
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
# Auto-update
# =============================================================================
check_for_update() {
    local remote_version
    local update_available=false

    info_msg "Vérification des mises à jour..."

    # Récupérer la version distante
    remote_version=$(curl -s --connect-timeout 5 "${UPDATE_REPO_URL}/VERSION" 2>/dev/null || echo "")

    if [[ -z "$remote_version" ]]; then
        error_msg "Impossible de vérifier les mises à jour (pas de connexion ?)"
        return 1
    fi

    # Nettoyer la version (supprimer espaces/retours ligne)
    remote_version=$(echo "$remote_version" | tr -d '[:space:]')

    if [[ "$remote_version" != "$SCRIPT_VERSION" ]]; then
        # Comparer les versions (semver simple)
        if [[ "$(printf '%s\n' "$SCRIPT_VERSION" "$remote_version" | sort -V | tail -n1)" == "$remote_version" ]]; then
            update_available=true
        fi
    fi

    if [[ "$update_available" == true ]]; then
        echo ""
        echo -e "${YELLOW}╔══════════════════════════════════════════════╗${NC}"
        echo -e "${YELLOW}║${NC}  ${BOLD}Nouvelle version disponible !${NC}"
        echo -e "${YELLOW}║${NC}  Actuelle: ${RED}${SCRIPT_VERSION}${NC} → Nouvelle: ${GREEN}${remote_version}${NC}"
        echo -e "${YELLOW}╚══════════════════════════════════════════════╝${NC}"
        echo ""
        echo -e "  Mettre à jour maintenant ? [o/N] "
        read -rsn1 answer
        echo ""

        if [[ "$answer" == "o" || "$answer" == "O" || "$answer" == "y" || "$answer" == "Y" ]]; then
            do_update
        else
            info_msg "Mise à jour ignorée"
        fi
    else
        success_msg "Vous utilisez la dernière version (${SCRIPT_VERSION})"
    fi
}

do_update() {
    local temp_script
    temp_script=$(mktemp)

    info_msg "Téléchargement de la nouvelle version..."

    if curl -s --connect-timeout 10 "${UPDATE_REPO_URL}/install.sh" -o "$temp_script" 2>/dev/null; then
        # Vérifier que le fichier téléchargé est valide (commence par #!/bin/bash)
        if head -1 "$temp_script" | grep -q "^#!/bin/bash"; then
            # Sauvegarder l'ancienne version
            cp "${SCRIPT_DIR}/install.sh" "${SCRIPT_DIR}/install.sh.bak"

            # Remplacer le script
            mv "$temp_script" "${SCRIPT_DIR}/install.sh"
            chmod +x "${SCRIPT_DIR}/install.sh"

            success_msg "Mise à jour effectuée !"
            echo -e "${DIM}  Ancien script sauvegardé: install.sh.bak${NC}"
            echo ""
            echo -e "${YELLOW}Relancez le script pour utiliser la nouvelle version.${NC}"
            exit 0
        else
            rm -f "$temp_script"
            error_msg "Fichier téléchargé invalide"
            return 1
        fi
    else
        rm -f "$temp_script"
        error_msg "Échec du téléchargement"
        return 1
    fi
}

action_update_script() {
    check_for_update
}

# =============================================================================
# Backup functions
# =============================================================================
backup_target() {
    # Skip if target doesn't exist
    if [[ ! -d "${TARGET_DIR}" ]]; then
        info_msg "Aucune cible à sauvegarder"
        return 0
    fi

    local timestamp=$(date +"%Y-%m-%d_%H-%M-%S")
    local backup_path="${BACKUP_DIR}/${timestamp}"

    info_msg "Sauvegarde de la cible..."
    mkdir -p "${backup_path}"
    cp -r "${TARGET_DIR}/." "${backup_path}/"
    success_msg "Backup créé: ${timestamp}"

    # Cleanup old backups
    cleanup_old_backups
}

cleanup_old_backups() {
    [[ ! -d "${BACKUP_DIR}" ]] && return 0

    local backup_count=$(find "${BACKUP_DIR}" -maxdepth 1 -mindepth 1 -type d | wc -l)

    if [[ $backup_count -gt $MAX_BACKUPS ]]; then
        info_msg "Nettoyage des anciens backups..."
        ls -1dt "${BACKUP_DIR}"/*/ | tail -n +$((MAX_BACKUPS + 1)) | while read -r dir; do
            rm -rf "$dir"
        done
        success_msg "Anciens backups supprimés (garde les ${MAX_BACKUPS} derniers)"
    fi
}

list_backups() {
    if [[ ! -d "${BACKUP_DIR}" ]] || [[ -z "$(ls -A "${BACKUP_DIR}" 2>/dev/null)" ]]; then
        echo ""
        return 1
    fi
    ls -1t "${BACKUP_DIR}" 2>/dev/null
}

action_restore() {
    local backups=($(list_backups))

    if [[ ${#backups[@]} -eq 0 ]]; then
        info_msg "Aucun backup disponible"
        return 0
    fi

    # Add "Annuler" option at the end
    backups+=("Annuler")

    local selected=0
    local key

    while true; do
        clear
        echo ""
        echo -e "${CYAN}╔══════════════════════════════════════════════╗${NC}"
        echo -e "${CYAN}║${NC}  ${BOLD}${SCRIPT_NAME}${NC} v${YELLOW}${SCRIPT_VERSION}${NC}"
        echo -e "${CYAN}║${NC}  Module: ${BOLD}${MODULE_NAME}${NC} v${YELLOW}${MODULE_VERSION}${NC}"
        echo -e "${CYAN}║${NC}  ${DIM}Restaurer un backup${NC}"
        echo -e "${CYAN}╚══════════════════════════════════════════════╝${NC}"
        echo ""

        for i in "${!backups[@]}"; do
            if [[ $i -eq $selected ]]; then
                echo -e "  ${GREEN}▸${NC} ${BOLD}${backups[$i]}${NC}"
            else
                echo -e "    ${DIM}${backups[$i]}${NC}"
            fi
        done

        echo ""
        echo -e "${DIM}  ↑↓ Naviguer  ⏎ Valider  Echap Annuler${NC}"

        IFS= read -rsn1 key

        if [[ "$key" == $'\x1b' ]]; then
            read -rsn1 -t 0.3 k1
            if [[ -z "$k1" ]]; then
                info_msg "Restauration annulée"
                return 2
            fi
            read -rsn1 -t 0.3 k2
            case "${k1}${k2}" in
                '[A') [[ $selected -gt 0 ]] && selected=$((selected - 1)) || true ;;
                '[B') [[ $selected -lt $((${#backups[@]} - 1)) ]] && selected=$((selected + 1)) || true ;;
            esac
            continue
        fi

        case "$key" in
            '')  # Entrée
                # Check if "Annuler" selected (last option)
                if [[ $selected -eq $((${#backups[@]} - 1)) ]]; then
                    info_msg "Restauration annulée"
                    return 2
                fi

                local selected_backup="${backups[$selected]}"
                local backup_path="${BACKUP_DIR}/${selected_backup}"

                clear
                echo ""
                info_msg "Restauration de ${selected_backup}..."

                rm -rf "${TARGET_DIR:?}"
                mkdir -p "${TARGET_DIR}"
                cp -r "${backup_path}/." "${TARGET_DIR}/"

                success_msg "Backup restauré: ${selected_backup}"
                clear_cache
                return 0
                ;;
            'q'|'Q')
                info_msg "Restauration annulée"
                return 2
                ;;
        esac
    done
}

# =============================================================================
# Actions de base
# =============================================================================
sync_files() {
    backup_target
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

PS_EXEC="docker exec -e SERVER_PORT=80 -e HTTP_HOST=localhost ${DOCKER_CONTAINER}"
PS_CONSOLE="php -d memory_limit=1G /var/www/html/bin/console"

clear_cache() {
    info_msg "Vidage du cache..."
    docker exec ${DOCKER_CONTAINER} sh -c "rm -rf /var/www/html/var/cache/* && mkdir -p /var/www/html/var/cache/dev && chown -R www-data:www-data /var/www/html/var/cache && chmod -R 775 /var/www/html/var/cache" 2>/dev/null || true
    success_msg "Cache vidé"
}

do_install() {
    info_msg "Installation du module..."
    if ${PS_EXEC} ${PS_CONSOLE} prestashop:module install ${MODULE_NAME} 2>&1 | grep -q "réussi\|successful"; then
        success_msg "Module installé"
    else
        error_msg "Installation échouée"
    fi
}

do_uninstall() {
    info_msg "Désinstallation du module..."
    ${PS_EXEC} ${PS_CONSOLE} prestashop:module uninstall ${MODULE_NAME} 2>&1 | grep -q "réussi\|successful" || true
    success_msg "Module désinstallé"
}

# =============================================================================
# Actions composées
# =============================================================================
action_install_reinstall() {
    sync_files
    do_install
    clear_cache
}

action_uninstall() {
    do_uninstall
    clear_cache
}

action_uninstall_reinstall() {
    do_uninstall
    sync_files
    do_install
    clear_cache
}

action_delete() {
    do_uninstall
    delete_files
    clear_cache
}

action_delete_reinstall() {
    do_uninstall
    delete_files
    sync_files
    do_install
    clear_cache
}

action_clear_cache() {
    clear_cache
}

action_restart_docker() {
    info_msg "Arrêt des conteneurs..."
    cd "${PRESTASHOP_PATH}"
    docker compose down
    info_msg "Redémarrage des conteneurs..."
    docker compose up -d
    cd "${SCRIPT_DIR}"
    success_msg "Conteneurs redémarrés"
}

action_build_zip() {
    local zip_name="${MODULE_NAME}_v${MODULE_VERSION}.zip"
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
    success_msg "Archive créée: ${zip_name} (${zip_size})"
}

# =============================================================================
# Menu interactif
# =============================================================================
MENU_OPTIONS=(
    "Installer / Réinstaller"
    "Désinstaller"
    "Désinstaller puis Réinstaller"
    "Supprimer"
    "Supprimer puis Réinstaller"
    "Restaurer un backup"
    "Vider le cache"
    "Restart Docker Containers"
    "Build ZIP"
    "Mise à jour du script"
    "Quitter"
)

print_menu() {
    local selected=$1
    local status_msg=$2

    echo ""
    echo -e "${CYAN}╔══════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║${NC}  ${BOLD}${SCRIPT_NAME}${NC} v${YELLOW}${SCRIPT_VERSION}${NC}"
    echo -e "${CYAN}║${NC}  Module: ${BOLD}${MODULE_NAME}${NC} v${YELLOW}${MODULE_VERSION}${NC}"
    if [[ -n "$status_msg" ]]; then
        echo -e "${CYAN}║${NC}  ${GREEN}✓${NC} ${status_msg}"
    fi
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
    echo -e "${DIM}  ↑↓ Naviguer  ⏎ Valider  Echap/q Quitter${NC}"
}

run_menu() {
    local selected=0
    local key
    local last_status=""

    # Cacher le curseur
    tput civis 2>/dev/null || true

    # Restaurer le curseur à la sortie
    trap 'tput cnorm 2>/dev/null || true; exit' EXIT INT TERM

    while true; do
        # Effacer l'écran et afficher le menu
        clear
        print_menu $selected "$last_status"

        # Lire une touche
        IFS= read -rsn1 key

        # Gérer les séquences d'échappement (flèches ou Echap seul)
        if [[ "$key" == $'\x1b' ]]; then
            read -rsn1 -t 0.3 k1
            if [[ -z "$k1" ]]; then
                # Echap seul : quitter
                tput cnorm 2>/dev/null || true
                clear
                echo -e "${DIM}Au revoir !${NC}"
                exit 0
            fi
            read -rsn1 -t 0.3 k2
            case "${k1}${k2}" in
                '[A') [[ $selected -gt 0 ]] && selected=$((selected - 1)) || true ;;
                '[B') [[ $selected -lt $((${#MENU_OPTIONS[@]} - 1)) ]] && selected=$((selected + 1)) || true ;;
            esac
            continue
        fi

        case "$key" in
            '')  # Entrée
                local action_name="${MENU_OPTIONS[$selected]}"
                local result=0

                case $selected in
                    10) tput cnorm 2>/dev/null || true; clear; echo -e "${DIM}Au revoir !${NC}"; exit 0 ;;
                    *)
                        tput cnorm 2>/dev/null || true
                        clear
                        echo ""
                        echo -e "${CYAN}╔══════════════════════════════════════════════╗${NC}"
                        echo -e "${CYAN}║${NC}  ${BOLD}${action_name}${NC}"
                        echo -e "${CYAN}╚══════════════════════════════════════════════╝${NC}"
                        echo ""

                        # Exécuter l'action et capturer le résultat
                        set +e
                        case $selected in
                            0) action_install_reinstall ;;
                            1) action_uninstall ;;
                            2) action_uninstall_reinstall ;;
                            3) action_delete ;;
                            4) action_delete_reinstall ;;
                            5) action_restore ;;
                            6) action_clear_cache ;;
                            7) action_restart_docker ;;
                            8) action_build_zip ;;
                            9) action_update_script ;;
                        esac
                        result=$?
                        set -e

                        if [[ $result -eq 0 ]]; then
                            # Succès
                            last_status="${action_name} - Terminé !"
                        elif [[ $result -eq 2 ]]; then
                            # Annulé
                            last_status="${action_name} - Annulé"
                        else
                            # Erreur : attendre une touche
                            echo ""
                            echo -e "${DIM}Appuyez sur une touche pour continuer...${NC}"
                            read -rsn1
                            last_status=""
                        fi
                        tput civis 2>/dev/null || true
                        ;;
                esac
                ;;
            'q'|'Q')  # Quitter
                tput cnorm 2>/dev/null || true
                clear
                echo -e "${DIM}Au revoir !${NC}"
                exit 0
                ;;
            'k')  # vim: haut
                [[ $selected -gt 0 ]] && selected=$((selected - 1)) || true
                ;;
            'j')  # vim: bas
                [[ $selected -lt $((${#MENU_OPTIONS[@]} - 1)) ]] && selected=$((selected + 1)) || true
                ;;
            [1-9])  # Sélection directe par numéro
                local num=$((key - 1))
                [[ $num -lt ${#MENU_OPTIONS[@]} ]] && selected=$num || true
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
    echo -e "  ${GREEN}--delete${NC}       Supprimer"
    echo -e "  ${GREEN}--reset${NC}        Supprimer puis Réinstaller"
    echo -e "  ${GREEN}--restore${NC}      Restaurer un backup"
    echo -e "  ${GREEN}--cache${NC}        Vider le cache"
    echo -e "  ${GREEN}--restart${NC}      Restart Docker Containers"
    echo -e "  ${GREEN}--zip${NC}          Build le zip"
    echo -e "  ${GREEN}--update-script${NC}  Mise à jour du script"
    echo -e "  ${GREEN}--help${NC}         Affiche cette aide"
    echo ""
}

# =============================================================================
# Main
# =============================================================================
run_cli_action() {
    local title=$1
    local action=$2
    echo ""
    echo -e "${BOLD}${title}${NC}"
    echo -e "${DIM}─────────────────────────${NC}"
    $action
    echo ""
    success_msg "Terminé !"
}

cd "${SCRIPT_DIR}"
check_prerequisites

case "${1:-}" in
    --install)     run_cli_action "Installer / Réinstaller" action_install_reinstall ;;
    --uninstall)   run_cli_action "Désinstaller" action_uninstall ;;
    --reinstall)   run_cli_action "Désinstaller puis Réinstaller" action_uninstall_reinstall ;;
    --delete)      run_cli_action "Supprimer" action_delete ;;
    --reset)       run_cli_action "Supprimer puis Réinstaller" action_delete_reinstall ;;
    --restore)     run_cli_action "Restaurer un backup" action_restore ;;
    --cache)       run_cli_action "Vider le cache" action_clear_cache ;;
    --restart)     run_cli_action "Restart Docker Containers" action_restart_docker ;;
    --zip)         run_cli_action "Build ZIP" action_build_zip ;;
    --update-script) run_cli_action "Mise à jour du script" action_update_script ;;
    --help|-h)     show_help ;;
    "")            run_menu ;;
    *)             error_msg "Option inconnue: $1. Utilisez --help pour l'aide." ;;
esac
