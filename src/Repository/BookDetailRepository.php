<?
namespace src\Repository;

use src\Domain\BookDetail;
use Doctrine\DBAL\Types\Type;

class BookDetailRepository extends BookRepository
{
    /**
    * @return string 
    */
    protected function getDomainClass()
    {
        return BookDetail::class;
    }
    /**
    * @return array
    */
    protected function getFieldDescription()
    {
        return [
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
        ];
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
                ->select('b.book_id', 'b.book_name', 'b.authors', 'b.price', 'b.preview_img', 'p.description', 'p.date_of_release', 'p.language', 'p.printing', 'p.books_img')
                ->from('books', 'b')
                ->innerJoin('b', 'books_properties', 'p', 'p.books_book_id = ?')
                ->where('b.book_id = ?');
            $stmt = $this->connection->prepare($queryBuilder);
            $stmt->bindValue(1, $idBook);
            $stmt->bindValue(2, $idBook);
            $stmt->execute();
            $book = $stmt->fetch();
            return $this->objectFromAssoc($book);
        }catch(DBALException $exception)
        {
            throw $exception;
        }
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
            switch($description->getProperty()){
                case 'price':
                    $result[$description->getDBField()] = $result[$description->getDBField()] / 100;
                    break;
                case 'booksImages':
                    $result[$description->getDBField()] = $this->unserializePaths($result[$description->getDBField()]);
                    break;
            }
        }
        return $result;
    }
}
?>