<?php
namespace src\Repository;

use \Doctrine\DBAL\DBALException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use src\Domain\Book;
use src\Domain\BookDetail;
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
                ->select('DISTINCT(b.book_id)', 'b.book_name', 'b.authors', 'b.price', 'b.preview_img')
                ->from('books', 'b')
                ->innerJoin('b', 'categories_has_books', 'c_h_b', 'b.book_id = c_h_b.books_book_id')
                ->innerJoin('c_h_b', 'categories', 'c', 'c.category_id = c_h_b.categories_category_id')
                ->innerJoin('c' , 'categories', 'p', 'p.category_id = ?')
                ->where('c.left_key >= p.left_key')
                ->andWhere('c.right_key <= p.right_key')
                ->setFirstResult($firstPos)
                ->setMaxResults($limit);
            $stmt = $this->connection->prepare($queryBuilder);
            $stmt->bindValue(1, $idCategory, Type::INTEGER);
            $stmt->execute();
            $books = $stmt->fetchAll();
            return $this->objectsFromArrayOfAssoc($books, Book::class);
        }catch(DBALException $exception)
        {
            throw $exception;
        }
    }
    /**
    * 
    * @param int $idBook
    *
    * @return array $result
    */
    public function getBookDetail(int $idBook)
    {
        try
        {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder
                ->select('b.book_id', 'b.book_name', 'b.authors', 'b.price', 'b.preview_img', 'p.description', 'p.date_of_release', 'p.language', 'p.printing', 'p.books_img', 'GROUP_CONCAT(CONCAT(c.category_id, \':\', c.name_category) SEPARATOR \';\') as categories')
                ->from('books', 'b')
                ->innerJoin('b', 'books_properties', 'p', 'p.books_book_id = b.book_id')
                ->innerJoin('b', 'categories_has_books', 'c_h_b', 'b.book_id = c_h_b.books_book_id')
                ->innerJoin('c_h_b', 'categories', 'c', 'c.category_id = c_h_b.categories_category_id')
                ->where('b.book_id = ?')
                ->andWhere('c.row != ?');
            $stmt = $this->connection->prepare($queryBuilder);
            $stmt->bindValue(1, $idBook, Type::INTEGER);
            $stmt->bindValue(2, 0, Type::INTEGER);
            $stmt->execute();
            $book = $stmt->fetch();
            return $this->objectFromAssoc($book, BookDetail::class);
        }catch(DBALException $exception)
        {
            throw $exception;
        }
    }
    /**
    * Add book to DB 
    *
    * This method keeping synchronisation between filesystem that store images and DB, for create new record. 
    *
    * @param string $bookName
    * @param string $authors
    * @param string $bookPrice
    * @param UploadedFile $image - file which we will be moved. Image must be JPG type
    * @param array $idCategories
    * @param string $description
    * @param int $dateRelease
    * @param string $language
    * @param int $printing
    * @param array $images
    * @param string $destinationPath - destination directory for move
    *
    * @return boolean - successeful/failed
    */
    public function saveBook(string $bookName, string $authors, int $bookPrice, UploadedFile $image, array $idCategories, string $description, $dateRelease, string $language, int $printing, array $images, string $destinationPath)
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
            $stmt->bindValue('name', $bookName, Type::STRING);
            $stmt->bindValue('auth', $authors, Type::STRING);
            $stmt->bindValue('price', $bookPrice, Type::INTEGER);
            $stmt->bindValue('img', $fullPath, Type::STRING);
            $stmt->execute();
            // selecting id book
            $bookId = $this->connection->lastInsertId();
            // Second insert
            unset($stmt);
            $stmt = $this->connection->prepare($queryBuilderToProperties);
            $stmt->bindValue('id', $bookId, Type::INTEGER);
            $stmt->bindValue('desc', $description, Type::STRING);
            $stmt->bindValue('date', $dateRelease, Type::ITEGER);
            $stmt->bindValue('lang', $language, Type::STRING);
            $stmt->bindValue('print', $printing, Type::INTEGER);
            $stmt->bindValue('imgs', $this->serializeArray($pathToImages), Type::STRING);
            $stmt->execute();
            // Third query
            foreach($idCategories as $idCategory){
                $this->connection->insert('categories_has_books', array('categories_category_id' => $idCategory, 'books_book_id' => $bookId));
            }
            // Fourth insert
                //add main category to inserted book
            $idMainCategory = $this->connection->fetchColumn('SELECT c.category_id FROM categories c WHERE c.row = ?', array(0), 0);    
            $this->connection->insert('categories_has_books', array('categories_category_id' => $idMainCategory, 'books_book_id' => $bookId));
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
    *
    * This method keeping synchronisation between filesystem that store images and DB, for update record. 
    *
    * @param integer $bookId
    * @param string $bookName
    * @param string $authors
    * @param string $bookPrice
    * @param UploadedFile $image - file which we will be moved
    * @param array $idCategories 
    * @param string $description
    * @param int $dateRelease
    * @param $language
    * @param int $printing
    * @param string $pathToImages - path to images directory for move
    *
    * @return boolean - successefully or not 
    */
    public function updateBook(int $bookId, string $bookName, string $authors, int $bookPrice, $image, array $idCategories, string $description, $dateRelease, string $language, int $printing, string $pathToImages)
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
            //echo $queryBuilderToBook;
            $queryBuilderToProperties = $this->connection->createQueryBuilder();
            $queryBuilderToProperties
                ->update('books_properties', 'bp')
                ->set('bp.description', ':desc')
                ->set('bp.date_of_release', ':date')
                ->set('bp.language', ':lang')
                ->set('bp.printing', ':print')
                ->where('bp.books_book_id = :p_id');
            $qbToDeleteCategories = $this->connection->createQueryBuilder();
            // DQL does not support joins in DELETE 
            $queryToDeleteCategories = 'DELETE categories_has_books FROM categories_has_books 
                LEFT JOIN categories c ON categories_has_books.categories_category_id = c.category_id 
                WHERE c.row != 0 
                AND categories_has_books.books_book_id = :bookId';
            // Change preview image if it was passed
            if ($image instanceof UploadedFile){
                $queryBuilderToBook->set('b.preview_img', ':img');
                $imageName = md5(microtime()) . '.' . $this->checkExtension($image);
                $directory = $this->generatePathToImage();
                $fullPath = $directory . DIRECTORY_SEPARATOR . $imageName;

                $this->connection->beginTransaction();
                $oldImage = $this->connection->fetchAssoc('SELECT b.book_id, b.preview_img FROM books b WHERE b.book_id = ?', array($bookId));
                if ( ! $oldImage['book_id'] )
                    throw new \Exception("Book with id $bookId doesn't exist");
                $stmt = $this->connection->prepare($queryBuilderToBook);
                $stmt->bindValue('img', $fullPath, Type::STRING);
            }elseif($image === null){
                $this->connection->beginTransaction();
                $stmt = $this->connection->prepare($queryBuilderToBook);
            }else{
                throw new \InvalidArgumentException('Passed file should be null or instance of ' . UploadedFile::class);
            }
            // For book
            $stmt->bindValue('name', $bookName, Type::STRING);
            $stmt->bindValue('auth', $authors, Type::STRING);
            $stmt->bindValue('price', $bookPrice, Type::INTEGER);
            $stmt->bindValue('id', $bookId, Type::INTEGER);
            $stmt->execute();
            unset($stmt);
            // For property book
            $stmt = $this->connection->prepare($queryBuilderToProperties);
            $stmt->bindValue('desc', $description, Type::STRING);
            $stmt->bindValue('date', $dateRelease, Type::INTEGER);
            $stmt->bindValue('lang', $language, Type::STRING);
            $stmt->bindValue('print', $printing, Type::INTEGER);
            $stmt->bindValue('p_id', $bookId, Type::INTEGER);
            $stmt->execute();
            unset($stmt);
            // Delete all categories
            $stmt = $this->connection->prepare($queryToDeleteCategories);
            $stmt->bindValue('bookId', $bookId, Type::INTEGER);
            $stmt->execute();
            // Update book categories
            foreach($idCategories as $idCategory){
                $this->connection->insert('categories_has_books', array('categories_category_id' => $idCategory, 'books_book_id' => $bookId), array(Type::INTEGER, Type::INTEGER));
            }
            // delete old image and move new image if it was passed
            if($image instanceof UploadedFile){
                $pathToImages = rtrim($pathToImages, '\\');
                if( ! @unlink($pathToImages . DIRECTORY_SEPARATOR . $oldImage['preview_img']))
                    throw new \Exception("Can't delete file - " . $pathToImages . DIRECTORY_SEPARATOR . $oldImage['preview_img']);
                $image->move($pathToImages . DIRECTORY_SEPARATOR . $directory, $imageName);
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
    * @return array
    */
    protected function getEntityDescription():array
    {
        return array(
            Book::class => [
                new FieldDescription('id', 'book_id', Type::INTEGER),
                new FieldDescription('name', 'book_name', Type::STRING),
                new FieldDescription('authors', 'authors', Type::STRING),
                new FieldDescription('price', 'price', Type::INTEGER),
                new FieldDescription('image', 'preview_img', Type::STRING),
            ],
            BookDetail::class => [
                new FieldDescription('id', 'book_id', Type::INTEGER),
                new FieldDescription('name', 'book_name', Type::STRING),
                new FieldDescription('authors', 'authors', Type::STRING),
                new FieldDescription('price', 'price', Type::INTEGER),
                new FieldDescription('image', 'preview_img', Type::STRING),
                new FieldDescription('description', 'description', Type::STRING),
                new FieldDescription('dateRelease', 'date_of_release', Type::DATE),
                new FieldDescription('language', 'language', Type::STRING),
                new FieldDescription('printing', 'printing', Type::INTEGER),
                new FieldDescription('booksImages', 'books_img', Type::STRING),
                new FieldDescription('bookCategories', 'categories', Type::STRING),
            ],
        );
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
    * Serialize array to save in DB
    *
    * @param array $array - array of paths 
    *
    * @return string
    */
    protected function serializeArray(array $array)
    {
        $string = '';
        foreach($array as $key => $path){
            $string .= $key . ':' . $path . ';';
        }
        return rtrim($string, ';');
    }
    /**
    * Unserialize sting from DB
    *
    * @param string $string 
    *
    * @return array
    */
    protected function unserializeString(string $string)
    {
        $pathArray = array();
        $array = explode(';', $string);
        foreach($array as $substring){
            $field = explode(':', $substring);
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
    protected function prepareResult(array $fieldDescription, array $result)
    {
        foreach($fieldDescription as $description){
            switch($description->getProperty()){
                case 'price':
                    $result[$description->getDBField()] = $result[$description->getDBField()] / 100;
                    break;
                case 'booksImages':
                    $result[$description->getDBField()] = $this->unserializeString($result[$description->getDBField()]);
                    break;
                case 'bookCategories':
                    $result[$description->getDBField()] = $this->unserializeString($result[$description->getDBField()]);
                    break;
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
