<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinição de senha</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f9fafb; margin: 0; padding: 0; }
        .wrapper { max-width: 520px; margin: 40px auto; background: #fff; border-radius: 16px; border: 1px solid #e5e7eb; overflow: hidden; }
        .header { background: #e11d48; padding: 32px 40px; text-align: center; }
        .header h1 { color: #fff; font-size: 20px; font-weight: 700; margin: 0; }
        .body { padding: 32px 40px; }
        .body p { color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
        .btn { display: inline-block; background: #e11d48; color: #fff; text-decoration: none; font-weight: 600; font-size: 15px; padding: 14px 32px; border-radius: 12px; margin: 8px 0 24px; }
        .footer { padding: 16px 40px 32px; }
        .footer p { color: #9ca3af; font-size: 13px; line-height: 1.5; margin: 0; }
        .footer a { color: #e11d48; word-break: break-all; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Gestão Beauty</h1>
        </div>
        <div class="body">
            <p>Olá, <strong>{{ $user->name }}</strong>!</p>
            <p>Recebemos uma solicitação para redefinir a senha da sua conta. Clique no botão abaixo para criar uma nova senha:</p>
            <p style="text-align:center">
                <a href="{{ $link }}" class="btn">Redefinir minha senha</a>
            </p>
            <p>Este link expira em <strong>60 minutos</strong>. Se você não solicitou a redefinição, ignore este e-mail — sua senha permanece a mesma.</p>
        </div>
        <div class="footer">
            <p>Se o botão não funcionar, copie e cole este link no navegador:</p>
            <p><a href="{{ $link }}">{{ $link }}</a></p>
        </div>
    </div>
</body>
</html>
