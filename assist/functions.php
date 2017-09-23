<?php

function alien() {
    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
        return false;
    }

    return true;
}

function isPost () {
    return $_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST);
}

function isGet () {
    return $_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_GET);
}

function isAddEdit () {
    return isset($_POST['addedit']);
}

function isSort () {
    return isset($_POST['sort']);
}

function isAssign () {
    return isset($_POST['assign']);
}
