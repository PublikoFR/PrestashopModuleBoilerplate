<?php
/**
 * Grid Query Builder for BoilerplateItem
 *
 * Builds SQL queries for the admin grid (PS9+)
 *
 * @author    Publiko
 * @copyright Publiko
 * @license   Commercial
 */

declare(strict_types=1);

namespace PublikoModuleBoilerplate\Grid\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Query\AbstractDoctrineQueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Query\DoctrineSearchCriteriaApplicatorInterface;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

class BoilerplateItemQueryBuilder extends AbstractDoctrineQueryBuilder
{
    /**
     * @var DoctrineSearchCriteriaApplicatorInterface
     */
    private $searchCriteriaApplicator;

    /**
     * @var int
     */
    private $contextLangId;

    /**
     * @var int
     */
    private $contextShopId;

    public function __construct(
        Connection $connection,
        string $dbPrefix,
        DoctrineSearchCriteriaApplicatorInterface $searchCriteriaApplicator,
        int $contextLangId,
        int $contextShopId
    ) {
        parent::__construct($connection, $dbPrefix);

        $this->searchCriteriaApplicator = $searchCriteriaApplicator;
        $this->contextLangId = $contextLangId;
        $this->contextShopId = $contextShopId;
    }

    /**
     * Get query for grid data
     */
    public function getSearchQueryBuilder(SearchCriteriaInterface $searchCriteria): QueryBuilder
    {
        $qb = $this->getBaseQuery();
        $qb->select('i.id_boilerplate_item, il.name, i.active, i.position, i.date_add, i.date_upd');

        $this->applyFilters($qb, $searchCriteria);
        $this->searchCriteriaApplicator->applyPagination($searchCriteria, $qb);
        $this->searchCriteriaApplicator->applySorting($searchCriteria, $qb);

        return $qb;
    }

    /**
     * Get query for counting total results
     */
    public function getCountQueryBuilder(SearchCriteriaInterface $searchCriteria): QueryBuilder
    {
        $qb = $this->getBaseQuery();
        $qb->select('COUNT(DISTINCT i.id_boilerplate_item)');

        $this->applyFilters($qb, $searchCriteria);

        return $qb;
    }

    /**
     * Build base query with joins
     */
    private function getBaseQuery(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->from($this->dbPrefix . 'boilerplate_item', 'i')
            ->leftJoin(
                'i',
                $this->dbPrefix . 'boilerplate_item_lang',
                'il',
                'i.id_boilerplate_item = il.id_boilerplate_item AND il.id_lang = :langId AND il.id_shop = :shopId'
            )
            ->setParameter('langId', $this->contextLangId)
            ->setParameter('shopId', $this->contextShopId);
    }

    /**
     * Apply search filters
     */
    private function applyFilters(QueryBuilder $qb, SearchCriteriaInterface $searchCriteria): void
    {
        $filters = $searchCriteria->getFilters();

        foreach ($filters as $filterName => $filterValue) {
            if (empty($filterValue)) {
                continue;
            }

            switch ($filterName) {
                case 'id_boilerplate_item':
                    $qb->andWhere('i.id_boilerplate_item = :id')
                        ->setParameter('id', (int) $filterValue);
                    break;

                case 'name':
                    $qb->andWhere('il.name LIKE :name')
                        ->setParameter('name', '%' . $filterValue . '%');
                    break;

                case 'active':
                    $qb->andWhere('i.active = :active')
                        ->setParameter('active', (int) $filterValue);
                    break;
            }
        }
    }
}
