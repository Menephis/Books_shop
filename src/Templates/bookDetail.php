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
                <img src='<?= $this->GetSourse() . DIRECTORY_SEPARATOR . 'images/' . $book->getImage();?>'>
                <span>Дата выпуска: <?= $book->getDateRelease(); ?></span><br />
                <span> Автор: <?= $book->getAuthors();?> Язык: <?= $book->getlanguage(); ?></span>
                <h3>Дополнительные изображения</h3>
                <?php 
                    foreach($book->getBookImages() as $image){
                        ?>
                        <img src='<?= $this->GetSourse() . DIRECTORY_SEPARATOR . 'images/' . $image;?>'>
                        <?php   
                    }
                ?>
            </div>
        </div>
        <div id="footer">
        </div>
    </div>
</body>
<html>