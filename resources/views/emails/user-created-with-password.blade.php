<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte créé — {{ config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:'Segoe UI',Arial,sans-serif;color:#333333;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f4;padding:30px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

                    <!-- HEADER -->
                    <tr>
                        <td style="padding:24px 32px;border-bottom:3px solid #C41E3A;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <img src="{{ config('app.url') }}/assets/template/logo.png" alt="{{ config('app.name') }}" height="45" style="display:block;">
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <span style="font-size:13px;color:#C41E3A;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">Gestion Électronique de Documents</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- HERO -->
                    <tr>
                        <td style="padding:40px 32px 24px 32px;text-align:center;">
                            <div style="width:64px;height:64px;background-color:#f0faf4;border-radius:50%;margin:0 auto 20px auto;line-height:64px;">
                                <span style="font-size:28px;">✅</span>
                            </div>
                            <h1 style="margin:0 0 8px 0;font-size:24px;font-weight:700;color:#1a1a1a;">Votre compte est activé</h1>
                            <p style="margin:0;font-size:15px;color:#666666;">Bienvenue sur {{ config('app.name') }}</p>
                        </td>
                    </tr>

                    <!-- BODY -->
                    <tr>
                        <td style="padding:0 32px 32px 32px;">
                            <p style="margin:0 0 16px 0;font-size:15px;line-height:1.7;color:#333333;">
                                Bonjour <strong>{{ $user->full_name }}</strong>,
                            </p>
                            <p style="margin:0 0 24px 0;font-size:15px;line-height:1.7;color:#444444;">
                                Votre compte sur la plateforme <strong>{{ config('app.name') }}</strong> a été créé avec succès. Vous pouvez dès maintenant vous connecter avec les informations suivantes :
                            </p>

                            <!-- CREDENTIALS -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f8f9fa;border-left:4px solid #C41E3A;border-radius:4px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <p style="margin:0 0 6px 0;font-size:13px;color:#888888;text-transform:uppercase;letter-spacing:0.5px;">Identifiant</p>
                                        <p style="margin:0;font-size:16px;font-weight:600;color:#1a1a1a;">{{ $user->email }}</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- WARNING -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#fff8e1;border-left:4px solid #f59e0b;border-radius:4px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:14px 16px;font-size:14px;color:#555555;">
                                        <strong style="color:#d97706;">⚠️ Mot de passe</strong> — Pour des raisons de sécurité, votre mot de passe ne figure pas dans cet email. Contactez votre administrateur système pour l'obtenir.
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="padding:8px 0 24px 0;">
                                        <a href="{{ config('app.url') }}" style="display:inline-block;background-color:#C41E3A;color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:6px;font-size:15px;font-weight:600;letter-spacing:0.3px;">
                                            Accéder à la plateforme
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:14px;color:#666666;">
                                Pour toute question, contactez votre administrateur système.
                            </p>
                        </td>
                    </tr>

                    <!-- DIVIDER -->
                    <tr>
                        <td style="padding:0 32px;"><hr style="border:none;border-top:1px solid #eeeeee;margin:0;"></td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="padding:20px 32px;text-align:center;">
                            <p style="margin:0 0 4px 0;font-size:12px;color:#aaaaaa;">Ceci est un email automatique — merci de ne pas y répondre.</p>
                            <p style="margin:0;font-size:12px;color:#aaaaaa;">&copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
