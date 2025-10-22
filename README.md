# Laravel Secure Coding Demo

This project demonstrates common web security vulnerabilities and their fixes in a Laravel application, focusing on:
- **SQL Injection**
- **Cross-Site Scripting (XSS)**
- **Cross-Site Request Forgery (CSRF)**

## 🚀 Setup Instructions

### 1) Create Post Model + Migration + Controller + Route

Run artisan commands:

```bash
php artisan make:model Post -m
php artisan make:controller PostController
```

### 2) Edit the Migration

Edit `database/migrations/xxxx_xx_xx_create_posts_table.php`:

```php
public function up(): void
{
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->string('title')->index();
        $table->text('content')->nullable();
        $table->timestamps();
    });
}
```

### 3) Run Migration

```bash
php artisan migrate
```

### 4) Update Files

#### `routes/web.php`
#### `app/Models/Post.php`
#### `app/Http/Controllers/PostController.php`

### 5) Create View Files

Create the following views:
- `resources/views/layouts/app.blade.php`
- `resources/views/posts/create.blade.php`
- `resources/views/posts/index.blade.php`
- `resources/views/posts/search.blade.php`

---

## 🔒 Security Demonstrations

## A) SQL Injection Simulation

### What is SQL Injection?

SQL Injection is a code injection technique that exploits security vulnerabilities in an application's database layer. Attackers can insert malicious SQL statements into application queries to:
- Bypass authentication
- Access unauthorized data
- Modify or delete data
- Execute administrative operations on the database

### How to Test

1. **Navigate to Search Page**: Go to `/posts/search`

2. **Test VULNERABLE Search** with these payloads:

   **Payload 1: Bypass Search Condition**
   ```
   ' OR '1'='1
   ```
   - **What happens**: Returns ALL posts instead of searching
   - **Why**: The query becomes `WHERE title LIKE '%' OR '1'='1%'` which is always true

   **Payload 2: UNION-based Injection**
   ```
   ' UNION SELECT id, title, content FROM posts WHERE '1'='1
   ```
   - **What happens**: Combines results from multiple SELECT statements
   - **Why**: Attacker can extract data from other tables

   **Payload 3: Extract Database Version**
   ```
   ' AND 1=0 UNION SELECT 1,'Database Version',version() WHERE '1'='1
   ```
   - **What happens**: Displays database version information
   - **Why**: Helps attacker understand the database system

   **⚠️ Payload 4: DROP TABLE (DANGEROUS!)**
   ```
   %'; DROP TABLE posts; --
   ```
   - **What happens**: Could delete the entire posts table!
   - **Why**: Executes multiple SQL statements

3. **Test SECURE Search** with the same payloads:
   - All payloads will be treated as literal strings
   - No SQL injection occurs because of parameter binding

### Vulnerable Code

```php
// ❌ VULNERABLE - String concatenation
public function searchVulnerable(Request $request)
{
    $search = $request->input('search');
    
    // Direct string concatenation - DANGEROUS!
    $posts = DB::select("SELECT * FROM posts WHERE title LIKE '%$search%' OR content LIKE '%$search%'");
    
    return view('posts.search', ['posts' => $posts]);
}
```

**Why it's vulnerable:**
- User input is directly concatenated into SQL query
- Special characters like quotes are not escaped
- Attacker can inject arbitrary SQL code

### Secure Code

```php
// ✅ SECURE - Parameter binding
public function searchSecure(Request $request)
{
    $search = $request->input('search');
    
    // Parameter binding - SAFE!
    $posts = DB::select(
        "SELECT * FROM posts WHERE title LIKE ? OR content LIKE ?",
        ["%$search%", "%$search%"]
    );
    
    return view('posts.search', ['posts' => $posts]);
}
```

**Why it's secure:**
- Uses parameter binding (prepared statements)
- Database driver escapes special characters automatically
- User input is treated as data, not executable SQL code

### Other Secure Methods

```php
// Method 1: Eloquent ORM (Recommended)
$posts = Post::where('title', 'like', "%$search%")
             ->orWhere('content', 'like', "%$search%")
             ->get();

// Method 2: Query Builder
$posts = DB::table('posts')
           ->where('title', 'like', "%$search%")
           ->orWhere('content', 'like', "%$search%")
           ->get();

// Method 3: Named Bindings
$posts = DB::select(
    "SELECT * FROM posts WHERE title LIKE :search OR content LIKE :search",
    ['search' => "%$search%"]
);
```

---

## B) XSS (Cross-Site Scripting) Simulation

### What is XSS?

Cross-Site Scripting (XSS) allows attackers to inject malicious scripts into web pages viewed by other users. These scripts can:
- Steal session cookies
- Redirect users to malicious sites
- Modify page content
- Perform actions on behalf of users

### How to Test

1. **Navigate to**: `/posts/create`

2. **In the secure form**, enter this content:

   ```html
   <p>Hello</p><script>console.log('xss triggered');alert('XSS!')</script>
   ```

3. **Submit the form** and view the index page at `/`

4. **Observe two renderings**:

   **Safe Rendering** (using `{{ $post->content }}`):
   - Displays the script as text
   - Does NOT execute the JavaScript
   - Browser shows: `<p>Hello</p><script>console.log('xss triggered');alert('XSS!')</script>`

   **Unsafe Rendering** (using `{!! $post->content !!}`):
   - Executes the `<script>` tag
   - Shows an alert popup with "XSS!"
   - Console logs "xss triggered"
   - **This demonstrates why escaping is critical!**

### Vulnerable Code

```php
{{-- ❌ VULNERABLE - Renders raw HTML --}}
<div>
    {!! $post->content !!}
</div>
```

**Why it's vulnerable:**
- `{!! !!}` outputs raw, unescaped HTML
- Any script tags will execute
- Attacker can inject malicious JavaScript

### Secure Code

```php
{{-- ✅ SECURE - Escapes HTML entities --}}
<div>
    {{ $post->content }}
</div>
```

**Why it's secure:**
- `{{ }}` automatically escapes HTML entities
- `<` becomes `&lt;`, `>` becomes `&gt;`
- Scripts are displayed as text, not executed

### Prevention Best Practices

1. **Always escape output:**
   ```blade
   {{ $userInput }}  <!-- SAFE -->
   {!! $userInput !!} <!-- DANGEROUS -->
   ```

2. **Use Content Security Policy (CSP):**
   ```php
   // In middleware or controller
   response()->header('Content-Security-Policy', "script-src 'self'");
   ```

3. **Sanitize input if HTML is needed:**
   ```php
   use HTMLPurifier;
   
   $purifier = new HTMLPurifier();
   $clean = $purifier->purify($request->input('content'));
   ```

4. **Validate input:**
   ```php
   $validated = $request->validate([
       'content' => 'required|string|max:5000',
   ]);
   ```

---

## C) CSRF (Cross-Site Request Forgery) Simulation

### What is CSRF?

CSRF tricks authenticated users into performing unwanted actions on a web application. An attacker can:
- Submit forms without user knowledge
- Change user settings
- Make unauthorized transactions
- Exploit authenticated sessions

### How to Test

1. **Navigate to**: `/posts/create`

2. **Find the first form** (labeled "Vulnerable form - no CSRF token")

3. **Submit this form**:
   - It intentionally lacks the `@csrf` directive
   - Laravel's `VerifyCsrfToken` middleware will block it
   - You'll get a **419 Error Page** (TokenMismatchException)
   - This demonstrates Laravel's CSRF protection works!

4. **Try the second form** (labeled "Secure form - with @csrf"):
   - Includes `@csrf` directive
   - Form submits successfully
   - Post is created

### Vulnerable Code

```blade
{{-- ❌ VULNERABLE - Missing CSRF token --}}
<form method="POST" action="{{ route('posts.store') }}">
  <!-- @csrf is missing! -->
  <input name="title" required>
  <textarea name="content"></textarea>
  <button type="submit">Submit</button>
</form>
```

**Why it's vulnerable:**
- No CSRF token validation
- Any external site can submit this form
- User's session can be exploited

### Secure Code

```blade
{{-- ✅ SECURE - Includes CSRF token --}}
<form method="POST" action="{{ route('posts.store') }}">
  @csrf
  <input name="title" required>
  <textarea name="content"></textarea>
  <button type="submit">Submit</button>
</form>
```

**What `@csrf` does:**
- Generates a hidden input field with a unique token
- Laravel validates this token on POST/PUT/PATCH/DELETE requests
- Blocks requests without valid tokens

### AJAX CSRF Protection

For AJAX requests, include the CSRF token in headers:

```javascript
// 1. Add meta tag to layout
<meta name="csrf-token" content="{{ csrf_token() }}">

// 2. Include token in AJAX requests
fetch('/posts/store', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
  },
  body: JSON.stringify({ 
    title: 'Example', 
    content: 'Content here' 
  })
});
```

```javascript
// Alternative: Using Axios (automatically includes token if meta tag exists)
axios.post('/posts/store', {
  title: 'Example',
  content: 'Content here'
});
```

### Excluding Routes from CSRF Protection

In `app/Http/Middleware/VerifyCsrfToken.php`:

```php
protected $except = [
    'webhook/*',  // External webhooks
    'api/*',      // API routes (use token auth instead)
];
```

**⚠️ Warning:** Only exclude routes that:
- Use alternative authentication (API tokens, OAuth)
- Are webhooks from trusted third parties
- Have other security measures in place

---

## 📊 Summary Comparison

| Vulnerability | Vulnerable Code | Secure Code | Impact |
|--------------|-----------------|-------------|---------|
| **SQL Injection** | `DB::select("... WHERE col = '$input'")` | `DB::select("... WHERE col = ?", [$input])` | Full database compromise |
| **XSS** | `{!! $userInput !!}` | `{{ $userInput }}` | Session hijacking, data theft |
| **CSRF** | Form without `@csrf` | Form with `@csrf` | Unauthorized actions |

---

## 🛡️ Security Best Practices

### General Principles

1. **Never Trust User Input**
   - Always validate
   - Always sanitize
   - Always escape output

2. **Use Framework Security Features**
   - Laravel's Eloquent ORM (prevents SQL injection)
   - Blade's `{{ }}` escaping (prevents XSS)
   - CSRF middleware (prevents CSRF)

3. **Principle of Least Privilege**
   - Database users should have minimal permissions
   - Don't use root/admin accounts in application

4. **Defense in Depth**
   - Multiple layers of security
   - Don't rely on single protection mechanism

### Laravel-Specific

1. **Use Eloquent/Query Builder** instead of raw SQL
2. **Always use `{{ }}`** for output unless you have a specific reason
3. **Include `@csrf`** in all forms
4. **Validate all input** with Form Requests or `$request->validate()`
5. **Use middleware** for authentication and authorization
6. **Enable HTTPS** in production
7. **Keep Laravel updated** for security patches

### Database Security

```php
// ✅ Good: Eloquent
Post::where('status', 'published')->get();

// ✅ Good: Query Builder
DB::table('posts')->where('status', 'published')->get();

// ✅ Good: Parameter Binding
DB::select('SELECT * FROM posts WHERE status = ?', ['published']);

// ❌ Bad: String Concatenation
DB::select("SELECT * FROM posts WHERE status = '$status'");
```

### Output Security

```blade
{{-- ✅ Good: Escaped output --}}
<h1>{{ $post->title }}</h1>
<div>{{ $post->content }}</div>

{{-- ✅ Good: JSON encoding --}}
<script>
  const data = @json($posts);
</script>

{{-- ❌ Bad: Raw output --}}
<div>{!! $post->content !!}</div>

{{-- ⚠️ Use only when necessary with sanitization --}}
<div>{!! Purifier::clean($post->content) !!}</div>
```

---

## 🧪 Testing Checklist

- [ ] SQL Injection: Test vulnerable search with `' OR '1'='1`
- [ ] SQL Injection: Test secure search with same payload
- [ ] XSS: Inject `<script>alert('XSS')</script>` in content
- [ ] XSS: Verify `{{ }}` escapes the script
- [ ] XSS: Verify `{!! !!}` executes the script (demo only!)
- [ ] CSRF: Submit form without `@csrf` token
- [ ] CSRF: Verify 419 error occurs
- [ ] CSRF: Submit form with `@csrf` token
- [ ] CSRF: Verify successful submission
