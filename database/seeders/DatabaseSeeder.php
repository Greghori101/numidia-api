<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Mail\VerifyEmail;
use App\Models\Admin;
use App\Models\Department;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Department::create([
            'name' => 'numidia school',
        ]);
        Department::create([
            'name' => 'numidia profession',
        ]);
        Department::create([
            'name' => 'numidia academy',
        ]);

        $content = Storage::get('default-profile-picture.jpeg');
        $extension = 'jpeg';
        $name = 'profile picture';
        $user = User::create([
            'name' => 'Numidia Admin',
            'email' => env('APP_MAIL_ADMIN'),
            'role' => 'admin',
            'gender' => 'Male',
            'password' => Hash::make('admin'),
        ]);
        $user->admin()->save(new Admin());

        $user->profile_picture()->save(
            new File([
                'name' => $name,
                'content' => base64_encode($content),
                'extension' => $extension,
            ])
        );

        $user->markEmailAsVerified();
    }
}
