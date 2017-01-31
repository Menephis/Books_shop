<html>

<head></head>
<tittle></tittle>

<body>
    <form action="" method="post">
        <h4>Выберите категорию для перемещения </h4>
        <select size="1" name="idCategory">
            <option disabled>Выберите категорию</option>
            <?php
                $row = 0;
                foreach($categories as $category){?>
                <option value="<?= $category['category_id']?>">
                    <?= $category['name_category'] ?>
                </option>
                <?php }?>
        </select>
        <h4>Выберите новую родительскую категорию</h4>
        <hr />
        <select size="1" name="idParentCategory">
            <option disabled>Выберите новую родительскую категорию</option>
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