<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\File;
use App\Models\User;
use App\Models\Wallet;
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
            'gender' => 'male',

        ]);
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
                'role' => "numidia",
                'gender' => 'male',
            ]);
        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('PROFESSION_API') . '/api/create-user', [
                'id' => $user->id,
                'email' => 'admin@admin.com',
                'name' => 'numidia admin',
                'phone_number' => '0990990990',
                'role' => "numidia",
                'gender' => 'male',
            ]);
        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('ACADEMY_API') . '/api/create-user', [
                'id' => $user->id,
                'email' => 'admin@admin.com',
                'name' => 'numidia admin',
                'phone_number' => '0990990990',
                'role' => "numidia",
                'gender' => 'male',
            ]);
        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('LIBRARY_API') . '/api/create-user', [
                'id' => $user->id,
                'email' => 'admin@admin.com',
                'name' => 'numidia admin',
                'phone_number' => '0990990990',
                'role' => "numidia",
                'gender' => 'male',
                'city' => 'batna',
                'wilaya' => 'batna',
                'street' => 'batna',
            ]);
    }
}
