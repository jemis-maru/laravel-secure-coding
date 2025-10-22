<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::latest()->get();
        return view('posts.index', compact('posts'));
    }

    public function create()
    {
        return view('posts.create');
    }

    /**
     * Patched: uses validation, parameter binding / Eloquent (prevents SQLi),
     * and relies on Blade escaping to prevent XSS.
     */
    public function store(Request $request)
    {
        // Server-side validation + sanitisation
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string|max:5000',
        ]);

        // Use Eloquent (parameter binding under the hood) -> prevents SQL injection
        Post::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        return redirect()->route('posts.index')->with('status', 'Saved (secure)');
    }

    /**
     * Show search page
     */
    public function searchForm()
    {
        return view('posts.search');
    }

    /**
     * VULNERABLE: Search with SQL injection vulnerability
     * Example malicious payload: ' OR '1'='1
     */
    public function searchVulnerable(Request $request)
    {
        $search = $request->input('search');

        try {
            // VULNERABLE: Direct string concatenation in WHERE clause
            $posts = DB::select("SELECT * FROM posts WHERE title LIKE '%$search%' OR content LIKE '%$search%'");

            return view('posts.search', [
                'posts' => $posts,
                'search' => $search,
                'method' => 'vulnerable'
            ]);
        } catch (\Exception $e) {
            return view('posts.search', [
                'posts' => [],
                'search' => $search,
                'method' => 'vulnerable',
                'error' => 'SQL Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * SECURE: Search with parameter binding (prevents SQL injection)
     */
    public function searchSecure(Request $request)
    {
        $search = $request->input('search');

        // SECURE: Using parameter binding
        $posts = DB::select(
            "SELECT * FROM posts WHERE title LIKE ? OR content LIKE ?",
            ["%$search%", "%$search%"]
        );

        return view('posts.search', [
            'posts' => $posts,
            'search' => $search,
            'method' => 'secure'
        ]);
    }
}
