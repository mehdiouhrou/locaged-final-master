<?php
declare(strict_types=1);

return [
    // Laravel Auth messages
    'failed'   => 'Ces identifiants ne correspondent pas à nos enregistrements.',
    'password' => 'Le mot de passe fourni est incorrect.',
    'throttle' => 'Trop de tentatives de connexion. Veuillez réessayer dans :seconds secondes.',

    // UI strings for auth screens
    'ui' => [
        'secure_space' => 'Gestion électronique de documents.',
        'sign_in_failed' => 'Échec de la connexion',
        'error_hint' => 'Vérifiez votre e-mail et votre mot de passe, puis réessayez.',
        'reset_password_link' => 'réinitialiser votre mot de passe',
        'login' => 'Identifiant',
        'password' => 'Mot de passe',
        'forgot_password_q' => 'Mot de passe oublié ?',
        'forgot_password' => 'Mot de passe oublié ?',
        'sign_in' => 'Se connecter',
        'we_couldnt_process' => 'Nous n\'avons pas pu traiter votre demande',
        'enter_email_send_link' => 'Entrez votre e-mail et nous vous enverrons un lien de réinitialisation.',
        'email' => 'E-mail',
        'email_placeholder' => 'vous@exemple.com',
        'email_reset_link' => 'Envoyer le lien de réinitialisation',
        'reset_password' => 'Réinitialiser le mot de passe',
        'confirm_password' => 'Confirmer le mot de passe',
        'sign_in_title' => 'Connexion',
        'sign_in_intro' => 'Entrez votre e-mail et votre mot de passe pour vous connecter !',
        
        // Language selector
        'language' => 'Langue',
        'language_en' => 'Anglais',
        'language_fr' => 'Français',
        'language_ar' => 'Arabe',
        
        // Password reset page specific
        'reset_password_subtitle' => 'Entrez votre nouveau mot de passe ci-dessous',
        'password_requirements' => 'Doit contenir au moins 8 caractères avec des majuscules, minuscules, chiffres et symboles',
        
        // Email translations
        'email_subject' => 'Réinitialiser votre mot de passe',
        'email_title' => 'Demande de réinitialisation de mot de passe',
        'email_greeting' => 'Bonjour :name,',
        'email_body_line1' => 'Nous avons reçu une demande de réinitialisation de votre mot de passe pour votre compte :app.',
        'email_body_line2' => 'Cliquez sur le bouton ci-dessous pour réinitialiser votre mot de passe :',
        'email_button' => 'Réinitialiser le mot de passe',
        'email_expiry_title' => 'Important :',
        'email_expiry_text' => 'Ce lien de réinitialisation du mot de passe expirera dans 60 minutes pour des raisons de sécurité. Veuillez réinitialiser votre mot de passe dès que possible.',
        'email_alternative_text' => 'Si le bouton ne fonctionne pas, copiez et collez cette URL dans votre navigateur :',
        'email_security_title' => 'Avis de sécurité :',
        'email_security_text' => 'Si vous n\'avez pas demandé cette réinitialisation de mot de passe, veuillez ignorer cet e-mail. Votre mot de passe restera inchangé.',
        'email_help_text' => 'Si vous avez des questions ou besoin d\'assistance, veuillez contacter votre administrateur système.',
        'email_closing' => 'Cordialement,',
        'email_team' => 'L\'équipe :app',
        'email_footer_line1' => 'Ceci est un e-mail automatique. Veuillez ne pas répondre à ce message.',
        'email_footer_line2' => '© :year :app. Tous droits réservés.',
    ],
];
