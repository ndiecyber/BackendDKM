<?php

namespace App\Console\Commands;

use App\Models\Qurban\QurbanTransaction;
use Illuminate\Console\Command;

class CancelExpiredQurbanTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qurban:cancel-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Batalkan transaksi qurban yang berstatus pending dan sudah melewati expired_at';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredTransactions = QurbanTransaction::where('status', 'pending')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now())
            ->get();

        if ($expiredTransactions->isEmpty()) {
            return;
        }

        $count = 0;
        foreach ($expiredTransactions as $transaction) {
            $transaction->update(['status' => 'failed']);
            $count++;
        }

        $this->info("Berhasil membatalkan {$count} transaksi yang kadaluarsa.");
    }
}
