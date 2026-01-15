<?php
/**
 * Form Data Handler for BoilerplateItem
 *
 * Handles form submission for creating/updating items (PS9+)
 *
 * @author    Publiko
 * @copyright Publiko
 * @license   Commercial
 */

declare(strict_types=1);

namespace PublikoModuleBoilerplate\Form;

use BoilerplateItem;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataHandler\FormDataHandlerInterface;

class BoilerplateItemDataHandler implements FormDataHandlerInterface
{
    /**
     * Create new item from form data
     *
     * @return int Created item ID
     */
    public function create(array $data): int
    {
        $item = new BoilerplateItem();
        $this->populateItem($item, $data);
        $item->position = BoilerplateItem::getNextPosition();
        $item->date_add = date('Y-m-d H:i:s');
        $item->date_upd = date('Y-m-d H:i:s');

        if (!$item->add()) {
            throw new \PrestaShop\PrestaShop\Core\Exception\CoreException('Failed to create BoilerplateItem');
        }

        return (int) $item->id;
    }

    /**
     * Update existing item from form data
     *
     * @return int Updated item ID
     */
    public function update($id, array $data): int
    {
        $item = new BoilerplateItem((int) $id);

        if (!$item->id) {
            throw new \PrestaShop\PrestaShop\Core\Exception\CoreException(
                sprintf('BoilerplateItem with id %d not found', $id)
            );
        }

        $this->populateItem($item, $data);
        $item->date_upd = date('Y-m-d H:i:s');

        if (!$item->update()) {
            throw new \PrestaShop\PrestaShop\Core\Exception\CoreException('Failed to update BoilerplateItem');
        }

        return (int) $item->id;
    }

    /**
     * Populate item with form data
     */
    private function populateItem(BoilerplateItem $item, array $data): void
    {
        if (isset($data['name'])) {
            $item->name = $data['name'];
        }

        if (isset($data['description'])) {
            $item->description = $data['description'];
        }

        if (isset($data['active'])) {
            $item->active = (bool) $data['active'];
        }
    }
}
