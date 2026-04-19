<?php
declare(strict_types=1);

return [
    // Laravel Auth messages
    'failed'   => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'deactivated' => 'This account has been deactivated. Contact your administrator.',

    // UI strings for auth screens
    'ui' => [
        'secure_space' => 'Electronic document management.',
        'sign_in_failed' => 'Sign in failed',
        'error_hint' => 'Check your email and password, then try again.',
        'reset_password_link' => 'reset your password',
        'login' => 'Login',
        'password' => 'Password',
        'forgot_password_q' => 'Forgot your password?',
        'forgot_password' => 'Forgot password?',
        'sign_in' => 'Sign in',
        'we_couldnt_process' => "We couldn't process your request",
        'enter_email_send_link' => 'Enter your email and we will send you a password reset link.',
        'email' => 'Email',
        'email_placeholder' => 'you@example.com',
        'email_reset_link' => 'Email Password Reset Link',
        'reset_password' => 'Reset Password',
        'confirm_password' => 'Confirm Password',
        'sign_in_title' => 'Sign In',
        'sign_in_intro' => 'Enter your email and password to sign in!',
        
        // Language selector
        'language' => 'Language',
        'language_en' => 'English',
        'language_fr' => 'French',
        'language_ar' => 'Arabic',
        
        // Password reset page specific
        'reset_password_subtitle' => 'Enter your new password below',
        'password_requirements' => 'Must be at least 8 characters with uppercase, lowercase, numbers, and symbols',
        
        // Email translations
        'email_subject' => 'Reset Your Password',
        'email_title' => 'Password Reset Request',
        'email_greeting' => 'Hello :name,',
        'email_body_line1' => 'We received a request to reset your password for your :app account.',
        'email_body_line2' => 'Click the button below to reset your password:',
        'email_button' => 'Reset Password',
        'email_expiry_title' => 'Important:',
        'email_expiry_text' => 'This password reset link will expire in 60 minutes for security reasons. Please reset your password as soon as possible.',
        'email_alternative_text' => 'If the button doesn\'t work, copy and paste this URL into your browser:',
        'email_security_title' => 'Security Notice:',
        'email_security_text' => 'If you didn\'t request this password reset, please ignore this email. Your password will remain unchanged.',
        'email_help_text' => 'If you have any questions or need assistance, please contact your system administrator.',
        'email_closing' => 'Best regards,',
        'email_team' => 'The :app Team',
        'email_footer_line1' => 'This is an automated email. Please do not reply to this message.',
        'email_footer_line2' => '© :year :app. All rights reserved.',
    ],
];
