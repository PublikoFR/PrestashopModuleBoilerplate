<?php
/**
 * Symfony Admin Controller for PrestaShop 9+
 *
 * This controller uses the modern Symfony-based architecture
 * with Grid and Form components.
 *
 * @author    Publiko
 * @copyright Publiko
 * @license   Commercial
 */

declare(strict_types=1);

namespace PublikoModuleBoilerplate\Controller\Admin;

use BoilerplateItem;
use Context;
use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\Builder\FormBuilderInterface;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\Handler\FormHandlerInterface;
use PrestaShop\PrestaShop\Core\Grid\GridFactoryInterface;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for managing BoilerplateItem entities in PS9+
 */
class BoilerplateItemController extends PrestaShopAdminController
{
    /**
     * List all items with Grid
     */
    #[AdminSecurity("is_granted('read', request.get('_legacy_controller'))")]
    public function indexAction(
        Request $request,
        GridFactoryInterface $boilerplateItemGridFactory
    ): Response {
        $grid = $boilerplateItemGridFactory->getGrid(
            $this->buildFiltersFromRequest($request, 'boilerplate_item')
        );

        return $this->render('@Modules/publikomoduleboilerplate/views/templates/admin/ps9/index.html.twig', [
            'grid' => $this->presentGrid($grid),
            'enableSidebar' => true,
            'layoutTitle' => $this->trans('Boilerplate Items', [], 'Modules.Publikomoduleboilerplate.Admin'),
        ]);
    }

    /**
     * Create new item
     */
    #[AdminSecurity("is_granted('create', request.get('_legacy_controller'))")]
    public function createAction(
        Request $request,
        FormBuilderInterface $boilerplateItemFormBuilder,
        FormHandlerInterface $boilerplateItemFormHandler
    ): Response {
        $form = $boilerplateItemFormBuilder->getForm();
        $form->handleRequest($request);

        try {
            $result = $boilerplateItemFormHandler->handle($form);

            if ($result->isSubmitted() && $result->isValid()) {
                $this->addFlash(
                    'success',
                    $this->trans('Item created successfully.', [], 'Modules.Publikomoduleboilerplate.Admin')
                );

                return $this->redirectToRoute('admin_boilerplate_items_index');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->render('@Modules/publikomoduleboilerplate/views/templates/admin/ps9/form.html.twig', [
            'form' => $form->createView(),
            'layoutTitle' => $this->trans('Add new item', [], 'Modules.Publikomoduleboilerplate.Admin'),
            'enableSidebar' => true,
        ]);
    }

    /**
     * Edit existing item
     */
    #[AdminSecurity("is_granted('update', request.get('_legacy_controller'))")]
    public function editAction(
        int $itemId,
        Request $request,
        FormBuilderInterface $boilerplateItemFormBuilder,
        FormHandlerInterface $boilerplateItemFormHandler
    ): Response {
        $form = $boilerplateItemFormBuilder->getFormFor($itemId);
        $form->handleRequest($request);

        try {
            $result = $boilerplateItemFormHandler->handleFor($itemId, $form);

            if ($result->isSubmitted() && $result->isValid()) {
                $this->addFlash(
                    'success',
                    $this->trans('Item updated successfully.', [], 'Modules.Publikomoduleboilerplate.Admin')
                );

                return $this->redirectToRoute('admin_boilerplate_items_index');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->render('@Modules/publikomoduleboilerplate/views/templates/admin/ps9/form.html.twig', [
            'form' => $form->createView(),
            'layoutTitle' => $this->trans('Edit item', [], 'Modules.Publikomoduleboilerplate.Admin'),
            'enableSidebar' => true,
        ]);
    }

    /**
     * Delete item
     */
    #[AdminSecurity("is_granted('delete', request.get('_legacy_controller'))")]
    public function deleteAction(int $itemId): RedirectResponse
    {
        $item = new BoilerplateItem($itemId);

        if ($item->id && $item->delete()) {
            $this->addFlash(
                'success',
                $this->trans('Item deleted successfully.', [], 'Modules.Publikomoduleboilerplate.Admin')
            );
        } else {
            $this->addFlash(
                'error',
                $this->trans('Could not delete item.', [], 'Modules.Publikomoduleboilerplate.Admin')
            );
        }

        return $this->redirectToRoute('admin_boilerplate_items_index');
    }

    /**
     * Toggle item status
     */
    #[AdminSecurity("is_granted('update', request.get('_legacy_controller'))")]
    public function toggleStatusAction(int $itemId): RedirectResponse
    {
        $item = new BoilerplateItem($itemId);

        if ($item->id) {
            $item->active = !$item->active;
            $item->update();

            $this->addFlash(
                'success',
                $this->trans('Status updated.', [], 'Modules.Publikomoduleboilerplate.Admin')
            );
        }

        return $this->redirectToRoute('admin_boilerplate_items_index');
    }

    /**
     * Bulk delete items
     */
    #[AdminSecurity("is_granted('delete', request.get('_legacy_controller'))")]
    public function bulkDeleteAction(Request $request): RedirectResponse
    {
        $ids = $request->request->all('boilerplate_item_bulk');

        if (!empty($ids)) {
            $deleted = 0;
            foreach ($ids as $id) {
                $item = new BoilerplateItem((int) $id);
                if ($item->id && $item->delete()) {
                    $deleted++;
                }
            }

            $this->addFlash(
                'success',
                $this->trans('%count% item(s) deleted.', ['%count%' => $deleted], 'Modules.Publikomoduleboilerplate.Admin')
            );
        }

        return $this->redirectToRoute('admin_boilerplate_items_index');
    }

    /**
     * Bulk enable items
     */
    #[AdminSecurity("is_granted('update', request.get('_legacy_controller'))")]
    public function bulkEnableAction(Request $request): RedirectResponse
    {
        return $this->bulkToggleStatus($request, true);
    }

    /**
     * Bulk disable items
     */
    #[AdminSecurity("is_granted('update', request.get('_legacy_controller'))")]
    public function bulkDisableAction(Request $request): RedirectResponse
    {
        return $this->bulkToggleStatus($request, false);
    }

    /**
     * Helper for bulk status toggle
     */
    private function bulkToggleStatus(Request $request, bool $status): RedirectResponse
    {
        $ids = $request->request->all('boilerplate_item_bulk');

        if (!empty($ids)) {
            $updated = 0;
            foreach ($ids as $id) {
                $item = new BoilerplateItem((int) $id);
                if ($item->id) {
                    $item->active = $status;
                    $item->update();
                    $updated++;
                }
            }

            $this->addFlash(
                'success',
                $this->trans('%count% item(s) updated.', ['%count%' => $updated], 'Modules.Publikomoduleboilerplate.Admin')
            );
        }

        return $this->redirectToRoute('admin_boilerplate_items_index');
    }

    /**
     * Build filters from request for Grid
     */
    private function buildFiltersFromRequest(Request $request, string $filterId): array
    {
        return [
            'limit' => $request->query->getInt('limit', 50),
            'offset' => $request->query->getInt('offset', 0),
            'orderBy' => $request->query->get('orderBy', 'position'),
            'sortOrder' => $request->query->get('sortOrder', 'asc'),
            'filters' => $request->query->all('filters') ?? [],
        ];
    }
}
