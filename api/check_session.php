<?php
require_once "config.php";

header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['user_id'])) {
  echo json_encode([
    'loggedIn' => true,
    'user_id'  => (int)$_SESSION['user_id'],
  ]);
} else {
  echo json_encode([
    'loggedIn' => false,
  ]);
}
