<?php
require __DIR__ . '/config/bootstrap.php';
unset($_SESSION['user']);
session_regenerate_id(true);
redirect('index.php');
