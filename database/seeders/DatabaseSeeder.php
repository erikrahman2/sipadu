<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Roles ──────────────────────────────────────────────────────────
        $roleNames = [
            'super_admin',
            'pa_assistant',
            'pa_management',
            'pa_staff',
            'disdukcapil_staff',
        ];

        foreach ($roleNames as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'api']);
        }

        // ── Permissions ────────────────────────────────────────────────────
        $permissions = [
            'view cases', 'edit cases', 'delete cases',
            'approve cases', 'validate cases',
            'upload documents', 'download documents',
            'process ocr', 'view ocr results',
            'manage users', 'view audit logs',
            'trigger sync',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'api']);
        }

        // ── Assign permissions to roles ────────────────────────────────────
        $role = fn(string $name) => Role::where('name', $name)->where('guard_name', 'web')->first();

        $role('pa_assistant')->syncPermissions([
            'view cases', 'edit cases',
            'upload documents', 'download documents',
            'view ocr results',
        ]);

        $role('pa_management')->syncPermissions([
            'view cases', 'edit cases', 'approve cases',
            'process ocr', 'view ocr results',
            'download documents',
        ]);

        $role('pa_staff')->syncPermissions([
            'view cases', 'upload documents', 'download documents',
            'view ocr results',
        ]);

        $role('disdukcapil_staff')->syncPermissions([
            'view cases', 'validate cases',
            'upload documents', 'download documents',
            'view ocr results',
        ]);

        $role('super_admin')->syncPermissions($permissions);

        // ── Institutions ───────────────────────────────────────────────────
        $pa = Institution::updateOrCreate(
            ['code' => 'PA-PAINAN-01'],
            ['name' => 'Pengadilan Agama Kota Painan', 'type' => 'PA', 'active' => true]
        );

        $disc = Institution::updateOrCreate(
            ['code' => 'DISC-PESSEL-01'],
            ['name' => 'Dinas Kependudukan dan Pencatatan Sipil Kabupaten Pesisir Selatan', 'type' => 'DISDUKCAPIL', 'active' => true]
        );

        // ── Super Admin ────────────────────────────────────────────────────
        $admin = User::updateOrCreate(
            ['email' => 'admin@sipadu.go.id'],
            ['name' => 'Administrator', 'password' => Hash::make('Admin@123456'), 'status' => 'active', 'institution_id' => $pa->id]
        );
        $admin->syncRoles(['super_admin']);

        // ── PA Assistant ───────────────────────────────────────────────────
        $paAsst = User::updateOrCreate(['email' => 'asisten@pa-painan.go.id'], [
            'name' => 'PA Assistant', 'password' => Hash::make('Pass@12345'),
            'status' => 'active', 'institution_id' => $pa->id,
        ]);
        $paAsst->syncRoles(['pa_assistant']);

        // ── PA Management ──────────────────────────────────────────────────
        $paMgmt = User::updateOrCreate(['email' => 'ketua@pa-painan.go.id'], [
            'name' => 'PA Management', 'password' => Hash::make('Pass@12345'),
            'status' => 'active', 'institution_id' => $pa->id,
        ]);
        $paMgmt->syncRoles(['pa_management']);

        // ── PA Staff ───────────────────────────────────────────────────────
        $paStaff = User::updateOrCreate(['email' => 'staf@pa-painan.go.id'], [
            'name' => 'PA Staff', 'password' => Hash::make('Pass@12345'),
            'status' => 'active', 'institution_id' => $pa->id,
        ]);
        $paStaff->syncRoles(['pa_staff']);

        // ── Disdukcapil Staff ──────────────────────────────────────────────
        $discStaff = User::updateOrCreate(['email' => 'petugas@disdukcapil-pessel.go.id'], [
            'name' => 'Disdukcapil Staff', 'password' => Hash::make('Pass@12345'),
            'status' => 'active', 'institution_id' => $disc->id,
        ]);
        $discStaff->syncRoles(['disdukcapil_staff']);

        $this->command->info('Seeded successfully.');
        $this->command->table(
            ['Akun', 'Email', 'Password', 'Role'],
            [
                ['Administrator',     'admin@sipadu.go.id',                'Admin@123456', 'super_admin'],
                ['PA Assistant',      'asisten@pa-painan.go.id',           'Pass@12345',   'pa_assistant'],
                ['PA Management',     'ketua@pa-painan.go.id',             'Pass@12345',   'pa_management'],
                ['PA Staff',          'staf@pa-painan.go.id',              'Pass@12345',   'pa_staff'],
                ['Disdukcapil Staff', 'petugas@disdukcapil-pessel.go.id',  'Pass@12345',   'disdukcapil_staff'],
            ]
        );
    }
}