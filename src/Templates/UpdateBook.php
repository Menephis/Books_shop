<html>
    <head></head>
    <tittle></tittle>
<body>
    <form enctype="multipart/form-data" action="" method="POST">
        <p>
            <label for="BookName">Имя книги</label>
            <input type="text" name="BookName" value='<?= $book->getName(); ?>' />
        </p>
        <p>
            <labe for="BookAuthors">Авторы</labe>
            <input type="text" name="BookAuthors" value='<?= $book->getAuthors(); ?>'/>
        </p>
        <p>
            <label for="BookPrice">Цена</label>
            <input type="text" name="BookPrice" value='<?= $book->getPrice(); ?>'>
        </p>
        <p>
            <label for="BookDescription">Описание</label>
            <textarea name="BookDescription" cols="30" rows="5"><?= $book->getDescription(); ?></textarea>
        </p>
        <p>
            <label for="DateOfRelease">Дата выпуска</label>
            <input type="text" name="DateOfRelease" value='<?= $book->getDateRelease(); ?>'>
        </p>
        <p>
            <label for="BookLanguage">Язык</label>
            <input type="text" name="BookLanguage" value='<?= $book->getlanguage(); ?>'>
        </p>
        <p>
            <label for="BookPrinting">Тираж</label>
            <input type="text" name="BookPrinting" value='<?= $book->getPrinting(); ?>'>
        </p>
        <select size="5" name="idCategories[]" multiple>
            <?php
                $bookCategories = $book->getBookCategories();
                $row = 0;
                foreach($categories as $category){?>
                    <option value="<?= $category['category_id']?>" 
                        <?php 
                        if(key_exists($category['category_id'], $bookCategories)){ ?>
                           selected
                        <?php } ?>>
                        <?= $category['name_category'] ?>
                    </option>
                <?php }?>
        </select>
        <p>
            <img src='<?= $this->GetSourse() . DIRECTORY_SEPARATOR . 'images/' . $book->getImage(); ?>'/><br />
            <span> Загрузить новое изображение в каталог </span>
            <input type="hidden" name="MAX_FILE_SIZE" value="5000000" />
            <input name="photo" type="file" /><br />
            <span> Каринка в галерею(не более 3 изображений)</span>
            <input type="hidden" name="MAX_FILE_SIZE" value="5000000" />
            <input name="images[]" type="file" multiple="true" min='0' max='3'/>
        </p>
        <input type="submit">
    </form>
    <hr />

</body>
</html>
