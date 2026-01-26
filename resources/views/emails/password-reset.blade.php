<!DOCTYPE html>
<html lang="{{ $locale ?? 'fr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ui_t('auth.ui.email_subject', [], $locale) }} - {{ config('app.name') }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 5px; margin: 20px 0; }
        .button { display: inline-block; background: #3498db; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; text-align: center; }
        .button:hover { background: #2980b9; }
        .expiry { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; font-size: 14px; }
        .footer { text-align: center; color: #7f8c8d; font-size: 12px; margin-top: 30px; }
        .info { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 20px 0; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ ui_t('auth.ui.email_title', [], $locale) }}</h1>
    </div>
    
    <div class="content">
        <h2>{{ ui_t('auth.ui.email_greeting', ['name' => $user->full_name], $locale) }}</h2>
        
        <p>{{ ui_t('auth.ui.email_body_line1', ['app' => config('app.name')], $locale) }}</p>
        
        <p>{{ ui_t('auth.ui.email_body_line2', [], $locale) }}</p>
        
        <p style="text-align: center;">
            <a href="{{ $resetUrl }}" class="button">{{ ui_t('auth.ui.email_button', [], $locale) }}</a>
        </p>
        
        <div class="expiry">
            <p><strong>⏰ {{ ui_t('auth.ui.email_expiry_title', [], $locale) }}</strong> {{ ui_t('auth.ui.email_expiry_text', [], $locale) }}</p>
        </div>
        
        <p>{{ ui_t('auth.ui.email_alternative_text', [], $locale) }}</p>
        <p style="word-break: break-all; font-size: 12px; color: #7f8c8d;">{{ $resetUrl }}</p>
        
        <div class="info">
            <p><strong>🔒 {{ ui_t('auth.ui.email_security_title', [], $locale) }}</strong> {{ ui_t('auth.ui.email_security_text', [], $locale) }}</p>
        </div>
        
        <p>{{ ui_t('auth.ui.email_help_text', [], $locale) }}</p>
        
        <p>{{ ui_t('auth.ui.email_closing', [], $locale) }}<br>
        {{ ui_t('auth.ui.email_team', ['app' => config('app.name')], $locale) }}</p>
    </div>
    
    <div class="footer">
        <p>{{ ui_t('auth.ui.email_footer_line1', [], $locale) }}</p>
        <p>{{ ui_t('auth.ui.email_footer_line2', ['year' => date('Y'), 'app' => config('app.name')], $locale) }}</p>
    </div>
</body>
</html>
