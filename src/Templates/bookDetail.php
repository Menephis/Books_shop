<!DOCTYPE html>
<html>

<head>
    <title>Book Shop</title>
    <link rel="stylesheet" href="<?= $this->GetSourse() ?>/css/style.css" type="text/css" media="screen" />
</head>

<body>
    <div class="all">
        <div id="header">
        </div>
        <div id="content">
            <div class="book">
                <h4><?= $book->getName(); ?></h4>
                <img src='<?= $this->GetSourse() . DIRECTORY_SEPARATOR . 'images/' . $book->getImage();?>'><br />
                <span>Дата выпуска: <?= $book->getDateRelease(); ?></span><br />
                <span> Автор: <?= $book->getAuthors();?><br /> Язык: <?= $book->getlanguage(); ?></span><br />
                <span> Дата выпуска : </span><?php $book->getDateRelease(); ?>
                <h3>Категории книги:</h3>
                <ul>
                <?php
                    foreach($book->getBookCategories() as $category){
                        ?>
                        <li><?= $category; ?></li>
                        <?php
                    }
                ?>
                </ul>
            </div>
        </div>
        <div id="footer">
        </div>
    </div>
</body>
<html>