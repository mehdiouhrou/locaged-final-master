<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddPostponeSuccessMessagesTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translations = [
            // Success messages for postpone
            [
                'key' => 'pages.destructions.postpone.success',
                'en_text' => 'Expiration postponed by :amount :unit. New expiry: :date.',
                'ar_text' => 'تم تأجيل انتهاء الصلاحية بمقدار :amount :unit. الصلاحية الجديدة: :date.',
                'fr_text' => 'Expiration reportée de :amount :unit. Nouvelle expiration : :date.',
            ],
            [
                'key' => 'pages.destructions.postpone.success_active',
                'en_text' => 'Expiration postponed by :amount :unit. New expiry: :date. Document is now active again.',
                'ar_text' => 'تم تأجيل انتهاء الصلاحية بمقدار :amount :unit. الصلاحية الجديدة: :date. المستند نشط الآن مرة أخرى.',
                'fr_text' => 'Expiration reportée de :amount :unit. Nouvelle expiration : :date. Le document est à nouveau actif.',
            ],
            // Request deletion success
            [
                 'key' => 'pages.destructions.request_deleted',
                 'en_text' => 'Destruction request deleted successfully.',
                 'ar_text' => 'تم حذف طلب الإتلاف بنجاح.',
                 'fr_text' => 'Demande de destruction supprimée avec succès.',
            ],
            // Time units
            [
                'key' => 'pages.destructions.postpone.minutes',
                'en_text' => 'minutes',
                'ar_text' => 'دقيقة',
                'fr_text' => 'minutes',
            ],
            [
                'key' => 'pages.destructions.postpone.hours',
                'en_text' => 'hours',
                'ar_text' => 'ساعات',
                'fr_text' => 'heures',
            ],
            [
                'key' => 'pages.destructions.postpone.days',
                'en_text' => 'days',
                'ar_text' => 'أيام',
                'fr_text' => 'jours',
            ],
            [
                'key' => 'pages.destructions.postpone.weeks',
                'en_text' => 'weeks',
                'ar_text' => 'أسابيع',
                'fr_text' => 'semaines',
            ],
            [
                'key' => 'pages.destructions.postpone.months',
                'en_text' => 'months',
                'ar_text' => 'شهور',
                'fr_text' => 'mois',
            ],
            [
                'key' => 'pages.destructions.postpone.years',
                'en_text' => 'years',
                'ar_text' => 'سنوات',
                'fr_text' => 'années',
            ],
        ];

        foreach ($translations as $t) {
            UiTranslation::updateOrCreate(
                ['key' => $t['key']],
                [
                    'en_text' => $t['en_text'],
                    'ar_text' => $t['ar_text'],
                    'fr_text' => $t['fr_text'],
                ]
            );
        }
    }
}
