<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeleteOldPlans extends Command
{
    protected $signature = 'plans:delete-old';
    protected $description = 'Delete and update plans older than 1 month';

    public function handle()
    {
        $plans = Plan::all();

        foreach ($plans as $plan) {
            $months = $plan->created_at->diffInMonths(now());

            if ($plan->subscription_id == 1 && $months >= 1) {
                $plan->delete();
            }

            if ($plan->status == 'Active' && $plan->subscription_id != 1 && $months >= 1) {
                $plan->status = 'Inactive';
                $plan->save();
            }
        }
        
        $this->info("Old plans cleaned successfully.");
    }
}
