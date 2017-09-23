<?php
session_start();
require_once ("assist/functions.php");

$error = "";

if (isPost()) {
    $database = 'global';

    try {
        $user = "root";
        $pdo = new PDO("mysql:host=localhost;dbname=$database;charset=utf8", $user);
    }
    catch (PDOException $e) {
        $user = "estukalov";
        $password = "neto1205";
        $pdo = new PDO("mysql:host=localhost;dbname=$database;charset=utf8", $user, $password);
    }

    if (isset($_POST['register']))
    {
        if (empty($_POST['login']) || empty($_POST['password'])) {
            $error = "Ошибка регистрации. Введите все необхдоимые данные.";
        }
        else {
            $sql = "SELECT login FROM user WHERE login=:login";
            $statement = $pdo->prepare($sql);
            $statement->execute(['login' => $_POST['login']]);

            if ($statement->fetchColumn()) {
                $error = 'Такой пользователь уже существует в базе данных.';
            }
        }

        if (empty($error)) {
            $sql = "INSERT INTO user ( login, password ) VALUES (:login, :password);";
            $statement = $pdo->prepare($sql);
            $statement->execute(['login' => $_POST['login'], 'password' => md5($_POST['password'])]);
        }

    }

    if (isset($_POST['sign_in'])) {

        if (empty($_POST['login']) || empty($_POST['password'])) {
            $error = "Ошибка входа. Введите все необходимые данные.";
        }
        else {
            $sql = "SELECT id, login FROM user WHERE login=:login AND password=:password";
            $statement = $pdo->prepare($sql);
            $statement->execute(['login' => $_POST['login'], 'password' => md5($_POST['password'])]);

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $error = "Такой пользователь не существует, либо неверный пароль.";
            }
            else {
                $_SESSION['id'] = $row['id'];
                $_SESSION['login'] = $row['login'];
                header('Location: index.php');
            }

        }
    }

}

?>

<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <title>Регистрация</title>
</head>
<body>
    <p><?=empty($error) ? 'Введите данные для регистрации или войдите, если уже регистрировались:' : $error?></p>

    <form method="POST">
        <input type="text" name="login" placeholder="Логин" />
        <input type="password" name="password" placeholder="Пароль" />
        <input type="submit" name="sign_in" value="Вход" />
        <input type="submit" name="register" value="Регистрация" />
    </form>
</body>
</html>
