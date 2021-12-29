<form action="" method="post" enctype='multipart/form-data' xmlns="http://www.w3.org/1999/html">
    <label for="file">
        загрузите файл: <br>
        Файл должен содержать столбцы 'email' и 'phone'. <br>
        Файл должен иметь расширение .xlsx
    </label>
    <input type="file" id="file" name="file">

    <label for="nick">Введите никнейм в Телеграмм (вместе с @)</label>
    <input type="text" id="nick" name="nick">

    <button type="submit">Оправить</button>
</form>