<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Secure Demo</title>
</head>

<body>
  <div style="max-width:800px;margin:2rem auto;">
    <a href="{{ route('posts.create') }}">Create Post</a> |
    <a href="{{ route('posts.index') }}">List</a> |
    <a href="{{ route('posts.search') }}">Search</a>
    <hr>
    @if(session('status'))
    <div style="padding:8px;background:#eef;border:1px solid #99c">{{ session('status') }}</div>
    @endif

    @yield('content')
  </div>
</body>

</html>
