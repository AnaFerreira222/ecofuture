<?php
session_start();
require_once 'config.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id_usuario = $_SESSION['ID'] ?? null;
if (!$id_usuario) {
    // Se não tiver usuário logado, redireciona (ajuste conforme sua lógica)
    header("Location: login.php");
    exit;
}

// Mensagens de sessão (opcional)
$mensagem_sucesso = $_SESSION['sucesso'] ?? null;
$mensagem_erro = $_SESSION['erro'] ?? null;
unset($_SESSION['sucesso'], $_SESSION['erro']);

// --- AÇÕES POST (USUÁRIO + ENDEREÇO) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // ----- CRUD USUÁRIO (edição/exclusão) -----
    if ($acao === 'editar_usuario') {
        try {
            $sql = "UPDATE usuario_sistema SET _Nome=?, EMAIL=?, TEL=?, POTENCIA_TOTAL=?, DATA_INSTALACAO=? WHERE ID=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['_Nome'], $_POST['EMAIL'], $_POST['TEL'],
                $_POST['POTENCIA_TOTAL'] ?: null, $_POST['DATA_INSTALACAO'] ?: null, $id_usuario
            ]);
            $_SESSION['sucesso'] = "Perfil atualizado com sucesso.";
        } catch (PDOException $e) {
            $_SESSION['erro'] = "Erro ao atualizar perfil: " . $e->getMessage();
        }
        header("Location: perfil.php");
        exit;
    }

    if ($acao === 'excluir_usuario') {
        try {
            $stmt = $pdo->prepare("DELETE FROM usuario_sistema WHERE ID=?");
            $stmt->execute([$id_usuario]);
            session_destroy();
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['erro'] = "Erro ao excluir usuário: " . $e->getMessage();
            header("Location: perfil.php");
            exit;
        }
    }

    // ----- CRUD ENDEREÇO (Opção B - 1 endereço por usuário) -----
    if ($acao === 'adicionar_endereco' || $acao === 'editar_endereco') {
        // Recebe campos do formulário de endereço
        $cep = trim($_POST['CEP'] ?? '');
        $rua_nome = trim($_POST['rua'] ?? '');
        $numero = trim($_POST['num'] ?? '');
        $complemento = trim($_POST['complemento'] ?? '');
        $bairro_nome = trim($_POST['bairro'] ?? '');
        $cidade_nome = trim($_POST['cidade'] ?? '');
        $estado_sigla = trim($_POST['estado'] ?? '');

        // Validação mínima
        if (empty($cep) || empty($rua_nome) || empty($numero) || empty($bairro_nome) || empty($cidade_nome) || empty($estado_sigla)) {
            $_SESSION['erro'] = "Preencha todos os campos do endereço (CEP, Rua, Número, Bairro, Cidade, Estado).";
            header("Location: perfil.php");
            exit;
        }

        try {
            $pdo->beginTransaction();

            // --- ESTADO ---
            $stmt = $pdo->prepare("SELECT ID FROM estado WHERE UF_SIGLA = ?");
            $stmt->execute([$estado_sigla]);
            $estado_id = $stmt->fetchColumn();

            if (!$estado_id) {
                $stmt = $pdo->prepare("INSERT INTO estado (UF_SIGLA) VALUES (?)");
                $stmt->execute([$estado_sigla]);
                $estado_id = $pdo->lastInsertId();
            }

            // --- CIDADE ---
            $stmt = $pdo->prepare("SELECT ID FROM cidade WHERE NOME = ? AND FK_ESTADO_ID = ?");
            $stmt->execute([$cidade_nome, $estado_id]);
            $cidade_id = $stmt->fetchColumn();

            if (!$cidade_id) {
                $stmt = $pdo->prepare("INSERT INTO cidade (NOME, FK_ESTADO_ID) VALUES (?, ?)");
                $stmt->execute([$cidade_nome, $estado_id]);
                $cidade_id = $pdo->lastInsertId();
            }

            // --- BAIRRO ---
            $stmt = $pdo->prepare("SELECT ID FROM bairro WHERE NOME = ? AND FK_CIDADE_ID = ?");
            $stmt->execute([$bairro_nome, $cidade_id]);
            $bairro_id = $stmt->fetchColumn();

            if (!$bairro_id) {
                $stmt = $pdo->prepare("INSERT INTO bairro (NOME, FK_CIDADE_ID) VALUES (?, ?)");
                $stmt->execute([$bairro_nome, $cidade_id]);
                $bairro_id = $pdo->lastInsertId();
            }

            // --- RUA ---
            // Tenta localizar rua pelo conjunto de campos (DESC_RUA, NUMERO, CEP, FK_BAIRRO_ID)
            $stmt = $pdo->prepare("SELECT ID FROM rua WHERE DESC_RUA = ? AND NUMERO = ? AND CEP = ? AND FK_BAIRRO_ID = ?");
            $stmt->execute([$rua_nome, $numero, $cep, $bairro_id]);
            $rua_id = $stmt->fetchColumn();

            if (!$rua_id) {
                $stmt = $pdo->prepare("INSERT INTO rua (DESC_RUA, NUMERO, CEP, FK_BAIRRO_ID) VALUES (?, ?, ?, ?)");
                $stmt->execute([$rua_nome, $numero, $cep, $bairro_id]);
                $rua_id = $pdo->lastInsertId();
            }

            if ($acao === 'adicionar_endereco') {
                // Insere endereco apontando para o usuário atual
                $stmt = $pdo->prepare("INSERT INTO endereco (COMPLEMENTO, FK_USUARIO_SISTEMA_ID, FK_RUA_ID) VALUES (?, ?, ?)");
                $stmt->execute([$complemento ?: null, $id_usuario, $rua_id]);
            } else {
                // editar_endereco: atualiza endereco existente do usuário
                // Primeiro busca se existe um endereco para o usuário
                $stmt = $pdo->prepare("SELECT ID FROM endereco WHERE FK_USUARIO_SISTEMA_ID = ?");
                $stmt->execute([$id_usuario]);
                $endereco_id = $stmt->fetchColumn();

                if ($endereco_id) {
                    $stmt = $pdo->prepare("UPDATE endereco SET COMPLEMENTO = ?, FK_RUA_ID = ? WHERE ID = ?");
                    $stmt->execute([$complemento ?: null, $rua_id, $endereco_id]);
                } else {
                    // se não existir, insere
                    $stmt = $pdo->prepare("INSERT INTO endereco (COMPLEMENTO, FK_USUARIO_SISTEMA_ID, FK_RUA_ID) VALUES (?, ?, ?)");
                    $stmt->execute([$complemento ?: null, $id_usuario, $rua_id]);
                }
            }

            $pdo->commit();
            $_SESSION['sucesso'] = ($acao === 'adicionar_endereco') ? "Endereço cadastrado com sucesso." : "Endereço atualizado com sucesso.";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['erro'] = "Erro ao salvar endereço: " . $e->getMessage();
        }

        header("Location: perfil.php");
        exit;
    }

    if ($acao === 'excluir_endereco') {
        try {
            // Apaga apenas o registro de endereco associado ao usuário (não remove ruas/bairros/cidades/estados)
            $stmt = $pdo->prepare("DELETE FROM endereco WHERE FK_USUARIO_SISTEMA_ID = ?");
            $stmt->execute([$id_usuario]);
            $_SESSION['sucesso'] = "Endereço excluído com sucesso.";
        } catch (PDOException $e) {
            $_SESSION['erro'] = "Erro ao excluir endereço: " . $e->getMessage();
        }
        header("Location: perfil.php");
        exit;
    }
}

// --- BUSCAR DADOS DO USUÁRIO ---
$stmt = $pdo->prepare("SELECT * FROM usuario_sistema WHERE ID = ?");
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// --- BUSCAR ENDEREÇO (1 por usuário) com joins para exibir campos legíveis ---
$stmt = $pdo->prepare("
    SELECT e.ID as END_ID, e.COMPLEMENTO,
           r.ID as RUA_ID, r.DESC_RUA, r.NUMERO as RUA_NUMERO, r.CEP,
           b.ID as BAIRRO_ID, b.NOME AS BAIRRO_NOME,
           c.ID as CIDADE_ID, c.NOME AS CIDADE_NOME,
           s.ID as ESTADO_ID, s.UF_SIGLA AS ESTADO_SIGLA
    FROM endereco e
    LEFT JOIN rua r ON e.FK_RUA_ID = r.ID
    LEFT JOIN bairro b ON r.FK_BAIRRO_ID = b.ID
    LEFT JOIN cidade c ON b.FK_CIDADE_ID = c.ID
    LEFT JOIN estado s ON c.FK_ESTADO_ID = s.ID
    WHERE e.FK_USUARIO_SISTEMA_ID = ?
    LIMIT 1
");
$stmt->execute([$id_usuario]);
$endereco = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EcoFuture • Perfil</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
  background-color: #f8f9fa;
  font-family: 'Poppins', sans-serif;
  color: #333;
}
.navbar {
  background-color: #ffffff;
  border-bottom: 1px solid #eaeaea;
}
.navbar-brand {
  color: #4a7c59 !important;
  font-weight: 600;
}
.card-elegant {
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  transition: 0.3s;
}
.card-elegant:hover {
  transform: translateY(-3px);
}
.avatar {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  border: 3px solid #4a7c59;
  object-fit: cover;
  box-shadow: 0 0 10px rgba(74,124,89,0.3);
}
.btn-green {
  background: linear-gradient(90deg, #4a7c59, #6bd07c);
  color: white;
  border: none;
  border-radius: 25px;
  padding: 8px 20px;
  transition: 0.3s;
}
.btn-green:hover {
  background: linear-gradient(90deg, #6bd07c, #4a7c59);
  box-shadow: 0 4px 12px rgba(74,124,89,0.4);
}
.btn-danger {
  border-radius: 25px;
  padding: 8px 20px;
}
.modal-content {
  border-radius: 15px;
  border: 1px solid #d8d8d8;
}
.section-title {
  color: #4a7c59;
  font-weight: 600;
  border-bottom: 2px solid #6bd07c;
  display: inline-block;
  padding-bottom: 5px;
}
.small-muted { color: #666; font-size: .9rem; }
</style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-light sticky-top">
  <div class="container">
    <a class="navbar-brand" href="#"><i class="fa-solid fa-solar-panel me-2"></i>EcoFuture</a>
    <a href="monitora.php" class="btn btn-outline-success btn-sm ms-auto">Voltar</a>
  </div>
</nav>

<div class="container py-5">

  <?php if ($mensagem_sucesso): ?>
    <div class="alert alert-success"><?= htmlspecialchars($mensagem_sucesso) ?></div>
  <?php endif; ?>
  <?php if ($mensagem_erro): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($mensagem_erro) ?></div>
  <?php endif; ?>

  <div class="card card-elegant p-4 text-center">
    <img src="https://i.pravatar.cc/150?u=<?= htmlspecialchars($usuario['_NOME'] ?? $usuario['_Nome'] ?? '') ?>" class="avatar mb-3" alt="avatar">
    <h3 class="fw-semibold"><?= htmlspecialchars($usuario['_NOME'] ?? $usuario['_Nome'] ?? '-') ?></h3>
    <p><i class="fa-solid fa-envelope text-success me-2"></i><?= htmlspecialchars($usuario['EMAIL'] ?? '-') ?></p>
    <p><i class="fa-solid fa-phone text-success me-2"></i><?= htmlspecialchars($usuario['TEL'] ?? '-') ?></p>

    <div class="mt-3">
      <button class="btn btn-green me-2" data-bs-toggle="modal" data-bs-target="#editarUsuarioModal"><i class="fa-solid fa-pen"></i> Editar</button>

      <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este perfil?');">
        <input type="hidden" name="acao" value="excluir_usuario">
       <!--<button class="btn btn-danger"><i class="fa-solid fa-trash"></i> Excluir</button>-->
      </form>
    </div>
  </div>

  <div class="card card-elegant mt-4 p-4">
    <h5 class="section-title"><i class="fa-solid fa-bolt me-2"></i>Dados do Sistema</h5>
    <div class="row mt-3">
      <div class="col-md-4"><strong>Potência Total:</strong> <?= htmlspecialchars($usuario['POTENCIA_TOTAL'] ?? '-') ?> W</div>
      <div class="col-md-4"><strong>Data de Instalação:</strong> <?= htmlspecialchars($usuario['DATA_INSTALACAO'] ?? '-') ?></div>
      <div class="col-md-4"><strong>Status:</strong> <?= (isset($usuario['STATUS']) && $usuario['STATUS']) ? 'Ativo' : 'Inativo' ?></div>
    </div>
  </div>

  <div class="card card-elegant mt-4 p-4">
    <h5 class="section-title"><i class="fa-solid fa-location-dot me-2"></i>Endereço</h5>

    <?php if ($endereco): ?>
      <div class="mt-3 text-start">
        <p class="mb-1"><strong>CEP:</strong> <?= htmlspecialchars($endereco['CEP'] ?? '-') ?></p>
        <p class="mb-1"><strong>Rua:</strong> <?= htmlspecialchars($endereco['DESC_RUA'] ?? '-') ?>, <strong>Nº</strong> <?= htmlspecialchars($endereco['RUA_NUMERO'] ?? '-') ?></p>
        <p class="mb-1"><strong>Complemento:</strong> <?= htmlspecialchars($endereco['COMPLEMENTO'] ?? '-') ?></p>
        <p class="mb-1"><strong>Bairro:</strong> <?= htmlspecialchars($endereco['BAIRRO_NOME'] ?? '-') ?></p>
        <p class="mb-1"><strong>Cidade:</strong> <?= htmlspecialchars($endereco['CIDADE_NOME'] ?? '-') ?> / <?= htmlspecialchars($endereco['ESTADO_SIGLA'] ?? '-') ?></p>
      </div>

      <div class="mt-3">
        <button class="btn btn-green me-2" data-bs-toggle="modal" data-bs-target="#editarEnderecoModal"><i class="fa-solid fa-pen"></i> Editar Endereço</button>

        <form method="POST" class="d-inline" onsubmit="return confirm('Excluir endereço?');">
          <input type="hidden" name="acao" value="excluir_endereco">
          <button class="btn btn-danger"><i class="fa-solid fa-trash"></i> Excluir Endereço</button>
        </form>
      </div>

    <?php else: ?>
      <p class="small-muted mt-3">Você ainda não cadastrou um endereço.</p>
      <div class="mt-3">
        <button class="btn btn-green" data-bs-toggle="modal" data-bs-target="#adicionarEnderecoModal"><i class="fa-solid fa-plus"></i> Adicionar Endereço</button>
      </div>
    <?php endif; ?>

  </div>

</div>

<!-- MODAL EDITAR USUÁRIO -->
<div class="modal fade" id="editarUsuarioModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title text-success"><i class="fa-solid fa-user-pen me-2"></i>Editar Usuário</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="acao" value="editar_usuario">
          <label>Nome</label>
          <input type="text" name="_Nome" class="form-control" value="<?= htmlspecialchars($usuario['_NOME'] ?? $usuario['_Nome'] ?? '') ?>">
          <label class="mt-2">Email</label>
          <input type="email" name="EMAIL" class="form-control" value="<?= htmlspecialchars($usuario['EMAIL'] ?? '') ?>">
          <label class="mt-2">Telefone</label>
          <input type="text" name="TEL" class="form-control" value="<?= htmlspecialchars($usuario['TEL'] ?? '') ?>">
          <label class="mt-2">Potência Total (W)</label>
          <input type="number" name="POTENCIA_TOTAL" class="form-control" value="<?= htmlspecialchars($usuario['POTENCIA_TOTAL'] ?? '') ?>">
          <label class="mt-2">Data Instalação</label>
          <input type="date" name="DATA_INSTALACAO" class="form-control" value="<?= htmlspecialchars($usuario['DATA_INSTALACAO'] ?? '') ?>">
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-green">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL ADICIONAR ENDEREÇO -->
<div class="modal fade" id="adicionarEnderecoModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title text-success"><i class="fa-solid fa-location-dot me-2"></i>Adicionar Endereço</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="acao" value="adicionar_endereco">

          <label>CEP</label>
          <input type="text" name="CEP" class="form-control" required>

          <label class="mt-2">Rua</label>
          <input type="text" name="rua" class="form-control" required>

          <label class="mt-2">Número</label>
          <input type="text" name="num" class="form-control" required>

          <label class="mt-2">Complemento</label>
          <input type="text" name="complemento" class="form-control">

          <label class="mt-2">Bairro</label>
          <input type="text" name="bairro" class="form-control" required>

          <label class="mt-2">Cidade</label>
          <input type="text" name="cidade" class="form-control" required>

          <label class="mt-2">Estado (sigla)</label>
          <input type="text" name="estado" class="form-control" maxlength="2" required placeholder="SP, RJ, MG...">
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-green">Salvar Endereço</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL EDITAR ENDEREÇO -->
<div class="modal fade" id="editarEnderecoModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title text-success"><i class="fa-solid fa-location-dot me-2"></i>Editar Endereço</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="acao" value="editar_endereco">

          <label>CEP</label>
          <input type="text" name="CEP" class="form-control" required value="<?= htmlspecialchars($endereco['CEP'] ?? '') ?>">

          <label class="mt-2">Rua</label>
          <input type="text" name="rua" class="form-control" required value="<?= htmlspecialchars($endereco['DESC_RUA'] ?? '') ?>">

          <label class="mt-2">Número</label>
          <input type="text" name="num" class="form-control" required value="<?= htmlspecialchars($endereco['RUA_NUMERO'] ?? '') ?>">

          <label class="mt-2">Complemento</label>
          <input type="text" name="complemento" class="form-control" value="<?= htmlspecialchars($endereco['COMPLEMENTO'] ?? '') ?>">

          <label class="mt-2">Bairro</label>
          <input type="text" name="bairro" class="form-control" required value="<?= htmlspecialchars($endereco['BAIRRO_NOME'] ?? '') ?>">

          <label class="mt-2">Cidade</label>
          <input type="text" name="cidade" class="form-control" required value="<?= htmlspecialchars($endereco['CIDADE_NOME'] ?? '') ?>">

          <label class="mt-2">Estado (sigla)</label>
          <input type="text" name="estado" class="form-control" maxlength="2" required value="<?= htmlspecialchars($endereco['ESTADO_SIGLA'] ?? '') ?>">
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-green">Salvar Alterações</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Se você quiser abrir automaticamente o modal de editar (por exemplo, depois de post com erro),
     você pode controlar com PHP imprimindo um pequeno script que abre o modal quando necessário. -->

</body>
</html>
