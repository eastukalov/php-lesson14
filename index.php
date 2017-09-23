<?php
require_once ("assist/functions.php");
session_start();

if (alien()) {
    echo '<a href="register.php">Войдите на сайт</a>';
    exit;
}

$database = 'global';

try {
    $user = "root";
    $pdo = new PDO("mysql:host=localhost;dbname=$database;charset=utf8", $user);
}
catch (PDOException $e) {
//    die('Подключение не удалось: ' . $e->getMessage());
//    die('Подключение не удалось: ');
// чтобы не менять логин / пароль для выкладывания на хостинг использую обработку ошибок не совсем по назначению
    $user = "estukalov";
    $password = "neto1205";
    $pdo = new PDO("mysql:host=localhost;dbname=$database;charset=utf8", $user, $password);
}
$array = [];
$description = [];
$order = ' ORDER BY date_added;';
$sort_array = ['date_added', 'is_done', 'description'];
$add_edit = 'add';

if (isPost()) {

    if (isAddEdit() && isset($_POST['var']) & !empty($_POST['var'])) {

        if (isset($_POST['add_edit']) && $_POST['add_edit'] == 'edit' && isset($_GET['id'])) {
            $sql = "UPDATE task SET description = :description WHERE id=:id;";
            $array = ['description'=>htmlspecialchars($_POST['var']), 'id'=>$_GET['id']];
        }
        else {
            $sql = "INSERT INTO task (user_id, assigned_user_id, description, is_done, date_added) VALUES (:user_id, :assigned_user_id, :description, :is_done, :date_added)";
            $array = ['user_id'=>$_SESSION['id'],'assigned_user_id'=>$_SESSION['id'],'description'=>htmlspecialchars($_POST['var']), 'is_done'=>0, 'date_added'=>date('Y.m.d Hi:s:',time())];
        }

        $statement = $pdo->prepare($sql);
        $statement->execute($array);
    }

    if (isSort() && isset($_POST['my_sort']) && !empty($_POST['my_sort']) && in_array($_POST['my_sort'], $sort_array)) {
        $order = ' ORDER BY ' . ($_POST['my_sort']) . ';';
    }

    if (isAssign() && isset($_POST['assigned_user_id']) && !empty($_POST['assigned_user_id'])) {
        $assigned_id = explode('_', $_POST['assigned_user_id']);
        $sql = $sql = "UPDATE task SET assigned_user_id = :assigned_user_id WHERE id=:id;";
        $statement = $pdo->prepare($sql);
        $statement->execute(['assigned_user_id'=>$assigned_id[0], 'id'=>$assigned_id[1]]);
    }

}

if (isset($_GET['action']) && $_GET['action']=='edit' && !isset($_POST['my_sort']) && !isset($_POST['var'])) {
    $add_edit = 'edit';
}

if (isGet()) {

    if (isset($_GET['action'])) {

        if ($_GET['action'] == 'edit') {
            $sql = "SELECT description FROM task WHERE id=:id;";
            $array = ['id'=>$_GET['id']];
        }
        else {

            if ($_GET['action'] == 'delete') {
                $sql = "DELETE FROM task WHERE id=:id;";
                $array = ['id'=>$_GET['id']];
            }
            elseif ($_GET['action'] == 'done') {
                $sql = "UPDATE task SET is_done = :is_done WHERE id=:id;";
                $array = ['is_done'=>1, 'id'=>$_GET['id']];
            }

        }

        $statement = $pdo->prepare($sql);
        $statement->execute($array);

        if ($_GET['action'] == 'edit' & $add_edit == 'edit') {
            $description = $statement -> fetchall(PDO::FETCH_COLUMN, 0);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' & !empty($_POST) & isset($_POST['addedit'])) {
    header("Location: index.php");
    exit;
}

$sql = "SELECT t.id, t.user_id, t.assigned_user_id, t.description, t.is_done, t.date_added, u1.login AS author, u2.login AS assigned 
        FROM task t INNER JOIN user u1 ON t.user_id = u1.id INNER JOIN user u2 ON t.assigned_user_id = u2.id WHERE t.user_id=:id" . $order;

$statement = $pdo->prepare($sql);
$statement->execute(['id'=>$_SESSION['id']]);

while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    $results[] = $row;
}

$sql2 = "SELECT id, login FROM user WHERE id<>:id";

$statement = $pdo->prepare($sql2);
$statement->execute(['id'=>$_SESSION['id']]);

while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    $assigns[] = $row;
}

$sql = "SELECT t.id, t.user_id, t.assigned_user_id, t.description, t.is_done, t.date_added, u1.login AS author, u2.login AS assigned 
        FROM task t INNER JOIN user u1 ON t.user_id = u1.id INNER JOIN user u2 ON t.assigned_user_id = u2.id WHERE t.assigned_user_id=:id And t.user_id<>:id" . $order;

$statement = $pdo->prepare($sql);
$statement->execute(['id'=>$_SESSION['id']]);

while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    $assigned_results[] = $row;
}

?>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <title>SELECT из нескольких таблиц</title>
    <style>
        td {padding: 5px 20px 5px 20px;border: 1px solid black;}
        form.select {margin: 0;}
        thead td {text-align: center;background-color: #dbdbdb;font-weight: 700;}
        table {border-collapse: collapse;border-spacing: 0;}
        .done {margin-right: 20px;}
    </style>
</head>
<body>
    <h1>Здравствуйте, <?=$_SESSION['login']?>! Вот ваш список дел:</h1>

    <div style="float: left">
        <form method='POST'>
            <input type="hidden" name="add_edit" value="<?=$add_edit?>">
            <input type="text" name="var" placeholder='Описание задачи' value="<?=!empty($description)? $description[0]:''?>">
            <input type='submit' value=<?=$add_edit=='edit' ? 'Сохранить' : 'Добавить'?> name='addedit'>
        </form>
    </div>

    <div style="float: left">
        <form method='POST'>
            <label for="sort">Сортировать по:</label>
            <select name="my_sort">
                <option value="date_added">Дате добавления</option>
                <option value="is_done">Статусу</option>
                <option value="description">Описанию</option>
            </select>
            <input type='submit' value='Отсортировать' name='sort'>
        </form>
    </div>

    <div style="clear: both"></div>
    <table>
        <thead>
            <tr>
                <td>Описание задачи</td>
                <td>Дата добавления</td>
                <td>Статус</td>
                <td></td>
                <td>Ответственный</td>
                <td>Автор</td>
                <td>Закрепить задачу за пользователем</td>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($results)) { foreach ($results as $value) :?>
            <tr>
                <td><?=$value['description']?></td>
                <td><?=$value['date_added']?></td>
                <td><span style=<?=!$value['is_done']?'color:orange;':'color:green;'?>><?=!$value['is_done']?'В процессее':'Выполнено'?></span></td>
                <td><a class="done" href=<?="?id=".$value['id']."&action=edit"?>>Изменить</a><?php if ($value['assigned_user_id']==$_SESSION['id']) { ?> <a class="done" href=<?="?id=".$value['id']."&action=done"?>>Выполнить</a><?php }?><a class="done" href=<?="?id=".$value['id']."&action=delete"?>>Удалить</a></td>
                <td><?=$value['assigned_user_id']==$_SESSION['id']?'Вы':$value['assigned']?></td>
                <td><?=$value['author']?></td>
                <td>
                    <form method="POST" class="select">
                        <select name='assigned_user_id'>
                            <?php if (!empty($assigns)) { foreach ($assigns as $assign ) :?>
                                <option value="<?=$assign['id'] . '_' . $value['id']?>"><?=$assign['login']?></option>
                            <?php endforeach; }?>
                        </select>
                        <input type='submit' name='assign' value='Переложить ответственность'>
                    </form>
                </td>
            </tr>
            <?php endforeach; } ?>
        </tbody>
    </table>

    <p><strong>Также, посмотрите, что от Вас требуют другие люди:</strong></p>


    <table>
        <thead>
        <tr>
            <td>Описание задачи</td>
            <td>Дата добавления</td>
            <td>Статус</td>
            <td></td>
            <td>Ответственный</td>
            <td>Автор</td>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($assigned_results)) { foreach ($assigned_results as $value) :?>
            <tr>
                <td><?=$value['description']?></td>
                <td><?=$value['date_added']?></td>
                <td><span style=<?=!$value['is_done']?'color:orange;':'color:green;'?>><?=!$value['is_done']?'В процессее':'Выполнено'?></span></td>
                <td><a class="done" href=<?="?id=".$value['id']."&action=edit"?>>Изменить</a><?php if ($value['assigned_user_id']==$_SESSION['id']) { ?> <a class="done" href=<?="?id=".$value['id']."&action=done"?>>Выполнить</a><?php }?><a class="done" href=<?="?id=".$value['id']."&action=delete"?>>Удалить</a></td>
                <td><?=$value['assigned_user_id']==$_SESSION['id']?'Вы':$value['assigned']?></td>
                <td><?=$value['author']?></td>
            </tr>
        <?php endforeach; } ?>
        </tbody>
    </table>

    <p><a href="logout.php">Выход</a></p>

</body>
</html>