<?php

namespace Modules\MarkingManagement\Console\Commands;

use Illuminate\Console\Command;
use Modules\MarkingManagement\Services\MarkingService;

class ExpireMarkingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marking:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-expire markings that have passed their expiration date';

    /**
     * Execute the console command.
     */
    public function handle(MarkingService $markingService): int
    {
        $this->info('Checking for expired markings...');

        $expiredCount = $markingService->autoExpireMarkings();

        if ($expiredCount > 0) {
            $this->info("Successfully expired {$expiredCount} marking(s).");
        } else {
            $this->info('No markings to expire.');
        }

        return Command::SUCCESS;
    }
}
