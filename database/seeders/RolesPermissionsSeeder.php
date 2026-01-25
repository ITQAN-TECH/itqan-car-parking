<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create([
            'name' => 'show-roles',
            'display_name' => 'عرض الأدوار',
        ]);
        Permission::create([
            'name' => 'create-roles',
            'display_name' => 'إنشاء دور',
        ]);
        Permission::create([
            'name' => 'edit-roles',
            'display_name' => 'تعديل دور',
        ]);
        Permission::create([
            'name' => 'delete-roles',
            'display_name' => 'حذف دور',
        ]);
    }
}
