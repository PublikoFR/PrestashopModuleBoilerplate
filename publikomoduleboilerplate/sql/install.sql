-- Table principale
CREATE TABLE IF NOT EXISTS `PREFIX_boilerplate_item` (
    `id_boilerplate_item` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `position` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_boilerplate_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table multilingue
CREATE TABLE IF NOT EXISTS `PREFIX_boilerplate_item_lang` (
    `id_boilerplate_item` INT(10) UNSIGNED NOT NULL,
    `id_lang` INT(10) UNSIGNED NOT NULL,
    `id_shop` INT(10) UNSIGNED NOT NULL DEFAULT 1,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    PRIMARY KEY (`id_boilerplate_item`, `id_lang`, `id_shop`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table multishop (optionnel)
CREATE TABLE IF NOT EXISTS `PREFIX_boilerplate_item_shop` (
    `id_boilerplate_item` INT(10) UNSIGNED NOT NULL,
    `id_shop` INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id_boilerplate_item`, `id_shop`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
