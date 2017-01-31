<html>

<head></head>
<tittle></tittle>

<body>
    <form action="" method="post">
        <h2> Выберите категорию для удаления</h2>
        <select size="1" name="idCategory">
            <option disabled>Выберите родительскую категорию</option>
            <?php
                $row = 0;
                foreach($categories as $category){?>
                <option value="<?= $category['category_id']?>">
                    <?= $category['name_category'] ?>
                </option>
                <?php }?>
        </select>
        <input type="submit">
    </form>
    <hr />

</body>

</html>