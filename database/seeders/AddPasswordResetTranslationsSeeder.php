<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddPasswordResetTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translations = [
            // Language selector
            [
                'key' => 'auth.ui.language',
                'en_text' => 'Language',
                'fr_text' => 'Langue',
                'ar_text' => 'اللغة',
            ],
            [
                'key' => 'auth.ui.language_en',
                'en_text' => 'English',
                'fr_text' => 'Anglais',
                'ar_text' => 'الإنجليزية',
            ],
            [
                'key' => 'auth.ui.language_fr',
                'en_text' => 'French',
                'fr_text' => 'Français',
                'ar_text' => 'الفرنسية',
            ],
            [
                'key' => 'auth.ui.language_ar',
                'en_text' => 'Arabic',
                'fr_text' => 'Arabe',
                'ar_text' => 'العربية',
            ],
            
            // Password reset page specific
            [
                'key' => 'auth.ui.reset_password_subtitle',
                'en_text' => 'Enter your new password below',
                'fr_text' => 'Entrez votre nouveau mot de passe ci-dessous',
                'ar_text' => 'أدخل كلمة المرور الجديدة أدناه',
            ],
            [
                'key' => 'auth.ui.password_requirements',
                'en_text' => 'Must be at least 8 characters with uppercase, lowercase, numbers, and symbols',
                'fr_text' => 'Doit contenir au moins 8 caractères avec des majuscules, minuscules, chiffres et symboles',
                'ar_text' => 'يجب أن تتكون من 8 أحرف على الأقل مع أحرف كبيرة وصغيرة وأرقام ورموز',
            ],
            
            // Email translations
            [
                'key' => 'auth.ui.email_subject',
                'en_text' => 'Reset Your Password',
                'fr_text' => 'Réinitialiser votre mot de passe',
                'ar_text' => 'إعادة تعيين كلمة المرور',
            ],
            [
                'key' => 'auth.ui.email_title',
                'en_text' => 'Password Reset Request',
                'fr_text' => 'Demande de réinitialisation de mot de passe',
                'ar_text' => 'طلب إعادة تعيين كلمة المرور',
            ],
            [
                'key' => 'auth.ui.email_greeting',
                'en_text' => 'Hello :name,',
                'fr_text' => 'Bonjour :name,',
                'ar_text' => 'مرحبًا :name،',
            ],
            [
                'key' => 'auth.ui.email_body_line1',
                'en_text' => 'We received a request to reset your password for your :app account.',
                'fr_text' => 'Nous avons reçu une demande de réinitialisation de votre mot de passe pour votre compte :app.',
                'ar_text' => 'لقد تلقينا طلبًا لإعادة تعيين كلمة المرور لحسابك في :app.',
            ],
            [
                'key' => 'auth.ui.email_body_line2',
                'en_text' => 'Click the button below to reset your password:',
                'fr_text' => 'Cliquez sur le bouton ci-dessous pour réinitialiser votre mot de passe :',
                'ar_text' => 'انقر على الزر أدناه لإعادة تعيين كلمة المرور:',
            ],
            [
                'key' => 'auth.ui.email_button',
                'en_text' => 'Reset Password',
                'fr_text' => 'Réinitialiser le mot de passe',
                'ar_text' => 'إعادة تعيين كلمة المرور',
            ],
            [
                'key' => 'auth.ui.email_expiry_title',
                'en_text' => 'Important:',
                'fr_text' => 'Important :',
                'ar_text' => 'مهم:',
            ],
            [
                'key' => 'auth.ui.email_expiry_text',
                'en_text' => 'This password reset link will expire in 60 minutes for security reasons. Please reset your password as soon as possible.',
                'fr_text' => 'Ce lien de réinitialisation du mot de passe expirera dans 60 minutes pour des raisons de sécurité. Veuillez réinitialiser votre mot de passe dès que possible.',
                'ar_text' => 'سينتهي صلاحية رابط إعادة تعيين كلمة المرور هذا خلال 60 دقيقة لأسباب أمنية. يرجى إعادة تعيين كلمة المرور في أقرب وقت ممكن.',
            ],
            [
                'key' => 'auth.ui.email_alternative_text',
                'en_text' => 'If the button doesn\'t work, copy and paste this URL into your browser:',
                'fr_text' => 'Si le bouton ne fonctionne pas, copiez et collez cette URL dans votre navigateur :',
                'ar_text' => 'إذا لم يعمل الزر، انسخ والصق عنوان URL هذا في متصفحك:',
            ],
            [
                'key' => 'auth.ui.email_security_title',
                'en_text' => 'Security Notice:',
                'fr_text' => 'Avis de sécurité :',
                'ar_text' => 'إشعار أمني:',
            ],
            [
                'key' => 'auth.ui.email_security_text',
                'en_text' => 'If you didn\'t request this password reset, please ignore this email. Your password will remain unchanged.',
                'fr_text' => 'Si vous n\'avez pas demandé cette réinitialisation de mot de passe, veuillez ignorer cet e-mail. Votre mot de passe restera inchangé.',
                'ar_text' => 'إذا لم تطلب إعادة تعيين كلمة المرور هذه، فيرجى تجاهل هذا البريد الإلكتروني. ستبقى كلمة المرور الخاصة بك دون تغيير.',
            ],
            [
                'key' => 'auth.ui.email_help_text',
                'en_text' => 'If you have any questions or need assistance, please contact your system administrator.',
                'fr_text' => 'Si vous avez des questions ou besoin d\'assistance, veuillez contacter votre administrateur système.',
                'ar_text' => 'إذا كان لديك أي أسئلة أو تحتاج إلى مساعدة، يرجى الاتصال بمسؤول النظام.',
            ],
            [
                'key' => 'auth.ui.email_closing',
                'en_text' => 'Best regards,',
                'fr_text' => 'Cordialement,',
                'ar_text' => 'مع أطيب التحيات،',
            ],
            [
                'key' => 'auth.ui.email_team',
                'en_text' => 'The :app Team',
                'fr_text' => 'L\'équipe :app',
                'ar_text' => 'فريق :app',
            ],
            [
                'key' => 'auth.ui.email_footer_line1',
                'en_text' => 'This is an automated email. Please do not reply to this message.',
                'fr_text' => 'Ceci est un e-mail automatique. Veuillez ne pas répondre à ce message.',
                'ar_text' => 'هذا بريد إلكتروني تلقائي. يرجى عدم الرد على هذه الرسالة.',
            ],
            [
                'key' => 'auth.ui.email_footer_line2',
                'en_text' => '© :year :app. All rights reserved.',
                'fr_text' => '© :year :app. Tous droits réservés.',
                'ar_text' => '© :year :app. جميع الحقوق محفوظة.',
            ],
        ];

        foreach ($translations as $translation) {
            DB::table('ui_translations')->updateOrInsert(
                ['key' => $translation['key']],
                [
                    'en_text' => $translation['en_text'],
                    'fr_text' => $translation['fr_text'],
                    'ar_text' => $translation['ar_text'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('Password reset translations have been seeded successfully.');
    }
}
