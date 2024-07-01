<?php session_start();
if(isset($_GET["logout"])){session_destroy();header("Location: login.php");exit;}
if($_SERVER["REQUEST_METHOD"]==="POST"){
if($_POST["user"]==="admin"&&$_POST["pass"]==="cron2024"){
$_SESSION["user"]="admin";header("Location: index.php");exit;}
$erro="Credenciais invalidas";}
?><!DOCTYPE html><html lang="pt-BR">
<head><meta charset="UTF-8"><title>Login - Cron</title>
<style>body{font-family:Arial;display:flex;justify-content:center;align-items:center;height:100vh;background:#f5f6fa}
.card{background:white;padding:40px;border-radius:8px;width:360px;box-shadow:0 2px 10px rgba(0,0,0,0.1)}
h1{text-align:center;margin-bottom:20px}input{width:100%;padding:12px;margin:8px 0;border:1px solid #ddd;border-radius:4px}
button{width:100%;padding:12px;background:#2c3e50;color:white;border:none;border-radius:4px;cursor:pointer}
.erro{color:#e74c3c;text-align:center}</style></head>
<body><div class="card"><h1>Cron Dashboard</h1>
<?php if(isset($erro)):?><div class="erro"><?=$erro?></div><?php endif;?>
<form method="POST"><input type="text" name="user" placeholder="Usuario" required>
<input type="password" name="pass" placeholder="Senha" required>
<button type="submit">Entrar</button></form></div></body></html>
