<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class PostController extends Controller
{
    //
    public function index()
    {
        $posts = Post::with(['user.profile_picture','photos'])->get();
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
        ]);

        $user = User::find($request->user()->id);
        $files = $request->file('uploaded_images');
        $user->posts()->save($post);
        foreach ($files as $file) {
            # code...
            $name = $file->getClientOriginalName(); // Get the original name of the file
            $content = file_get_contents($file->getRealPath()); // Get the content of the file
            $extension = $file->getClientOriginalExtension(); // Get the extension of the file

            $post->photos()->save(new File([
                'name' => $name,
                'content' => base64_encode($content),
                'extension' => $extension,
            ]));
        }
        $user->posts()->save($post);

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
