<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class EmployeePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create([
            'name' => 'show-employees',
            'display_name' => 'عرض موظف',
        ]);
        Permission::create([
            'name' => 'create-employees',
            'display_name' => 'إنشاء موظف',
        ]);
        Permission::create([
            'name' => 'edit-employees',
            'display_name' => 'تعديل موظف',
        ]);
        Permission::create([
            'name' => 'delete-employees',
            'display_name' => 'حذف موظف',
        ]);
    }
}
