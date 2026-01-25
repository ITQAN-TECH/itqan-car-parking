<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class SubscriptionPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create([
            'name' => 'show-subscriptions',
            'display_name' => 'عرض الاشتراكات',
        ]);
        Permission::create([
            'name' => 'create-subscriptions',
            'display_name' => 'إنشاء الاشتراكات',
        ]);
        Permission::create([
            'name' => 'edit-subscriptions',
            'display_name' => 'تعديل الاشتراكات',
        ]);
        Permission::create([
            'name' => 'delete-subscriptions',
            'display_name' => 'حذف الاشتراكات',
        ]);
    }
}
