<?php
if (!isset($user)) {
    $user = isLoggedIn() ? getCurrentUser() : null;
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
:root {
  --cream:        #fffcf0;
  --ink:          #0f0e17;
  --indigo:       #1e2557;
  --indigo-mid:   #2d3a8c;
  --amber:        #e8a020;
  --muted:        #6b6a75;
  --border:       #e2dfd6;
  --sand:         #f4f1e8;
}
</style>