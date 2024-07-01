<?php
session_start();
if(!isset($_SESSION["user"])){header("Location: login.php");exit;}
$pdo = new PDO("mysql:host=localhost;dbname=cron_dash;charset=utf8","root","");

$action = $_GET["action"] ?? "dashboard";

if ($action === "toggle") {
    $stmt = $pdo->prepare("UPDATE tarefas SET ativo = NOT ativo WHERE id=?");
    $stmt->execute([$_GET["id"]]);
    header("Location: index.php"); exit;
}
if ($action === "deletar") {
    $pdo->prepare("DELETE FROM tarefas WHERE id=?")->execute([$_GET["id"]]);
    header("Location: index.php"); exit;
}
if ($action === "executar") {
    $stmt = $pdo->prepare("INSERT INTO logs (tarefa_id, status, saida) VALUES (?, 'executando', ?)");
    $stmt->execute([$_GET["id"], "Iniciado manualmente em ".date("d/m/Y H:i:s")]);
    $pdo->prepare("UPDATE tarefas SET ultima_exec=NOW() WHERE id=?" )->execute([$_GET["id"]]);
    header("Location: index.php"); exit;
}
if ($_SERVER["REQUEST_METHOD"]==="POST" && isset($_POST["nome"])) {
    if (!empty($_POST["id"])) {
        $stmt = $pdo->prepare("UPDATE tarefas SET nome=?,comando=?,intervalo=?,descricao=? WHERE id=?");
        $stmt->execute([$_POST["nome"],$_POST["comando"],$_POST["intervalo"],$_POST["descricao"],$_POST["id"]]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO tarefas (nome,comando,intervalo,descricao) VALUES (?,?,?,?)");
        $stmt->execute([$_POST["nome"],$_POST["comando"],$_POST["intervalo"],$_POST["descricao"]]);
    }
    header("Location: index.php"); exit;
}

$tarefas = $pdo->query("SELECT t.*,(SELECT MAX(criado_em) FROM logs WHERE tarefa_id=t.id) as ultimo_log FROM tarefas t ORDER BY t.id DESC")->fetchAll();
$stats = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN ativo=1 THEN 1 ELSE 0 END) as ativos, COUNT(CASE WHEN ultima_exec >= NOW() - INTERVAL 1 DAY THEN 1 END) as hoje FROM tarefas")->fetch();
$logs = $pdo->query("SELECT l.*, t.nome FROM logs l JOIN tarefas t ON l.tarefa_id=t.id ORDER BY l.criado_em DESC LIMIT 20")->fetchAll();
?>
<!DOCTYPE html><html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Cron Dashboard</title>
<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:Arial;background:#f5f6fa;color:#333}
header{background:#2c3e50;color:white;padding:15px 30px;display:flex;justify-content:space-between;align-items:center}
header h1{font-size:1.3em}header a{color:white;text-decoration:none}
.container{max-width:1000px;margin:20px auto;padding:0 20px}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin-bottom:20px}
.stat{background:white;padding:20px;border-radius:8px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.08)}
.stat h2{font-size:2.5em}.stat p{color:#999}.topo{display:flex;justify-content:space-between;align-items:center;margin-bottom:15px}
.btn{display:inline-block;background:#3498db;color:white;padding:10px 20px;border-radius:4px;text-decoration:none;font-size:0.9em}
.btn-success{background:#27ae60}.btn-warning{background:#e67e22}
table{width:100%;border-collapse:collapse;background:white;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08)}
th,td{padding:12px;text-align:left;border-bottom:1px solid #eee}th{background:#f8f9fa;font-weight:600}
.badge{padding:3px 10px;border-radius:10px;font-size:0.85em;color:white}
.badge-on{background:#27ae60}.badge-off{background:#95a5a6}
.small{font-size:0.85em;color:#999}.actions a{margin-right:8px;color:#3498db;text-decoration:none;font-size:0.9em}
.actions .del{color:#e74c3c}
h2{margin:25px 0 10px}form{background:white;padding:20px;border-radius:8px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,0.08)}
input,select,textarea{width:100%;padding:8px;margin:5px 0;border:1px solid #ddd;border-radius:4px}
textarea{height:60px}button{background:#27ae60;color:white;border:none;padding:10px 25px;border-radius:4px;cursor:pointer}
.log-list{margin-top:10px}.log-item{background:white;padding:10px 15px;margin-bottom:5px;border-radius:4px;border-left:3px solid #3498db;font-size:0.9em}
.log-item .data{color:#999;margin-right:15px}.log-item .nome{font-weight:bold}
footer{text-align:center;padding:20px;color:#999;font-size:0.9em}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000}
.modal-content{background:white;margin:10% auto;padding:30px;max-width:500px;border-radius:8px}
.modal-content h2{margin-bottom:15px}.fechar{float:right;cursor:pointer;font-size:1.5em}
</style></head>
<body>
<header><h1>Cron Dashboard</h1><a href="login.php?logout=1">Sair</a></header>
<div class="container">
<div class="stats"><div class="stat"><h2><?=$stats["total"]?></h2><p>Tarefas</p></div>
<div class="stat"><h2><?=$stats["ativos"]?></h2><p>Ativas</p></div>
<div class="stat"><h2><?=$stats["hoje"]?></h2><p>Executadas hoje</p></div></div>
<div class="topo"><h2>Tarefas</h2><a href="#" class="btn" onclick="abrirModal()">+ Nova Tarefa</a></div>
<table><tr><th>Nome</th><th>Comando</th><th>Intervalo</th><th>Status</th><th>Ultima exec</th><th>Acoes</th></tr>
<?php foreach($tarefas as $t):?><tr>
<td><?=htmlspecialchars($t["nome"])?></td><td><code><?=htmlspecialchars($t["comando"])?></code></td>
<td><?=$t["intervalo"]?></td>
<td><span class="badge badge-<?=$t["ativo"]?"on":"off"?>"><?=$t["ativo"]?"Ativa":"Pausada"?></span></td>
<td><?=$t["ultima_exec"]?date("d/m/Y H:i",strtotime($t["ultima_exec"])):"Nunca"?></td>
<td class="actions">
<a href="?action=toggle&id=<?=$t["id"]?>"><?=$t["ativo"]?"Pausar":"Ativar"?></a>
<a href="#" onclick="editar(<?=$t["id"]?>,'<?=addslashes($t["nome"])?>','<?=addslashes($t["comando"])?>','<?=addslashes($t["intervalo"])?>','<?=addslashes($t["descricao"])?>')">Editar</a>
<a href="?action=executar&id=<?=$t["id"]?>">Executar</a>
<a href="?action=deletar&id=<?=$t["id"]?>" class="del" onclick="return confirm('Deletar?')">Deletar</a>
</td></tr><?php endforeach;?></table>

<h2>Logs Recentes</h2>
<div class="log-list"><?php foreach($logs as $l):?><div class="log-item">
<span class="data"><?=date("d/m/Y H:i",strtotime($l["criado_em"]))?></span>
<span class="nome">[<?=htmlspecialchars($l["nome"])?>]</span>
<span><?=htmlspecialchars(mb_substr($l["saida"],0,80))?></span></div>
<?php endforeach;?></div>
<footer>Cron Dashboard v1.0</footer></div>

<!-- Modal Nova/Editar -->
<div id="modal" class="modal"><div class="modal-content">
<span class="fechar" onclick="fecharModal()">&times;</span>
<h2 id="modal-titulo">Nova Tarefa</h2>
<form method="POST" id="form-tarefa">
<input type="hidden" name="id" id="tarefa-id">
<input type="text" name="nome" id="tarefa-nome" placeholder="Nome da tarefa" required>
<input type="text" name="comando" id="tarefa-comando" placeholder="Comando (ex: php /caminho/script.php)" required>
<select name="intervalo" id="tarefa-intervalo" required>
<option value="*/5 * * * *">A cada 5 min</option>
<option value="*/15 * * * *">A cada 15 min</option>
<option value="0 * * * *">A cada hora</option>
<option value="0 0 * * *">Todo dia meia-noite</option>
<option value="0 9 * * 1-5">Dias uteis as 9h</option>
<option value="0 0 1 * *">Todo dia 1 do mes</option></select>
<textarea name="descricao" id="tarefa-descricao" placeholder="Descricao"></textarea>
<button type="submit">Salvar</button></form></div></div>

<script>
function abrirModal(){document.getElementById("modal").style.display="block";
document.getElementById("modal-titulo").textContent="Nova Tarefa";
document.getElementById("tarefa-id").value="";
document.getElementById("tarefa-nome").value="";
document.getElementById("tarefa-comando").value="";
document.getElementById("tarefa-descricao").value="";}
function fecharModal(){document.getElementById("modal").style.display="none";}
function editar(id,nome,comando,intervalo,desc){
document.getElementById("modal").style.display="block";
document.getElementById("modal-titulo").textContent="Editar Tarefa";
document.getElementById("tarefa-id").value=id;
document.getElementById("tarefa-nome").value=nome;
document.getElementById("tarefa-comando").value=comando;
document.getElementById("tarefa-descricao").value=desc;}
window.onclick=function(e){if(e.target==document.getElementById("modal"))fecharModal();}
</script></body></html>
