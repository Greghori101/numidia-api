<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;


use App\Models\File;
use App\Models\Permission;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        DB::transaction(function () {
            $user = User::create([
                'email' => 'admin@admin.com',
                'name' => 'numidia admin',
                'password' => 'admin',
                'phone_number' => '0990990990',
                'gender' => 'male',

            ]);

            $permissions = [
                ["value" => "financials", "label" => "Financials"],
                ["value" => "statistics", "label" => "Statistics"],
                ["value" => "news", "label" => "News"],
                ["value" => "user-management", "label" => "User Management"],
                ["value" => "users", "label" => "Users"],
                ["value" => "delete-users", "label" => "Delete Users"],
                ["value" => "edit-users", "label" => "Edit Users"],
                ["value" => "create-users", "label" => "Create Users"],
                ["value" => "teachers", "label" => "Teachers"],
                ["value" => "delete-teachers", "label" => "Delete Teachers"],
                ["value" => "edit-teachers", "label" => "Edit Teachers"],
                ["value" => "create-teachers", "label" => "Create Teachers"],
                ["value" => "students", "label" => "Students"],
                ["value" => "delete-students", "label" => "Delete Students"],
                ["value" => "edit-students", "label" => "Edit Students"],
                ["value" => "create-students", "label" => "Create Students"],
                ["value" => "parents", "label" => "Parents"],
                ["value" => "delete-parents", "label" => "Delete Parents"],
                ["value" => "edit-parents", "label" => "Edit Parents"],
                ["value" => "create-parents", "label" => "Create Parents"],
                ["value" => "department-management", "label" => "Department Management"],
                ["value" => "groups", "label" => "Groups"],
                ["value" => "delete-groups", "label" => "Delete Groups"],
                ["value" => "edit-groups", "label" => "Edit Groups"],
                ["value" => "create-groups", "label" => "Create Groups"],
                ["value" => "levels", "label" => "Levels"],
                ["value" => "delete-levels", "label" => "Delete Levels"],
                ["value" => "edit-levels", "label" => "Edit Levels"],
                ["value" => "create-levels", "label" => "Create Levels"],
                ["value" => "sessions", "label" => "Sessions"],
                ["value" => "delete-sessions", "label" => "Delete Sessions"],
                ["value" => "edit-sessions", "label" => "Edit Sessions"],
                ["value" => "create-sessions", "label" => "Create Sessions"],
                ["value" => "attendance", "label" => "Attendance"],
                ["value" => "delete-attendance", "label" => "Delete Attendance"],
                ["value" => "edit-attendance", "label" => "Edit Attendance"],
                ["value" => "create-attendance", "label" => "Create Attendance"],
                ["value" => "exams", "label" => "Exams"],
                ["value" => "delete-exams", "label" => "Delete Exams"],
                ["value" => "edit-exams", "label" => "Edit Exams"],
                ["value" => "create-exams", "label" => "Create Exams"],
                ["value" => "library-management", "label" => "Library Management"],
                ["value" => "library", "label" => "Library"],
                ["value" => "clients", "label" => "Clients"],
                ["value" => "delete-clients", "label" => "Delete Clients"],
                ["value" => "edit-clients", "label" => "Edit Clients"],
                ["value" => "create-clients", "label" => "Create Clients"],
                ["value" => "orders", "label" => "Orders"],
                ["value" => "delete-orders", "label" => "Delete Orders"],
                ["value" => "edit-orders", "label" => "Edit Orders"],
                ["value" => "create-orders", "label" => "Create Orders"],
                ["value" => "products", "label" => "Products"],
                ["value" => "delete-products", "label" => "Delete Products"],
                ["value" => "edit-products", "label" => "Edit Products"],
                ["value" => "create-products", "label" => "Create Products"],
                ["value" => "dawarat-management", "label" => "Dawarat Management"],
                ["value" => "dawarat", "label" => "Dawarat"],
                ["value" => "amphis", "label" => "Amphis"],
                ["value" => "delete-amphis", "label" => "Delete Amphis"],
                ["value" => "edit-amphis", "label" => "Edit Amphis"],
                ["value" => "create-amphis", "label" => "Create Amphis"],
                ["value" => "dawarat-groups", "label" => "Dawarat Groups"],
                ["value" => "delete-dawarat-groups", "label" => "Delete Dawarat Groups"],
                ["value" => "edit-dawarat-groups", "label" => "Edit Dawarat Groups"],
                ["value" => "create-dawarat-groups", "label" => "Create Dawarat Groups"],
                ["value" => "dawarat-sessions", "label" => "Dawarat Sessions"],
                ["value" => "delete-dawarat-sessions", "label" => "Delete Dawarat Sessions"],
                ["value" => "edit-dawarat-sessions", "label" => "Edit Dawarat Sessions"],
                ["value" => "create-dawarat-sessions", "label" => "Create Dawarat Sessions"],
                ["value" => "tickets", "label" => "Tickets"],
                ["value" => "delete-tickets", "label" => "Delete Tickets"],
                ["value" => "edit-tickets", "label" => "Edit Tickets"],
                ["value" => "create-tickets", "label" => "Create Tickets"],
                ["value" => "dawarat-attendance", "label" => "Dawarat Attendance"],
                ["value" => "delete-dawarat-attendance", "label" => "Delete Dawarat Attendance"],
                ["value" => "edit-dawarat-attendance", "label" => "Edit Dawarat Attendance"],
                ["value" => "create-dawarat-attendance", "label" => "Create Dawarat Attendance"],
                ["value" => "waiting-list", "label" => "Waiting List"],
                ["value" => "delete-waiting-list", "label" => "Delete Waiting List"],
                ["value" => "edit-waiting-list", "label" => "Edit Waiting List"],
                ["value" => "create-waiting-list", "label" => "Create Waiting List"],
            ];


            foreach ($permissions as $permissionData) {
                Permission::create(['name' => $permissionData['value'], 'user_id' => $user->id]);
            }

            $user->markEmailAsVerified();

            $content = Storage::get('default-profile-picture.jpeg');
            $bytes = random_bytes(ceil(64 / 2));
            $hex = bin2hex($bytes);
            $file_name = substr($hex, 0, 64);
            $file_url = '/avatars/' .  $file_name . '.jpeg';
            Storage::put($file_url, $content);
            $user->profile_picture()->create(['url' => $file_url]);


            $user->wallet()->save(new Wallet());

            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('SCHOOL_API') . '/api/create-user', [
                    'id' => $user->id,
                    'email' => 'admin@admin.com',
                    'name' => 'numidia admin',
                    'phone_number' => '0990990990',
                    'role' => "admin",
                    'gender' => 'male',
                ]);
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('PROFESSION_API') . '/api/create-user', [
                    'id' => $user->id,
                    'email' => 'admin@admin.com',
                    'name' => 'numidia admin',
                    'phone_number' => '0990990990',
                    'role' => "admin",
                    'gender' => 'male',
                ]);
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('ACADEMY_API') . '/api/create-user', [
                    'id' => $user->id,
                    'email' => 'admin@admin.com',
                    'name' => 'numidia admin',
                    'phone_number' => '0990990990',
                    'role' => "admin",
                    'gender' => 'male',
                ]);
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('LIBRARY_API') . '/api/create-user', [
                    'id' => $user->id,
                    'email' => 'admin@admin.com',
                    'name' => 'numidia admin',
                    'phone_number' => '0990990990',
                    'role' => "admin",
                    'gender' => 'male',
                    'city' => 'batna',
                    'wilaya' => 'batna',
                    'street' => 'batna',
                ]);
        });
    }
}
