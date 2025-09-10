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
    protected $description = 'Delete plans older than 1 month';

    public function handle()
    {

        $free_plan = Plan::where('provider_id', Auth::id())->where('subscription_id', 1)->first();
        $paid_plan = Plan::where('provider_id', Auth::id())->where('status', 'Active')->first();

        if ($free_plan) {
            return Plan::where($free_plan->created_at > Carbon::now()->addMonth())
                ->where('subscription_id', $free_plan->subscription_id)
                ->delete();
        }

        if ($paid_plan) {
            Plan::where($paid_plan->created_at > Carbon::now()->addMonth())
                ->where('status', 'Active')
                ->where('subscription_id', $paid_plan->subscription_id)
                ->delete();
        }

        $this->info("Deleted plans.");
    }
}
