@extends('layouts.app')

@section('content')
<h2>Create post (demo)</h2>

<p><strong>Vulnerable form (no CSRF token) — submit to demonstrate CSRF failure:</strong></p>

<form method="POST" action="{{ route('posts.store') }}">
  <!-- intentionally missing @csrf here to demo CSRF protection blocking the request -->
  <div>
    <label>Title</label><br>
    <input name="title" required>
  </div>
  <div>
    <label>Content</label><br>
    <textarea name="content" rows="6"></textarea>
  </div>
  <button type="submit">Submit (without csrf)</button>
</form>

<hr>

<p><strong>Secure form (with @csrf and server validation):</strong></p>

<form method="POST" action="{{ route('posts.store') }}">
  @csrf
  <div>
    <label>Title</label><br>
    <input name="title" required>
  </div>
  <div>
    <label>Content</label><br>
    <textarea name="content" rows="6"></textarea>
  </div>
  <button type="submit">Submit (secure)</button>
</form>
@endsection
