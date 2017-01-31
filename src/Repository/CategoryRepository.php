<?php
namespace src\Repository;

class CategoryRepository extends AbstractRepository
{   
    /**
    * Get all category
    *
    * @return array $categories
    */
    public function getCategories()
    {
        try
        {
            $queryBuilder = $this->connection->createQueryBuilder();
            $query = $queryBuilder
                ->select('category_id', 'name_category', 'row')
                ->from('categories')
                ->addOrderBy('left_key');
            $stmt = $this->connection->query($query);
            $categories = $stmt->fetchAll();
            return $categories;
        }catch(DBALException $exception)
        {
            throw $exception;
        }
    }
    /**
    * Add category to categories
    *
    * Stored procedure is using here because query is complicated because architecture of categories table implements nested sets 
    *
    * @param $idParentCategory - any category must have parent category, except main category
    * @param $nameNewCategory - name of new category, that will be add
    *
    * @retun boolean - procedure success
    */
    public function addCategory(int $idParentCategory, string $nameNewCategory)
    {
        try
        {
            $query = 'call add_category(?, ?)';
            $stmt = $this->connection->prepare($query);
            $stmt->bindValue(1, $idParentCategory);
            $stmt->bindValue(2, $nameNewCategory);
            $stmt->execute();
            return true;
        }catch(DBALException $exception)
        {
            throw $exception;
        }
    }
    /**
    * Delete category from categories
    *
    * Stored procedure is using here because query is complicated because architecture of categories table implements nested sets 
    *
    * @param $idDeleteCategory - any category must have parent category, except main category
    *
    * @retun boolean - procedure success
    */
    public function deleteCategory(int $idDeleteCategory)
    {
        try
        {
            $query = 'call delete_category(' . $idDeleteCategory . ')';
            $this->connection->query($query);
            return true;
        }catch(DBALException $exception)
        {
            throw $exception;
        }
    }
    /** 
    * Move category
    *
    * Move category used in case when need change parent category
    * 
    * @param integer $idMovedCategories - moved categories
    * @param integer $idNewParentCategory - new parent category
    *
    * @retun boolean - procedure success
    */
    public function moveCategory(int $idMovedCategories, int $idNewParentCategory)
    {
        try
        {
            $query = 'call change_parent(' . $idMovedCategories . ', ' . $idNewParentCategory . ')';
            $this->connection->query($query);
            return true;
        }catch(DBALException $exception)
        {
            throw $exception;
        }
    }
     /** 
    * Change order of subcategories from one category
    * 
    * @param integer $idMovedCategories - moved categories
    * @param integer $idSetAfterCategories - category after that moving category will stay, if it is id parent category then category will stay at the first  place
    *
    * @retun boolean - procedure success
    */
    public function changeOrder(int $idMovedCategories, int $idSetAfterCategories)
    {
        try
        {   
            $query = 'call change_order(' . $idMovedCategories . ', ' . $idSetAfterCategories . ')';
            $this->connection->query($query);
            return true;
        }catch(DBALException $exception)
        {
            throw $exception;
        }
    }
    /**
    * Cap
    */
    protected function getDomainClass()
    {
        return null;
    }
    /**
    * Cap
    */
    protected function getFieldDescription()
    {
        return null;
    }
    /**
    * Cap
    */
    protected function prepareValue($fieldDescription, array $result)
    {
        return null;
    }
}
?>