<html>
    <head></head>
    <tittle></tittle>
    <body>
        <form action="" method="post">
            <p>
                <label for="categoryName">Имя категории</label>
                <input type="text" name="categoryName" />
            </p>
            <select size="1" name="parentCategory">
               <option disabled>Выберите родительскую категорию</option>
                <?php
                $row = 0;
                foreach($categories as $category){?>
                      <option value="<?= $category['category_id']?>"><?= $category['name_category'] ?></option>
                <?php }?>
            </select>
            <input type="submit">
        </form>
        <hr />

    </body>
</html>