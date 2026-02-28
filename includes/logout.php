<?php
session_start();
require_once 'config/database.php';

session_destroy();
redirect('../signin.php');
