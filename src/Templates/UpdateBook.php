<html>
    <head></head>
    <tittle></tittle>
<body>
    <form enctype="multipart/form-data" action="" method="POST">
        <p>
            <label for="updateBook">Books ID for update</label>
            <input type="text" name="updateBook" />
        </p>
        <p>
            <label for="BookName">Имя книги</label>
            <input type="text" name="BookName" />
        </p>
        <p>
            <labe for="BookDes">Description</labe>
            <textarea rows="10" cols="45" name="BookDes"></textarea>
        </p>
        <p>
            <label for="BookPrice">Price</label>
            <input type="text" name="BookPrice">
        </p>
        <p>
            <!-- Поле MAX_FILE_SIZE должно быть указано до поля загрузки файла -->
            <input type="hidden" name="MAX_FILE_SIZE" value="5000000" />
            <!-- Название элемента input определяет имя в массиве $_FILES -->
            <input name="photo" type="file" />
        </p>
        <input type="submit">
    </form>
    <hr />

</body>
</html>
