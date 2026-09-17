<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.access',

            'hotels.view',
            'hotels.manage',

            'flights.view',
            'flights.manage',

            'airport-transfers.view',
            'airport-transfers.manage',

            'visa-services.view',
            'visa-services.manage',

            'daily-tours.view',
            'daily-tours.manage',

            'holiday-packages.view',
            'holiday-packages.manage',

            'bookings.view',
            'bookings.manage',

            'book-now.view',
            'book-now.manage',

            'popular-destinations.view',
            'popular-destinations.manage',

            'about-us.view',
            'about-us.manage',

            'contact-messages.view',
            'contact-messages.manage',

            'newsletter.view',
            'newsletter.manage',

            'users.manage',
            'roles.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(
            Permission::query()
                ->whereNotIn('name', ['users.manage', 'roles.manage'])
                ->pluck('name')
        );

        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $manager->syncPermissions([
            'dashboard.access',
            'bookings.view',
            'bookings.manage',
            'book-now.view',
            'book-now.manage',
            'contact-messages.view',
            'contact-messages.manage',
            'newsletter.view',
            'newsletter.manage',
            'airport-transfers.view',
            'airport-transfers.manage',
        ]);

        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewer->syncPermissions(
            Permission::query()
                ->where(function ($query) {
                    $query->where('name', 'like', '%.view')
                        ->orWhere('name', 'dashboard.access');
                })
                ->pluck('name')
        );

        $superUser = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123@'),
            ]
        );
        $superUser->syncRoles(['super-admin']);

        $demoAdmin = User::updateOrCreate(
            ['email' => 'manager@gmail.com'],
            [
                'name' => 'Dashboard Manager',
                'password' => Hash::make('manager123@'),
            ]
        );
        $demoAdmin->syncRoles(['manager']);

        $demoViewer = User::updateOrCreate(
            ['email' => 'viewer@gmail.com'],
            [
                'name' => 'Dashboard Viewer',
                'password' => Hash::make('viewer123@'),
            ]
        );
        $demoViewer->syncRoles(['viewer']);
    }
}
