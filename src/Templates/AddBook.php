<!DOCTYPE HTML>
<html>
    <head></head>
    <tittle></tittle>
<body>
    <form enctype="multipart/form-data" action="" method="POST">
        <p>
            <label for="BookName">Имя книги</label>
            <input type="text" name="BookName" value='<?= $viewHelper->getField('BookName') ?>'/>
            <?php  if($viewHelper->isNotValid('BookName')){?>
                <span>Неверно</span>
            <?php }; ?>
        </p>
        <p>
            <labe for="BookAuthors">Авторы</labe>
            <input type="text" name="BookAuthors" value='<?= $viewHelper->getField('BookAuthors') ?>'/>
            <?php  if($viewHelper->isNotValid('BookAuthors')){?>
                <span>Неверно</span>
            <?php }; ?>
        </p>
        <p>
            <label for="BookPrice">Цена</label>
            <input type="text" name="BookPrice" value='<?= $viewHelper->getField('BookPrice') ?>'/>
            <?php  if($viewHelper->isNotValid('BookPrice')){?>
                <span>Цена должна быть числом </span>
            <?php }; ?>
        </p>
        <p>
            <label for="BookDescription">Описание</label>
            <textarea name="BookDescription" cols="30" rows="5"><?= $viewHelper->getField('BookName') ?></textarea>
            <?php  if($viewHelper->isNotValid('BookName')){?>
                <span>Неверно</span>
            <?php }; ?>
        </p>
        <p>
            <label for="DateOfRelease">Дата выпуска</label>
            <input type="text" name="DateOfRelease" value='<?= $viewHelper->getField('DateOfRelease') ?>'/>
            <?php  if($viewHelper->isNotValid('DateOfRelease')){?>
                <span>Неверно</span>
            <?php }; ?>
        </p>
        <p>
            <label for="BookLanguage">Язык</label>
            <input type="text" name="BookLanguage" value='<?= $viewHelper->getField('BookLanguage') ?>'/>
            <?php  if($viewHelper->isNotValid('BookLanguage')){?>
                <span>Неверно</span>
            <?php }; ?>
        </p>
        <p>
            <label for="BookPrinting">Тираж</label>
            <input type="text" name="BookPrinting" value='<?= $viewHelper->getField('BookPrinting') ?>'/>
            <?php  if($viewHelper->isNotValid('BookPrinting')){?>
                <span>Неверно</span>
            <?php }; ?>
        </p>
        
        <select size="5" name="idCategories[]" multiple>
            <option disabled>Выберите категорию</option>
            <?php
               
                $selectedCategories = $viewHelper->getField('idCategories');
                $selectedCategories = $selectedCategories ?? array();
                foreach($categories as $category){?>
                    <option value="<?= $category['category_id']?>"
                       <?php
                        if(in_array($category['category_id'], $selectedCategories)){ ?>
                            selected
                        <?php }?>>
                        <?= $category['name_category'] ?>
                    </option>
                <?php }?>
        </select>
        <?php if( ! $selectedCategories){ ?>
            Не выбрана ни одна категория
        <?php }?>
        <p>
            <span> Каринка в каталог</span>
            <input type="hidden" name="MAX_FILE_SIZE" value="5000000" />
            <input name="photo" type="file" /><br />
            <?php if( ! $validImage ) { ?>
                <span><?= isset($imageError) ? $imageError : '' ;?>  </span>
            <?php }; ?>
        </p>
        <input type="submit">
    </form>
    <hr />

</body>
</html>
