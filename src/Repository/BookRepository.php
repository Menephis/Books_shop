<?php
namespace src\Repository;

use \Doctrine\DBAL\DBALException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;

class BookRepository
{
    /**
    * @var - resourse $connection - stores connection to DB
    */
    protected $connection;
    /**
    * @param - resourse $connection
    */
    function __construct($connection)
    {
        $this->connection = $connection;
    }
    /**
    * Get books by category
    *
    * @param int $IdCategory - category for selection
    * @param int $firstPos - position for start selection
    * @param int $limit - limit of selection
    *
    * @return array $books
    */
    public function getBooksByCat(int $IdCategory, int $firstPos = 0, int $limit = 10): array
    {
        try
        {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder
                ->select('b.book_id', 'b.book_name', 'b.description', 'b.price', 'b.preview_img')
                ->from('books', 'b')
                ->innerJoin('b', 'categories_has_books', 'c_h_b', 'b.book_id = c_h_b.books_book_id')
                ->innerJoin('c_h_b', 'categories', 'c', 'c.category_id = c_h_b.categories_category_id')
                ->innerJoin('c' , 'categories', 'p', 'p.category_id = ?')
                ->where('c.left_key >= p.left_key')
                ->andWhere('c.right_key <= p.right_key')
                ->setFirstResult($firstPos)
                ->setMaxResults($limit);
            $stmt = $this->connection->prepare($queryBuilder);
            $stmt->bindValue(1, $IdCategory);
            $stmt->execute();
            $books = $stmt->fetchAll();
            return $books;
        }catch(DBALException $exception)
        {
            throw $exception;
        }
    }
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
    * Add book to DB 
    *
    * This method keeping synchronisation between filesystem that store images and DB, for create new record. ONLY jpeg type
    *
    * @param string $bookName
    * @param string $bookDes
    * @param string $bookPrice
    * @param UploadedFile $image - file which we will be moved. Image must be JPG type
    * @param string $destinationPath - destination directory for move
    *
    * @return boolean - successeful/failed
    */
    public function saveBook(string $bookName, string $bookDes, int $bookPrice, UploadedFile $image, string $destinationPath)
    {
        try
        {   
            // Set hierarchy subdirectories for image and new name
            $directory = substr(md5(microtime()), mt_rand(0, 30), 2) . DIRECTORY_SEPARATOR . substr(md5(microtime()), mt_rand(0, 30), 2);
            $imageName = md5(microtime()) . ".jpg";
            // Creating record in database
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder
                ->insert('books')
                ->values(
                    [
                        'book_name' => ':name',
                        'description' => ':des',
                        'price' => ':price',
                        'preview_img' => ':img',
                    ]
                );
            $this->connection->beginTransaction();
            $stmt = $this->connection->prepare($queryBuilder);
            $stmt->bindValue('name', $bookName);
            $stmt->bindValue('des', $bookDes);
            $stmt->bindValue('price', $bookPrice);
            $stmt->bindValue('img', $directory . DIRECTORY_SEPARATOR . $imageName);
            $stmt->execute();
            $image->move($destinationPath . DIRECTORY_SEPARATOR . $directory, $imageName);
            $this->connection->commit();
            return true;
        }catch(\Exception $exception)
        {
            $this->connection->rollback();
            throw $exception;
        }
    }
    /**
    * Delete book
    *
    * This method keeping synchronisation between filesystem that store images and DB, for delete record
    *
    * @param int $bookId
    * @param string $pathToImages
    *
    * @return boolean - successufully or not
    */
    public function deleteBook(int $bookId, string $pathToImages)
    {
        try
        {   
            $this->connection->beginTransaction();
            $image = $this->connection->fetchColumn('SELECT b.preview_img FROM books b WHERE b.book_id = ?', array($bookId), 0);
            if ( ! $image )
                throw new \Exception("Book with id $bookId doesn't exist");
            $x = $this->connection->delete('books', array('book_id' => $bookId));
            if( ! @unlink($pathToImages . $image))
                throw new \Exception("Can't delete file");
            $this->connection->commit(); 
            return true;
        }catch(\Exception $exception)
        {
            $this->connection->rollback();
            throw $exception;
        }
    }
    /*
    * Update book
    * This method keeping synchronisation between filesystem that store images and DB, for update record. ONLY jpeg type
    *
    * @param integer $bookId
    * @param string $bookName
    * @param string $bookDes
    * @param string $bookPrice
    * @param UploadedFile $image - file which we will be moved
    * @param string $pathToImages - path to images directory for move
    *
    * @return boolean - successefully or not 
    */
    public function updateBook(int $bookId, string $bookName, string $bookDes, int $bookPrice, UploadedFile $image = null , string $pathToImages = null)
    {
        try
        {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder
                ->update('books', 'b')
                ->set('b.book_name', ':name')
                ->set('b.description', ':des')
                ->set('b.price', ':price')
                ->where('b.book_id = :id');
            if ($image){
                $queryBuilder->set('b.preview_img', ':img');
                // Set hierarchy subdirectories for image and new name
                $directory = substr(md5(microtime()), mt_rand(0, 30), 2) . DIRECTORY_SEPARATOR . substr(md5(microtime()), mt_rand(0, 30), 2);
                $imageName = md5(microtime()) . ".jpg";
                $this->connection->beginTransaction();
                $oldImage = $this->connection->fetchColumn('SELECT b.preview_img FROM books b WHERE book_id = ?', array($bookId), 0);
                if ( ! $oldImage )
                    throw new \Exception("Book with id $bookId doesn't exist");
                $stmt = $this->connection->prepare($queryBuilder);
                $stmt->bindValue('img', $directory . DIRECTORY_SEPARATOR . $imageName);
            }else{
                $this->connection->beginTransaction();
                $stmt = $this->connection->prepare($queryBuilder);
            }
            $stmt->bindValue('name', $bookName);
            $stmt->bindValue('des', $bookDes);
            $stmt->bindValue('price', $bookPrice);
            $stmt->bindValue('id', $bookId);
            $stmt->execute();
            if($image){
                if( ! @unlink($pathToImages . $oldImage))
                    throw new \Exception("Can't delete file");
                $image->move($pathToImages . DIRECTORY_SEPARATOR . $directory, $imageName);
            }
            $this->connection->commit();
            return true;
        }catch(\Exception $exception)
        {
            $this->connection->rollback();
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
}
?>
