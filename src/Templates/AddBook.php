<html>
    <head></head>
    <tittle></tittle>
<body>
    <form enctype="multipart/form-data" action="" method="POST">
        <p>
            <label for="BookName">Имя книги</label>
            <input type="text" name="BookName" />
        </p>
        <p>
            <labe for="BookAuthors">Авторы</labe>
            <input type="text" name="BookAuthors" />
        </p>
        <p>
            <label for="BookPrice">Цена</label>
            <input type="text" name="BookPrice">
        </p>
        <p>
            <label for="BookDescription">Описание</label>
            <textarea name="BookDescription" cols="30" rows="5"></textarea>
        </p>
        <p>
            <label for="DateOfRelease">Дата выпуска</label>
            <input type="text" name="DateOfRelease">
        </p>
        <p>
            <label for="BookLanguage">Язык</label>
            <input type="text" name="BookLanguage">
        </p>
        <p>
            <label for="BookPrinting">Тираж</label>
            <input type="text" name="BookPrinting">
        </p>
        <select size="5" name="idCategories[]" multiple>
            <option disabled>Выберите категорию</option>
            <?php
                $row = 0;
                foreach($categories as $category){?>
                    <option value="<?= $category['category_id']?>">
                        <?= $category['name_category'] ?>
                    </option>
                <?php }?>
        </select>
        <p>
            <span> Каринка в каталог</span>
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
