<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class AdminPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create([
            'name' => 'show-admins',
            'display_name' => 'عرض ادمن',
        ]);
        Permission::create([
            'name' => 'create-admins',
            'display_name' => 'إنشاء ادمن',
        ]);
        Permission::create([
            'name' => 'edit-admins',
            'display_name' => 'تعديل ادمن',
        ]);
        Permission::create([
            'name' => 'delete-admins',
            'display_name' => 'حذف ادمن',
        ]);
    }
}
