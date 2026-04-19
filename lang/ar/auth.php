<?php
declare(strict_types=1);

return [
    // Laravel Auth messages
    'failed'   => 'هذه البيانات لا تتطابق مع سجلاتنا.',
    'password' => 'كلمة المرور المقدمة غير صحيحة.',
    'throttle' => 'محاولات تسجيل دخول كثيرة. يرجى المحاولة خلال :seconds ثانية.',
    'deactivated' => 'تم تعطيل هذا الحساب. اتصل بالمسؤول.',

    // UI strings for auth screens
    'ui' => [
        'secure_space' => 'إدارة الوثائق الإلكترونية.',
        'sign_in_failed' => 'فشل تسجيل الدخول',
        'error_hint' => 'تحقق من البريد الإلكتروني وكلمة المرور ثم حاول مرة أخرى.',
        'reset_password_link' => 'إعادة تعيين كلمة المرور',
        'login' => 'تسجيل الدخول',
        'password' => 'كلمة المرور',
        'forgot_password_q' => 'هل نسيت كلمة المرور؟',
        'forgot_password' => 'نسيت كلمة المرور؟',
        'sign_in' => 'تسجيل الدخول',
        'we_couldnt_process' => 'لم نتمكن من معالجة طلبك',
        'enter_email_send_link' => 'أدخل بريدك الإلكتروني وسنرسل لك رابط إعادة تعيين كلمة المرور.',
        'email' => 'البريد الإلكتروني',
        'email_placeholder' => 'you@example.com',
        'email_reset_link' => 'إرسال رابط إعادة تعيين كلمة المرور',
        'reset_password' => 'إعادة تعيين كلمة المرور',
        'confirm_password' => 'تأكيد كلمة المرور',
        'sign_in_title' => 'تسجيل الدخول',
        'sign_in_intro' => 'أدخل بريدك الإلكتروني وكلمة المرور لتسجيل الدخول!',
        
        // Language selector
        'language' => 'اللغة',
        'language_en' => 'الإنجليزية',
        'language_fr' => 'الفرنسية',
        'language_ar' => 'العربية',
        
        // Password reset page specific
        'reset_password_subtitle' => 'أدخل كلمة المرور الجديدة أدناه',
        'password_requirements' => 'يجب أن تتكون من 8 أحرف على الأقل مع أحرف كبيرة وصغيرة وأرقام ورموز',
        
        // Email translations
        'email_subject' => 'إعادة تعيين كلمة المرور',
        'email_title' => 'طلب إعادة تعيين كلمة المرور',
        'email_greeting' => 'مرحبًا :name،',
        'email_body_line1' => 'لقد تلقينا طلبًا لإعادة تعيين كلمة المرور لحسابك في :app.',
        'email_body_line2' => 'انقر على الزر أدناه لإعادة تعيين كلمة المرور:',
        'email_button' => 'إعادة تعيين كلمة المرور',
        'email_expiry_title' => 'مهم:',
        'email_expiry_text' => 'سينتهي صلاحية رابط إعادة تعيين كلمة المرور هذا خلال 60 دقيقة لأسباب أمنية. يرجى إعادة تعيين كلمة المرور في أقرب وقت ممكن.',
        'email_alternative_text' => 'إذا لم يعمل الزر، انسخ والصق عنوان URL هذا في متصفحك:',
        'email_security_title' => 'إشعار أمني:',
        'email_security_text' => 'إذا لم تطلب إعادة تعيين كلمة المرور هذه، فيرجى تجاهل هذا البريد الإلكتروني. ستبقى كلمة المرور الخاصة بك دون تغيير.',
        'email_help_text' => 'إذا كان لديك أي أسئلة أو تحتاج إلى مساعدة، يرجى الاتصال بمسؤول النظام.',
        'email_closing' => 'مع أطيب التحيات،',
        'email_team' => 'فريق :app',
        'email_footer_line1' => 'هذا بريد إلكتروني تلقائي. يرجى عدم الرد على هذه الرسالة.',
        'email_footer_line2' => '© :year :app. جميع الحقوق محفوظة.',
    ],
];
