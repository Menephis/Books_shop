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
            <a href="/books-shop/web/admin/"> Админка</a>
            <div class="blocks">
                <?php
                    $row = 0;
                    foreach($categories as $category){

                        if($row < $category['row']){
                            $row = $category['row'];
                            echo "<ul>";
                        }elseif($row > $category['row']){
                            $count = (int)$row - (int)$category['row'];
                            for($i = 0; $i < $count; $i++){
                                echo "</ul>";
                            }
                            $row = $category['row'];
                        }
                        ?>
                    <li>
                        <a href="/books-shop/web/catalog/<?= $category['category_id']?>">
                            <?= $category['name_category']?>
                        </a>
                    </li>
                    <?php }?>
            </div>
            <div class="blocks">
                <ol>
                    <?php
                    foreach($books as $book){ 
                        ?>
                        <li class='books' >
                            <p>
                                <a href="detail/<?= $book->getId();?>"><?= $book->getName();?></a><br /> 
                                <img src='<?= $this->GetSourse() . DIRECTORY_SEPARATOR . 'images/' . $book->getImage();?>'>
                            </p><br />
                            <span> Цена: <?= $book->getPrice();?>"</span><br />
                            <?php
                            if($authorizationChecker->isGranted('ROLE_ADMIN')){
                            ?>
                                <a href='/books-shop/web/admin/update/<?= $book->getId();?>'>Редактировать</a><br />
                                <a href='/books-shop/web/admin/delete/<?= $book->getId();?>'>Удалить</a>
                            <?php 
                            }?>
                        </li>
                        
                    
                    <?php 
                    }
                    ?>
                </ol>
            </div>
        </div>
        <div id="footer">
        </div>
    </div>
</body>
<html>