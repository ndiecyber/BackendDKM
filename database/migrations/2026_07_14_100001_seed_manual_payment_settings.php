<?php

use App\Models\Qurban\QurbanSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            'payment_mode' => 'manual',
            'manual_qris_string' => '00020101021126640017ID.CO.BANKBSI.WWW0118936004510000200008021000002019320303UMI51440014ID.CO.QRIS.WWW0215ID10232628326460303UMI5204866153033605802ID5916DKM JAMI KASSITI6011TASIKMALAYA610546464630411B8',
            'manual_qris_name' => 'DKM JAMI KASSITI',
            'manual_qris_nmid' => 'ID 1023262832646',
            'manual_bank_name' => 'BSI',
            'manual_bank_account' => '7453555555',
            'manual_bank_holder' => 'DKM Masjid Jami Kassiti',
        ];

        foreach ($settings as $key => $value) {
            QurbanSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }

    public function down(): void
    {
        QurbanSetting::whereIn('key', [
            'payment_mode',
            'manual_qris_string',
            'manual_qris_name',
            'manual_qris_nmid',
            'manual_bank_name',
            'manual_bank_account',
            'manual_bank_holder',
        ])->delete();
    }
};
