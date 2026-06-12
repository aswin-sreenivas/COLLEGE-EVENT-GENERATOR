<?php
session_start();
session_regenerate_id(true);

$conn = new mysqli("localhost", "root", "", "campus_connect");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>