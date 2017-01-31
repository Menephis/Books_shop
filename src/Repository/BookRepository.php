<?php
namespace src\Repository;

use \Doctrine\DBAL\DBALException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use src\Domain\Book;
use src\Repository\FieldDescription;
use Doctrine\DBAL\Types\Type;

class BookRepository extends AbstractRepository
{
    const ALLOWED_IMAGE_EXTENSION = array('jpeg', 'jpg');
    /**
    * Get books by category
    *
    * @param int $idCategory - category for selection
    * @param int $firstPos - position for start selection
    * @param int $limit - limit of selection
    *
    * @return array $books
    */
    public function getBooksByCat(int $idCategory, int $firstPos = 0, int $limit = 10)
    {
        try
        {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder
                ->select('b.book_id', 'b.book_name', 'b.authors', 'b.price', 'b.preview_img')
                ->from('books', 'b')
                ->innerJoin('b', 'categories_has_books', 'c_h_b', 'b.book_id = c_h_b.books_book_id')
                ->innerJoin('c_h_b', 'categories', 'c', 'c.category_id = c_h_b.categories_category_id')
                ->innerJoin('c' , 'categories', 'p', 'p.category_id = ?')
                ->where('c.left_key >= p.left_key')
                ->andWhere('c.right_key <= p.right_key')
                ->setFirstResult($firstPos)
                ->setMaxResults($limit);
            $stmt = $this->connection->prepare($queryBuilder);
            $stmt->bindValue(1, $idCategory);
            $stmt->execute();
            $books = $stmt->fetchAll();
            return $this->objectsFromArrayOfAssoc($books);
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
    * @param string $authors
    * @param string $bookPrice
    * @param UploadedFile $image - file which we will be moved. Image must be JPG type
    * @param string $destinationPath - destination directory for move
    *
    * @return boolean - successeful/failed
    */
    public function saveBook(string $bookName, string $authors, int $bookPrice, UploadedFile $image, int $idCategory, string $destinationPath, string $description, $dateRelease, string $language, int $printing, array $images)
    {
        try
        {   
            
            $imageName = md5(microtime()) . '.' . $this->checkExtension($image);
            $directory = $this->generatePathToImage();
            $fullPath = $directory . DIRECTORY_SEPARATOR . $imageName;
            
            if(count($images) > 0){
                $createPaths = function(UploadedFile $someImage){
                    return $this->generatePathToImage() . DIRECTORY_SEPARATOR . md5(microtime()) . '.' . $this->checkExtension($someImage);
                };
                $pathToImages = array_map($createPaths, $images);
            }else{
                $pathToImages = array();
            }
            // Creating record in database
            $queryBuilderToBooks = $this->connection->createQueryBuilder();
            $queryBuilderToBooks
                ->insert('books')
                ->values(
                    [
                        'book_name' => ':name',
                        'authors' => ':auth',
                        'price' => ':price',
                        'preview_img' => ':img',
                    ]
                );
            $queryBuilderToProperties = $this->connection->createQueryBuilder();
            $queryBuilderToProperties
                ->insert('books_properties')
                ->values(
                    [
                        'books_book_id' => ':id',
                        'description' => ':desc',
                        'date_of_release' => ':date',
                        'language' => ':lang',
                        'printing' => ':print',
                        'books_img' => ':imgs',
                    ]
                );
            $this->connection->beginTransaction();
            // First insert
            $stmt = $this->connection->prepare($queryBuilderToBooks);
            $stmt->bindValue('name', $bookName);
            $stmt->bindValue('auth', $authors);
            $stmt->bindValue('price', $bookPrice);
            $stmt->bindValue('img', $fullPath);
            $stmt->execute();
            // selecting id book
            $bookId = $this->connection->lastInsertId();
            // Second insert
            unset($stmt);
            $stmt = $this->connection->prepare($queryBuilderToProperties);
            $stmt->bindValue('id', $bookId);
            $stmt->bindValue('desc', $description);
            $stmt->bindValue('date', $dateRelease);
            $stmt->bindValue('lang', $language);
            $stmt->bindValue('print', $printing);
            $stmt->bindValue('imgs', $this->serializePaths($pathToImages));
            $stmt->execute();
            // Third query
            $this->connection->insert('categories_has_books', array('categories_category_id' => $idCategory, 'books_book_id' => $bookId));
            // For preview image
            $image->move($destinationPath . DIRECTORY_SEPARATOR . $directory, $imageName);
            // For galery image
            foreach($pathToImages as $key => $path){
                $imgName = basename($path);
                $dir = str_replace($imgName, '', $path);
                $images[$key]->move(realpath($destinationPath) . DIRECTORY_SEPARATOR . $dir, $imgName);
            }
            
            $this->connection->commit();
            return true;
        }catch(\InvalidArgumentException $exception)
        {
            throw $exception;
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
            $image = $this->connection->fetchAssoc('SELECT b.book_id, b.preview_img FROM books b WHERE b.book_id = ?', array($bookId));
            if ( ! $image['book_id'] )
                throw new \Exception("Book with id $bookId doesn't exist");
            $x = $this->connection->delete('books', array('book_id' => $bookId));
            if( ! @unlink($pathToImages . $image['preview_img']) and ! $image['book_id'])
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
    * @param string $authors
    * @param string $bookPrice
    * @param UploadedFile $image - file which we will be moved
    * @param string $pathToImages - path to images directory for move
    *
    * @return boolean - successefully or not 
    */
    public function updateBook(int $bookId, string $bookName, string $authors, int $bookPrice, $image, int $idCategory, string $pathToImages, string $description, $dateRelease, string $language, int $printing)
    {
        try
        {   
            $queryBuilderToBook = $this->connection->createQueryBuilder();
            $queryBuilderToBook
                ->update('books', 'b')
                ->set('b.book_name', ':name')
                ->set('b.authors', ':auth')
                ->set('b.price', ':price')
                ->where('b.book_id = :id');
            $queryBuilderToProperties = $this->connection->createQueryBuilder();
            $queryBuilderToProperties
                ->update('books_properties', 'bp')
                ->set('bp.description', ':desc')
                ->set('bp.date_of_release', ':date')
                ->set('bp.language', ':lang')
                ->set('bp.printing', ':print')
                ->where('bp.books_book_id', ':p_id');
            // Change preview image if it was passed
            if ($image instanceof UploadedFile){
                $queryBuilderToBook->set('b.preview_img', ':img');
                // 
                $imageName = md5(microtime()) . '.' . $this->checkExtension($image);
                $directory = $this->generatePathToImage();
                $fullPath = $directory . DIRECTORY_SEPARATOR . $imageName;

                $this->connection->beginTransaction();
                $oldImage = $this->connection->fetchAssoc('SELECT b.book_id, b.preview_img FROM books b WHERE b.book_id = ?', array($bookId));
                if ( ! $oldImage['book_id'] )
                    throw new \Exception("Book with id $bookId doesn't exist");
                $stmt = $this->connection->prepare($queryBuilderToBook);
                $stmt->bindValue('img', $fullPath);
            }else{
                $this->connection->beginTransaction();
                $stmt = $this->connection->prepare($queryBuilderToBook);
            }
            // For book
            $stmt->bindValue('name', $bookName);
            $stmt->bindValue('auth', $authors);
            $stmt->bindValue('price', $bookPrice);
            $stmt->bindValue('id', $bookId);
            $stmt->execute();
            // For property book
            $stmt->bindValue('desc', $description);
            $stmt->bindValue('date', $dateRelease);
            $stmt->bindValue('lang', $language);
            $stmt->bindValue('print', $printing);
            $stmt->bindValue('p_id', $bookId);
            $stmt->execute();
            // Update book category
            $this->connection->update('categories_has_books', array('categories_category_id' => $idCategory), array('books_book_id' => $bookId));
            // move image if it was passed
            if($image instanceof UploadedFile){
                $pathToImages = rtrim($pathToImages, '\\');
                if( ! @unlink($pathToImages . DIRECTORY_SEPARATOR . $oldImage['preview_img']))
                    throw new \Exception("Can't delete file - " . $pathToImages . DIRECTORY_SEPARATOR . $oldImage['preview_img']);
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
    * @return string  - full class name of domain entity
    */
    protected function getDomainClass()
    {
        return Book::class;
    }
    /**
    * @return array - description of field
    */
    protected function getFieldDescription()
    {
        return [
            new FieldDescription('id', 'book_id', Type::INTEGER),
            new FieldDescription('name', 'book_name', Type::STRING),
            new FieldDescription('authors', 'authors', Type::STRING),
            new FieldDescription('price', 'price', Type::INTEGER),
            new FieldDescription('image', 'preview_img', Type::STRING),
        ];
    }
    /**
    * Generating path to storing images
    *
    * Generate path  - RandomDirectory/RandomDirectory
    *
    * @return string  - path to store image
    */
    protected function generatePathToImage()
    {
        return substr(md5(microtime()), mt_rand(0, 30), 2) . DIRECTORY_SEPARATOR . substr(md5(microtime()), mt_rand(0, 30), 2);
    }
    /**
    * Serialize array of paths to save in DB
    *
    * @param array $paths - array of paths 
    *
    * @return string
    */
    protected function serializePaths(array $paths)
    {
        $string = '';
        foreach($paths as $key => $path){
            $string .= $key . ':' . $path . ';';
        }
        return rtrim($string, ';');
    }
    /**
    * Unserialize sting with paths from DB
    *
    * @param string $paths 
    *
    * @return array
    */
    protected function unserializePaths(string $paths)
    {
        $pathArray = array();
        $array = explode(';', $paths);
        foreach($array as $path){
            $field = explode(':', $path);
            $pathArray[$field[0]] = $field[1];
        }
        return $pathArray;
    }
    /**
    * @param $result
    * @param FieldDescription
    * 
    * @return mixed
    */
    protected function prepareValue($fieldDescription, array $result)
    {
        foreach($fieldDescription as $description){
            if($description->getProperty() == 'price'){
                $result[$description->getDBField()] = $result[$description->getDBField()] / 100;
            }
        }
        return $result;
    }
    /**
    * @param UploadedFile $image
    * 
    * @return string 
    *
    * @throws - InvalidArgumentException
    */
    protected function checkExtension(UploadedFile $image)
    {
        $extension = $image->guessClientExtension();
        if( ! in_array($extension, self::ALLOWED_IMAGE_EXTENSION)){
            $answer = implode(',', self::ALLOWED_IMAGE_EXTENSION);
            throw new \InvalidArgumentException('Passed extension of image must be in ' . $answer);
        }
        return $extension;
    }
}
?>
