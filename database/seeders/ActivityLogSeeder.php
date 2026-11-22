<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    /**
     * Sample audit entries so the Activity Log page has meaningful data
     * on first run. Live activity is added automatically afterwards.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@jimtrack.local')->value('id');
        $juan = User::where('email', 'juan@jimtrack.local')->value('id');
        $maria = User::where('email', 'maria@jimtrack.local')->value('id');

        $entries = [
            [$admin, 'logged in', 'Auth', 'Signed in successfully.', 95],
            [$admin, 'viewed', 'Dashboard', 'Opened the dashboard.', 88],
            [$juan, 'viewed', 'Products', 'Opened the Products list.', 73],
            [$juan, 'searched', 'Products', 'Searched Products for "protein".', 66],
            [$maria, 'viewed', 'Inventory', 'Opened the Inventory list.', 54],
            [$admin, 'viewed', 'Brands', 'Opened the Brands list.', 42],
            [$admin, 'viewed', 'Categories', 'Opened the Category list.', 36],
            [$juan, 'updated', 'Inventory', 'Stock In of 20 pcs for "Whey Protein 1kg".', 25],
            [$admin, 'created', 'Products', "Created product 'Test Plyo Box' (JT-TST-001).", 18],
            [$admin, 'viewed', 'Users', 'Opened the User list.', 12],
            [$maria, 'viewed', 'Activity Log', 'Opened the Activity Log.', 6],
            [$admin, 'deactivated', 'Products', "Marked product 'Test Plyo Box' as unavailable (inactive).", 2],
        ];

        foreach ($entries as [$userId, $action, $module, $description, $minutesAgo]) {
            $log = ActivityLog::create([
                'user_id' => $userId,
                'action' => $action,
                'module' => $module,
                'description' => $description,
                'ip_address' => '127.0.0.1',
            ]);

            $time = now()->subMinutes($minutesAgo);
            $log->timestamps = false;
            $log->created_at = $time;
            $log->updated_at = $time;
            $log->save();
        }
    }
}
