<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class LocationPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create([
            'name' => 'show-locations',
            'display_name' => 'عرض موقع',
        ]);
        Permission::create([
            'name' => 'create-locations',
            'display_name' => 'إنشاء موقع',
        ]);
        Permission::create([
            'name' => 'edit-locations',
            'display_name' => 'تعديل موقع',
        ]);
        Permission::create([
            'name' => 'delete-locations',
            'display_name' => 'حذف موقع',
        ]);
    }
}
