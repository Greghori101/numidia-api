<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::create([
            'email' => 'admin@admin.com',
            'name' => 'numidia admin',
            'password' => 'admin',
            'phone_number' => '0990990990',
            'role' => 'admin',
            'gender' => 'male',

        ]);
        $user->markEmailAsVerified();
        $content = Storage::get('default-profile-picture.jpeg');
        $extension = 'jpeg';
        $name = 'profile picture';
        $user->profile_picture()->save(
            new File([
                'name' => $name,
                'content' => base64_encode($content),
                'extension' => $extension,
            ])
        );

        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('SCHOOL_API') . '/api/create-user', [
                'id' => $user->id,
                'email' => 'admin@admin.com',
                'name' => 'numidia admin',
                'phone_number' => '0990990990',
                'role' => 'admin',
                'gender' => 'male',
            ]);
        // $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
        //     ->post(env('PROFESSION_API') . '/api/create-user', [
        //         'id' => $user->id,
        //         'email' => 'admin@admin.com',
        //         'name' => 'numidia admin',
        //         'phone_number' => '0990990990',
        //         'role' => 'admin',
        //         'gender' => 'male',
        //     ]);
        // $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
        //     ->post(env('ACADEMY_API') . '/api/create-user', [
        //         'id' => $user->id,
        //         'email' => 'admin@admin.com',
        //         'name' => 'numidia admin',
        //         'phone_number' => '0990990990',
        //         'role' => 'admin',
        //         'gender' => 'male',
        //     ]);
        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('LIBRARY_API') . '/api/create-user', [
                'id' => $user->id,
                'email' => 'admin@admin.com',
                'name' => 'numidia admin',
                'phone_number' => '0990990990',
                'role' => 'admin',
                'gender' => 'male',
                'city' => 'batna',
                'wilaya' => 'batna',
                'street' => 'batna',
            ]);
        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('DAWARAT_API') . '/api/create-user', [
                'id' => $user->id,
                'email' => 'admin@admin.com',
                'name' => 'numidia admin',
                'phone_number' => '0990990990',
                'role' => 'admin',
                'gender' => 'male',
            ]);
    }
}
