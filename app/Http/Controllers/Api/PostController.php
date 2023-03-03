<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class PostController extends Controller
{
    //
    public function index($id = null)
    {
        if ($id) {
            $post = Post::find($id);
            $post['author'] = $post->author;
            $post['author']['profile_picture'] =  $post->author->profile_picture;

            return response()->json($post, 200);
        } else {
            $posts = Post::all();
            foreach ($posts as $post) {
                # code...
                $post['author'] = $post->author;
                $post['author']['profile_picture'] =  $post->author->profile_picture;


            }
            return response()->json($posts, 200);
        }
    }

    public function create(Request $request)
    {
        $post = Post::create([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        $user = User::find($request->author);

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
        $post = Post::updateOrCreate(['id' => $id], [
            'title' => $request->title,
            'content' => $request->content,
        ]);
        return response()->json(200);
    }
}
