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
            <h2 align='center'> HELLO ADMIN!</h2>
            <h4>BOOKS</h4>
            <ul>
                <li><a href="addbook">add book</a></li>
                <li><a href="delete">delete book</a></li>
                <li><a href="update">update</a></li>
            </ul>
            <hr /> 
            <h4>Category</h4>
            <ul>
                <li><a href="addcategory"> add category</a></li>
                <li><a href="deletecategory"> delete category</a></li>
                <li><a href="movecategory"> move category</a></li>
<!--                <li><a href="changeordercategory"> change order category</a></li>-->
            </ul>
        </div>
        <div id="footer">
        </div>
    </div>
</body>
<html>