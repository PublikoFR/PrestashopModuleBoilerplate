<?php
/**
 * Form Data Provider for BoilerplateItem
 *
 * Provides default values and loads existing entity data for forms (PS9+)
 *
 * @author    Publiko
 * @copyright Publiko
 * @license   Commercial
 */

declare(strict_types=1);

namespace PublikoModuleBoilerplate\Form;

use BoilerplateItem;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataProvider\FormDataProviderInterface;

class BoilerplateItemDataProvider implements FormDataProviderInterface
{
    /**
     * Get default form data for new item
     */
    public function getDefaultData(): array
    {
        return [
            'name' => [],
            'description' => [],
            'active' => true,
        ];
    }

    /**
     * Get form data for existing item
     */
    public function getData($id): array
    {
        $item = new BoilerplateItem((int) $id);

        if (!$item->id) {
            throw new \PrestaShop\PrestaShop\Core\Exception\CoreException(
                sprintf('BoilerplateItem with id %d not found', $id)
            );
        }

        return [
            'name' => $item->name,
            'description' => $item->description,
            'active' => (bool) $item->active,
        ];
    }
}
