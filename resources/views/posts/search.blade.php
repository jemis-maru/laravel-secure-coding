@extends('layouts.app')

@section('content')
<h2>Search Posts - SQL Injection Demo</h2>

<hr style="margin: 20px 0;">

<!-- VULNERABLE SEARCH -->
<div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin-bottom: 20px;">
  <h3 style="color: #856404;">⚠️ VULNERABLE Search (SQL Injection Demo)</h3>
  <p><strong>Try these SQL injection payloads:</strong></p>
  <ul style="margin: 10px 0;">
    <li><code>' OR '1'='1</code> - Returns all posts (bypasses search condition)</li>
    <li><code>' UNION SELECT id, title, content FROM posts WHERE '1'='1</code> - Union-based injection</li>
    <li><code>' AND 1=0 UNION SELECT 1,'Database Version',version() WHERE '1'='1</code> - Extract database info</li>
    <li><code>%'; DROP TABLE posts; --</code> - Dangerous! (Drops the table)</li>
  </ul>

  <form method="GET" action="{{ route('posts.search.vulnerable') }}">
    <div>
      <input type="text" name="search" placeholder="Search posts..."
        value="{{ $search ?? '' }}"
        style="width: 70%; padding: 8px; font-size: 14px;">
      <button type="submit" style="padding: 8px 16px; background-color: #dc3545; color: white; border: none; cursor: pointer;">
        Search (VULNERABLE)
      </button>
    </div>
  </form>
</div>

<!-- SECURE SEARCH -->
<div style="background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin-bottom: 20px;">
  <h3 style="color: #155724;">✅ SECURE Search (Using Parameter Binding)</h3>
  <p>This uses parameterized queries which prevent SQL injection by treating user input as data, not code.</p>

  <form method="GET" action="{{ route('posts.search.secure') }}">
    <div>
      <input type="text" name="search" placeholder="Search posts..."
        value="{{ $search ?? '' }}"
        style="width: 70%; padding: 8px; font-size: 14px;">
      <button type="submit" style="padding: 8px 16px; background-color: #28a745; color: white; border: none; cursor: pointer;">
        Search (SECURE)
      </button>
    </div>
  </form>
</div>

<hr style="margin: 20px 0;">

<!-- RESULTS OR ERROR -->
@if(isset($error))
<div style="padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; margin-bottom: 20px;">
  <strong>Error:</strong> {{ $error }}
</div>
@endif

@if(isset($method))
@if($method === 'vulnerable')
<div style="padding: 10px; background-color: #fff3cd; border: 1px solid #ffeaa7; margin-bottom: 20px;">
  <strong>Search Method Used:</strong> {{ strtoupper($method) }}
  @if(isset($search))
  | <strong>Search Term:</strong> "{{ $search }}"
  @endif
</div>
@else
<div style="padding: 10px; background-color: #d4edda; border: 1px solid #c3e6cb; margin-bottom: 20px;">
  <strong>Search Method Used:</strong> {{ strtoupper($method) }}
  @if(isset($search))
  | <strong>Search Term:</strong> "{{ $search }}"
  @endif
</div>
@endif
@endif

<!-- SEARCH RESULTS -->
@if(isset($posts))
@if(count($posts) > 0)
<h3>Search Results ({{ count($posts) }} found)</h3>
@foreach($posts as $post)
<div style="padding: 15px; border: 1px solid #ddd; margin-bottom: 10px; background-color: #f9f9f9;">
  <h4 style="margin: 0 0 10px 0;">
    @if(is_object($post))
    {{ $post->title }}
    @else
    {{ $post['title'] ?? 'N/A' }}
    @endif
  </h4>
  <p style="margin: 0; color: #666;">
    @if(is_object($post))
    {{ $post->content ?? 'No content' }}
    @else
    {{ $post['content'] ?? 'No content' }}
    @endif
  </p>
  <small style="color: #999;">
    ID:
    @if(is_object($post))
    {{ $post->id }}
    @else
    {{ $post['id'] ?? 'N/A' }}
    @endif
  </small>
</div>
@endforeach
@else
<p style="padding: 15px; background-color: #f8f9fa; border: 1px solid #dee2e6;">
  No posts found matching your search.
</p>
@endif
@else
<p style="padding: 15px; background-color: #f8f9fa; border: 1px solid #dee2e6;">
  Enter a search term above to find posts.
</p>
@endif

<hr style="margin: 30px 0;">

<!-- EXPLANATION SECTION -->
<div style="background-color: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px;">
  <h3>How SQL Injection Works</h3>

  <h4>Vulnerable Code (PostController::searchVulnerable):</h4>
  <pre style="background-color: #2d2d2d; color: #f8f8f2; padding: 15px; overflow-x: auto; border-radius: 5px;"><code>// VULNERABLE - String concatenation
$posts = DB::select("SELECT * FROM posts WHERE title LIKE '%$search%' OR content LIKE '%$search%'");</code></pre>

  <p><strong>Problem:</strong> User input is directly concatenated into the SQL query. If a user enters <code>' OR '1'='1</code>, the query becomes:</p>
  <pre style="background-color: #2d2d2d; color: #f8f8f2; padding: 15px; overflow-x: auto; border-radius: 5px;"><code>SELECT * FROM posts WHERE title LIKE '%' OR '1'='1%' OR content LIKE '%' OR '1'='1%'</code></pre>
  <p>This returns all posts because '1'='1' is always true!</p>

  <h4>Secure Code (PostController::searchSecure):</h4>
  <pre style="background-color: #2d2d2d; color: #22c55e; padding: 15px; overflow-x: auto; border-radius: 5px;"><code>// SECURE - Parameter binding
$posts = DB::select(
    "SELECT * FROM posts WHERE title LIKE ? OR content LIKE ?",
    ["%$search%", "%$search%"]
);</code></pre>

  <p><strong>Solution:</strong> Parameter binding treats user input as data, not executable SQL code. The database engine escapes special characters automatically.</p>

  <h4>Other Prevention Methods:</h4>
  <ul>
    <li><strong>Eloquent ORM:</strong> <code>Post::where('title', 'like', "%$search%")->get()</code> (uses parameter binding internally)</li>
    <li><strong>Query Builder:</strong> <code>DB::table('posts')->where('title', 'like', "%$search%")->get()</code></li>
    <li><strong>Input Validation:</strong> Validate and sanitize all user inputs</li>
    <li><strong>Prepared Statements:</strong> Always use parameterized queries</li>
  </ul>
</div>

@endsection
