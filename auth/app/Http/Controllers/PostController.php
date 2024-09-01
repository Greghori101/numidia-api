<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    //
    public function index()
    {
        $posts = Post::with(['user.profile_picture', 'photos'])->get();
        return response()->json($posts, 200);
    }

    public function show($id)
    {
        $post = Post::with(['user.profile_picture'])->find($id);

        return response()->json($post, 200);
    }
    public function create(Request $request)
    {
        $post = Post::create([
            'title' => $request->title,
            'content' => $request->content,
            'department' => $request->department,

        ]);

        $user = User::find($request->user()->id);

        $images =  $request->file('uploaded_images');
        if ($images) {
            foreach ($images as $image) {
                $file = $image;

                $file_extension = $image->extension();

                $bytes = random_bytes(ceil(64 / 2));
                $hex = bin2hex($bytes);
                $file_name = substr($hex, 0, 64);

                $file_url = '/posts/' .  $file_name . '.' . $file_extension;

                Storage::put($file_url, file_get_contents($file));

                $post->photos()->create(['url' => $file_url]);
            }
        }
        $user->posts()->save($post);


        $users = User::all()->except($user->id);
        foreach ($users as $user) {
            $notification =  Notification::create([
                'type' => "info",
                'title' => "New Post",
                'content' => "new post created by: " . $user->name . " at: " . Carbon::Now(),
                'displayed' => false,
                'user_id' => $user->id,
            ]);
        }

        return response()->json(200);
    }

    public function delete($id)
    {
        $post = Post::find($id);
        $post->delete();
        return response()->json(200);
    }

    public function update(Request $request, $id)
    {
        $post = Post::updateOrCreate(
            ['id' => $id],
            [
                'title' => $request->title,
                'content' => $request->content,
            ]
        );
        return response()->json(200);
    }
}
