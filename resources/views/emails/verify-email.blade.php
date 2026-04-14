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
                            Estamos quase lá! Só mais um passo.
                        </p>

                        <p style="color:#374151;font-size:16px;line-height:1.7;margin:0 0 16px;">
                            Olá, <strong style="color:#2FA7A0;">{{ $user->name }}</strong>!
                        </p>
                        <p style="color:#4b5563;font-size:15px;line-height:1.7;margin:0 0 32px;">
                            Bem-vindo(a) ao <strong style="color:#1c6e69;">Salon Beauty</strong>!
                            Para ativar sua conta e aproveitar todos os recursos de gestão do seu salão,
                            confirme seu endereço de e-mail clicando no botão abaixo:
                        </p>

                        {{-- Botão CTA --}}
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="center" style="padding:4px 0 36px;">
                                    <a href="{{ $verifyUrl }}"
                                       style="display:inline-block;
                                              background:linear-gradient(135deg,#2FA7A0 0%,#1c6e69 100%);
                                              color:#ffffff;
                                              text-decoration:none;
                                              font-weight:700;
                                              font-size:16px;
                                              padding:18px 48px;
                                              border-radius:50px;
                                              letter-spacing:0.3px;
                                              box-shadow:0 4px 16px rgba(47,167,160,0.35);">
                                        Confirmar meu E-mail
                                    </a>
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
                                        ⏱&nbsp; Este link de confirmação expira em <strong>60 minutos</strong>.
                                        Se você não criou uma conta no Salon Beauty, ignore este e-mail.
                                    </p>
                                </td>
                            </tr>
                        </table>

                        {{-- Separador --}}
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="padding:32px 0 0;">
                                    <p style="color:#4b5563;font-size:14px;line-height:1.7;margin:0;">
                                        Após confirmar seu e-mail, você poderá acessar o painel completo
                                        e começar a gerenciar seu salão com praticidade. 💇‍♀️✨
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
                        <p style="color:#6b7280;font-size:13px;line-height:1.6;margin:0 0 10px;">
                            Se o botão acima não funcionar, copie e cole o link abaixo no seu navegador:
                        </p>
                        <p style="margin:0 0 20px;">
                            <a href="{{ $verifyUrl }}"
                               style="color:#2FA7A0;font-size:12px;word-break:break-all;
                                      text-decoration:none;">
                                {{ $verifyUrl }}
                            </a>
                        </p>
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
