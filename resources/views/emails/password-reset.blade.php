<!DOCTYPE html>
<html lang="{{ $locale ?? 'fr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe — {{ config('app.name') }}</title>
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
                            <div style="width:64px;height:64px;background-color:#fff5f5;border-radius:50%;margin:0 auto 20px auto;line-height:64px;">
                                <span style="font-size:28px;">🔑</span>
                            </div>
                            <h1 style="margin:0 0 8px 0;font-size:24px;font-weight:700;color:#1a1a1a;">{{ ui_t('auth.ui.email_title', [], $locale) }}</h1>
                            <p style="margin:0;font-size:15px;color:#666666;">{{ config('app.name') }}</p>
                        </td>
                    </tr>

                    <!-- BODY -->
                    <tr>
                        <td style="padding:0 32px 32px 32px;">
                            <p style="margin:0 0 16px 0;font-size:15px;line-height:1.7;color:#333333;">
                                {{ ui_t('auth.ui.email_greeting', ['name' => $user->full_name], $locale) }}
                            </p>
                            <p style="margin:0 0 8px 0;font-size:15px;line-height:1.7;color:#444444;">
                                {{ ui_t('auth.ui.email_body_line1', ['app' => config('app.name')], $locale) }}
                            </p>
                            <p style="margin:0 0 24px 0;font-size:15px;line-height:1.7;color:#444444;">
                                {{ ui_t('auth.ui.email_body_line2', [], $locale) }}
                            </p>

                            <!-- CTA -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="padding:8px 0 24px 0;">
                                        <a href="{{ $resetUrl }}" style="display:inline-block;background-color:#C41E3A;color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:6px;font-size:15px;font-weight:600;letter-spacing:0.3px;">
                                            {{ ui_t('auth.ui.email_button', [], $locale) }}
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- EXPIRY -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#fff8e1;border-left:4px solid #f59e0b;border-radius:4px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:14px 16px;font-size:14px;color:#555555;">
                                        <strong style="color:#d97706;">⏰ {{ ui_t('auth.ui.email_expiry_title', [], $locale) }}</strong> {{ ui_t('auth.ui.email_expiry_text', [], $locale) }}
                                    </td>
                                </tr>
                            </table>

                            <!-- SECURITY -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0f9ff;border-left:4px solid #3b82f6;border-radius:4px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:14px 16px;font-size:14px;color:#555555;">
                                        <strong style="color:#2563eb;">🔒 {{ ui_t('auth.ui.email_security_title', [], $locale) }}</strong> {{ ui_t('auth.ui.email_security_text', [], $locale) }}
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px 0;font-size:13px;color:#888888;">{{ ui_t('auth.ui.email_alternative_text', [], $locale) }}</p>
                            <p style="margin:0 0 24px 0;font-size:12px;color:#aaaaaa;word-break:break-all;">{{ $resetUrl }}</p>

                            <p style="margin:0 0 4px 0;font-size:14px;color:#666666;">{{ ui_t('auth.ui.email_help_text', [], $locale) }}</p>
                            <p style="margin:0;font-size:14px;color:#666666;">
                                {{ ui_t('auth.ui.email_closing', [], $locale) }}<br>
                                {{ ui_t('auth.ui.email_team', ['app' => config('app.name')], $locale) }}
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
                            <p style="margin:0 0 4px 0;font-size:12px;color:#aaaaaa;">{{ ui_t('auth.ui.email_footer_line1', [], $locale) }}</p>
                            <p style="margin:0;font-size:12px;color:#aaaaaa;">&copy; {{ date('Y') }} {{ config('app.name') }}. {{ ui_t('auth.ui.email_footer_line2', ['year' => date('Y'), 'app' => config('app.name')], $locale) }}</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
