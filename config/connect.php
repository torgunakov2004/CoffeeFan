<?php
$connect = mysqli_connect('localhost', 'root', '', 'profile');
if(!$connect) {
  die('Ошибка подключения к БД');
}