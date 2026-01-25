<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminPermissionsSeeder::class);
        $this->call(RolesPermissionsSeeder::class);
        $this->call(EmployeePermissionsSeeder::class);
        $this->call(LocationPermissionsSeeder::class);
        $this->call(CarPermissionsSeeder::class);
        $this->call(SubscriptionPermissionsSeeder::class);
        $this->call(TicketsPermissionsSeeder::class);
        $this->call(InvoicesPermissionsSeeder::class);
        $this->call(ShiftsPermissionsSeeder::class);
        $this->call(ReportsPermissionsSeeder::class);
        
        \App\Models\User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => '12345678',
        ]);

        \App\Models\Role::create([
            'name' => 'admin',
            'display_name' => 'ادمن',
            'description' => 'ادمن',
        ]);

        DB::table('role_user')->insert([
            'role_id' => '1',
            'user_id' => '1',
            'user_type' => 'App\Models\User',
        ]);

        $permissions = Permission::get();
        foreach ($permissions as $item) {
            DB::table('permission_role')->insert([
                'permission_id' => $item->id,
                'role_id' => '1',
            ]);
        }
    }
}
