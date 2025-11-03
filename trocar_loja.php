<?php
session_start();
unset($_SESSION['loja_id'], $_SESSION['loja_nome']);
header("Location: form_quantidade.php");
exit;
