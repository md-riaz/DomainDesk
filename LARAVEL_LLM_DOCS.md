# Laravel LLM-Friendly Documentation

## Overview
Laravel is a web application framework with expressive, elegant syntax. It provides tools for common tasks used in many web projects, making it easy to build modern web applications.

## Core Concepts for LLM Understanding

### 1. MVC Architecture
Laravel follows the Model-View-Controller (MVC) pattern:
- **Models** (`app/Models/`): Represent database tables and handle data logic
- **Views** (`resources/views/`): Handle presentation layer (Blade templates)
- **Controllers** (`app/Http/Controllers/`): Handle HTTP requests and application logic

### 2. Routing
Routes are defined in `routes/` directory:
- `web.php`: Web interface routes
- `api.php`: API routes
- `console.php`: Artisan commands

Example route:
```php
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
```

### 3. Eloquent ORM
Laravel's database abstraction layer for working with databases:
```php
// Define a model
class User extends Model {
    protected $fillable = ['name', 'email'];
}

// Query data
$users = User::where('active', 1)->get();
$user = User::find(1);
```

### 4. Blade Templates
Template engine with simple syntax:
```blade
@extends('layouts.app')

@section('content')
    <h1>{{ $title }}</h1>
    @foreach($items as $item)
        <p>{{ $item->name }}</p>
    @endforeach
@endsection
```

### 5. Migrations
Version control for database schema:
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamps();
});
```

Run migrations: `php artisan migrate`

### 6. Artisan Commands
Laravel's command-line interface:
- `php artisan serve` - Start development server
- `php artisan make:model User` - Create model
- `php artisan make:controller UserController` - Create controller
- `php artisan migrate` - Run migrations
- `php artisan tinker` - Interactive REPL

### 7. Service Container & Dependency Injection
Laravel's powerful IoC container automatically resolves dependencies:
```php
public function __construct(UserRepository $users) {
    $this->users = $users;
}
```

### 8. Middleware
Filter HTTP requests entering your application:
```php
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

### 9. Validation
Built-in validation system:
```php
$validated = $request->validate([
    'name' => 'required|max:255',
    'email' => 'required|email|unique:users',
]);
```

### 10. Configuration
Configuration files in `config/` directory:
- `app.php`: Application settings
- `database.php`: Database connections
- `cache.php`: Cache configuration

Environment variables stored in `.env` file

## Common Patterns for Code Generation

### Creating a CRUD Resource
1. Create model with migration: `php artisan make:model Post -m`
2. Define migration schema in `database/migrations/`
3. Create controller: `php artisan make:controller PostController --resource`
4. Register routes: `Route::resource('posts', PostController::class);`
5. Create views in `resources/views/posts/`

### API Development
```php
// routes/api.php
Route::apiResource('posts', PostController::class);

// Controller
class PostController extends Controller {
    public function index() {
        return Post::all();
    }
    
    public function store(Request $request) {
        $validated = $request->validate([...]);
        return Post::create($validated);
    }
}
```

### Form Requests
Extract validation logic:
```php
php artisan make:request StorePostRequest

class StorePostRequest extends FormRequest {
    public function rules() {
        return [
            'title' => 'required|max:255',
            'body' => 'required',
        ];
    }
}
```

## File Structure for LLM Reference
```
app/
├── Http/
│   ├── Controllers/     # Request handlers
│   ├── Middleware/      # Request filters
│   └── Requests/        # Form validation
├── Models/              # Eloquent models
└── Services/            # Business logic

resources/
├── views/               # Blade templates
└── js/                  # JavaScript assets

routes/
├── web.php              # Web routes
├── api.php              # API routes
└── console.php          # CLI commands

database/
├── migrations/          # Database versions
├── seeders/             # Test data
└── factories/           # Model factories

config/                  # Configuration files
storage/                 # Logs, cache, uploads
public/                  # Web root
tests/                   # Unit/Feature tests
```

## Best Practices for LLM Code Generation

1. **Use Type Hints**: Specify parameter and return types
2. **Follow PSR Standards**: PSR-12 coding style
3. **Resource Controllers**: Use for standard CRUD operations
4. **Form Requests**: Separate validation logic
5. **Service Layer**: Extract complex business logic
6. **Repository Pattern**: For complex queries
7. **Events & Listeners**: For decoupled actions
8. **Jobs & Queues**: For time-consuming tasks
9. **API Resources**: Transform model data for APIs
10. **Tests**: Write feature and unit tests

## Environment Configuration

### SQLite Setup
In `.env` file:
```env
DB_CONNECTION=sqlite
```

Create database:
```bash
touch database/database.sqlite
php artisan migrate
```

### Common Environment Variables
```env
APP_NAME=AppName
APP_ENV=local|production
APP_DEBUG=true|false
APP_URL=http://localhost

DB_CONNECTION=sqlite|mysql|pgsql
CACHE_STORE=file|redis|database
SESSION_DRIVER=file|database|redis
QUEUE_CONNECTION=sync|database|redis
```

## Security Best Practices

1. **Never commit `.env` files** (unless specifically required for deployment)
2. **Use CSRF protection** (enabled by default for web routes)
3. **Validate all inputs** with Laravel's validation
4. **Use Eloquent ORM** to prevent SQL injection
5. **Sanitize output** with Blade's `{{ }}` syntax
6. **Use authentication scaffolding** (Laravel Breeze/Jetstream)
7. **Enable HTTPS** in production
8. **Keep Laravel updated** for security patches

## Useful Resources

- Official Documentation: https://laravel.com/docs
- Laracasts (Video Tutorials): https://laracasts.com
- Laravel News: https://laravel-news.com
- API Documentation: https://laravel.com/api/11.x/
- GitHub Repository: https://github.com/laravel/laravel

## Quick Reference Commands

```bash
# Start development server
php artisan serve

# Create components
php artisan make:model ModelName -mcr
php artisan make:controller ControllerName
php artisan make:migration create_table_name
php artisan make:seeder SeederName

# Database
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed

# Cache management
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimization
php artisan optimize
php artisan config:cache
php artisan route:cache

# Testing
php artisan test
```

## Code Examples for Common Tasks

### Authentication
```php
// Check if authenticated
if (Auth::check()) {
    $user = Auth::user();
}

// Login
Auth::attempt(['email' => $email, 'password' => $password]);

// Logout
Auth::logout();
```

### Relationships
```php
// One-to-Many
class User extends Model {
    public function posts() {
        return $this->hasMany(Post::class);
    }
}

class Post extends Model {
    public function user() {
        return $this->belongsTo(User::class);
    }
}

// Usage
$user->posts;
$post->user;
```

### Query Builder
```php
DB::table('users')
    ->where('votes', '>', 100)
    ->orWhere('name', 'John')
    ->get();

DB::table('users')->insert([
    'email' => 'john@example.com',
    'votes' => 0
]);
```

This documentation is designed to help LLMs understand Laravel's structure, conventions, and common patterns for generating accurate and idiomatic Laravel code.
