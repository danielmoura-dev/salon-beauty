<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirme seu E-mail — Salon Beauty</title>
</head>
<body style="margin:0;padding:0;background-color:#eaf6f5;font-family:'Segoe UI',Arial,Helvetica,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
       style="background-color:#eaf6f5;padding:40px 16px;">
    <tr>
        <td align="center">
            <table width="580" cellpadding="0" cellspacing="0" border="0" role="presentation"
                   style="max-width:580px;width:100%;">

                {{-- ===== HEADER ===== --}}
                <tr>
                    <td style="background:linear-gradient(135deg,#2FA7A0 0%,#1c6e69 100%);
                                border-radius:20px 20px 0 0;padding:40px 40px 32px;text-align:center;">
                        <img src="{{ url('images/logo-email.png') }}"
                             alt="Salon Beauty"
                             width="200"
                             style="max-width:200px;max-height:80px;display:inline-block;">
                        <div style="margin-top:10px;">
                            <span style="color:#ffffff;font-size:11px;letter-spacing:3px;
                                         text-transform:uppercase;opacity:0.75;">
                                gestão de salões
                            </span>
                        </div>
                    </td>
                </tr>

                {{-- ===== BARRA DECORATIVA ===== --}}
                <tr>
                    <td style="height:5px;
                                background:linear-gradient(90deg,#2FA7A0 0%,#A6789B 100%);"></td>
                </tr>

                {{-- ===== CORPO ===== --}}
                <tr>
                    <td style="background:#ffffff;
                                padding:44px 48px 36px;
                                border-left:1px solid #d1ece9;
                                border-right:1px solid #d1ece9;">

                        {{-- Ícone decorativo --}}
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="center" style="padding-bottom:28px;">
                                    <div style="display:inline-block;
                                                background:linear-gradient(135deg,#eaf6f5,#f6f0f5);
                                                border:2px solid #d1ece9;
                                                border-radius:50%;
                                                width:72px;height:72px;
                                                line-height:72px;
                                                font-size:32px;
                                                text-align:center;">
                                        ✉️
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <h1 style="color:#1c6e69;font-size:24px;font-weight:700;
                                   margin:0 0 8px;text-align:center;letter-spacing:-0.3px;">
                            Confirme seu E-mail
                        </h1>
                        <p style="color:#6b7280;font-size:14px;text-align:center;margin:0 0 32px;">
                            Olá, <strong style="color:#2FA7A0;">{{ $user->name }}</strong>! Use o código abaixo para ativar sua conta.
                        </p>

                        {{-- Código de verificação --}}
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="center" style="padding-bottom:36px;">
                                    <div style="display:inline-block;
                                                background:linear-gradient(135deg,#eaf6f5 0%,#f0faf9 100%);
                                                border:2px solid #2FA7A0;
                                                border-radius:16px;
                                                padding:24px 40px;">
                                        <p style="margin:0 0 8px;color:#6b7280;font-size:12px;
                                                   letter-spacing:2px;text-transform:uppercase;text-align:center;">
                                            seu código de verificação
                                        </p>
                                        <p style="margin:0;font-size:42px;font-weight:800;
                                                   letter-spacing:12px;color:#1c6e69;
                                                   text-align:center;font-family:'Courier New',monospace;">
                                            {{ $code }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        {{-- Caixa de aviso --}}
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="background:#f6f0f5;
                                            border-left:4px solid #A6789B;
                                            border-radius:0 12px 12px 0;
                                            padding:16px 20px;">
                                    <p style="color:#5a3d52;font-size:14px;line-height:1.6;margin:0;">
                                        ⏱&nbsp; Este código expira em <strong>60 minutos</strong>.
                                        Se você não criou uma conta no Salon Beauty, ignore este e-mail.
                                    </p>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

                {{-- ===== RODAPÉ ===== --}}
                <tr>
                    <td style="background:#f8fbfb;
                                border:1px solid #d1ece9;
                                border-top:none;
                                border-radius:0 0 20px 20px;
                                padding:24px 48px 28px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="border-top:1px solid #e5e7eb;padding-top:18px;text-align:center;">
                                    <p style="color:#9ca3af;font-size:12px;margin:0;line-height:1.6;">
                                        © {{ date('Y') }} <strong>Salon Beauty</strong> · Todos os direitos reservados
                                    </p>
                                    <p style="color:#d1d5db;font-size:11px;margin:6px 0 0;">
                                        Este é um e-mail automático, por favor não responda.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
