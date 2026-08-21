<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe modifié</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 5px; margin: 20px 0; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        .footer { text-align: center; color: #7f8c8d; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
    </div>

    <div class="content">
        <h2>Bonjour {{ $user->full_name }},</h2>

        <p>Votre mot de passe a été modifié par un administrateur le {{ now()->format('d/m/Y à H:i') }}.</p>

        <div class="warning">
            <p><strong>⚠️ Vous n'êtes pas à l'origine de cette action ?</strong> Contactez immédiatement votre administrateur système.</p>
        </div>

        <p>Pour vous connecter, visitez :<br>
        <a href="{{ config('app.url') }}">{{ config('app.url') }}</a></p>

        <p>Cordialement,<br>
        L'équipe {{ config('app.name') }}</p>
    </div>

    <div class="footer">
        <p>Ceci est un email automatique. Merci de ne pas y répondre.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
    </div>
</body>
</html>
