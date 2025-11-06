<?php
require '../config.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Gera token único e expira em 1 hora
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $pdo->prepare("INSERT INTO usuarios_tokens (usuario_id, token, expira_em) VALUES (?, ?, ?)")
            ->execute([$user['id'], $token, $expira]);

        // Envia e-mail com link de redefinição
        $link = "http://localhost/Controle_lojas/sections/redefinir_senha.php?token=$token";

        $mail = new PHPMailer(true);


        try {
            // CONFIGURAÇÃO SMTP (ajuste com seus dados)
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'dexter.craus@gmail.com';
            $mail->Password = 'zqvg rxzw rwpy kirt';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // 🔹 Configuração de codificação
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            $mail->setFrom('dexter.craus@gmail.com', 'Controle de Lojas');
            $mail->addAddress($email, $user['nome']);
            $mail->isHTML(true);
            $mail->Subject = 'Redefinição de senha - Controle de Lojas';
            $mail->Body = "
<!DOCTYPE html>
<html lang='pt-br'>
<head>
<meta charset='UTF-8'>
<style>
body {
    background: #f4f4f4;
    font-family: Arial, sans-serif;
    color: #333;
    padding: 0;
    margin: 0;
}
.email-container {
    background: #ffffff;
    max-width: 600px;
    margin: 30px auto;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}
.header {
    background: linear-gradient(135deg, #0d6efd, #20c997);
    padding: 20px;
    text-align: center;
    color: white;
}
.header img {
    width: 100px;
    margin-bottom: 10px;
}
.content {
    padding: 30px;
    text-align: center;
}
.button {
    background-color: #0d6efd;
    color: white !important;
    padding: 12px 25px;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    display: inline-block;
    margin-top: 20px;
}
.footer {
    text-align: center;
    font-size: 13px;
    color: #777;
    padding: 15px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
}
</style>
</head>
<body>
<div class='email-container'>
    <div class='header'>
        <a href='https://ibb.co/RkKKKDmv'><img src='https://i.ibb.co/RkKKKDmv/dl-apps-logo.png' alt='dl-apps-logo' border='0'></a>
        <h2>Controle de Lojas</h2>
    </div>

    <div class='content'>
        <h3>Olá, {$user['nome']}!</h3>
        <p>Você solicitou a redefinição da sua senha no <strong>Controle de Lojas</strong>.</p>
        <p>Para criar uma nova senha, clique no botão abaixo:</p>

        <a href='{$link}' class='button'>Redefinir minha senha</a>

        <p style='margin-top: 25px; font-size: 14px; color: #666;'>
            Este link é válido por apenas <strong>1 hora</strong>.<br>
            Caso não tenha feito esta solicitação, ignore este e-mail.
        </p>
    </div>

    <div class='footer'>
        © " . date('Y') . " Controle de Lojas. Todos os direitos reservados.<br>
        <a href='http://localhost/Controle_lojas/' style='color:#0d6efd;'>Acesse o sistema</a>
    </div>
</div>
</body>
</html>
";


            $mail->send();
            $msg = 'Enviamos um link de redefinição para seu e-mail.';
        } catch (Exception $e) {
            $msg = 'Erro ao enviar o e-mail: ' . $mail->ErrorInfo;
        }
    } else {
        $msg = 'E-mail não encontrado.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Esqueci minha senha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #ff6b6b, #f06595);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 400px;
        }
    </style>
</head>

<body>
    <section class="card">
        <h4 class="text-center text-danger mb-3">Redefinir Senha</h4>
        <?php if ($msg): ?>
            <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label>E-mail cadastrado:</label>
                <input type="email" name="email" class="form-control" required autofocus>
            </div>
            <button type="submit" class="btn btn-danger w-100">Enviar link</button>
            <div class="text-center mt-3">
                <a href="login.php">Voltar ao Login</a>
            </div>
        </form>
    </section>
</body>

</html>