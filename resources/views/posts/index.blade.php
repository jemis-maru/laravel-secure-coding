@extends('layouts.app')

@section('content')
<h2>Posts</h2>

<p>Examples of output rendering below.</p>

@foreach($posts as $post)
<div style="padding:10px;border:1px solid #ddd;margin-bottom:8px;">
  <h3>{{ $post->title }}</h3>

  <p><strong>Safe rendering (escaped):</strong></p>
  <div>
    {{ $post->content }}
  </div>

  <p><strong>Unsafe rendering (intentionally shown to demonstrate XSS):</strong></p>
  <div>
    {{-- Danger: this outputs raw HTML — only show during controlled demo --}}
    {!! $post->content !!}
  </div>
</div>
@endforeach

@endsection
