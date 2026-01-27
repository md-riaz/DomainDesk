# Livewire LLM-Friendly Documentation

## Overview
Livewire is a full-stack framework for Laravel that makes building dynamic interfaces simple, without leaving the comfort of Laravel. It provides a way to build reactive, modern interfaces using server-side rendering.

## Core Concepts for LLM Understanding

### 1. What is Livewire?
Livewire allows you to build dynamic interfaces without writing JavaScript. Components are PHP classes that render Blade views and can be updated in real-time via AJAX.

**Key Benefits:**
- No JavaScript framework required (Vue, React, etc.)
- Write everything in PHP and Blade
- Real-time validation
- Automatic state management
- Built-in security (CSRF, XSS protection)

### 2. Component Structure
A Livewire component consists of two parts:

**PHP Class** (app/Livewire/Counter.php):
```php
<?php

namespace App\Livewire;

use Livewire\Component;

class Counter extends Component
{
    public $count = 0;

    public function increment()
    {
        $this->count++;
    }

    public function render()
    {
        return view('livewire.counter');
    }
}
```

**Blade View** (resources/views/livewire/counter.blade.php):
```blade
<div>
    <h1>{{ $count }}</h1>
    <button wire:click="increment">+</button>
</div>
```

### 3. Creating Livewire Components

```bash
# Create a component
php artisan make:livewire Counter

# Create a component in a subdirectory
php artisan make:livewire Users/ShowUser

# Create inline component (class and view in one file)
php artisan make:livewire Counter --inline
```

### 4. Rendering Components

**In Blade Templates:**
```blade
<!-- Tag syntax -->
<livewire:counter />

<!-- Blade directive -->
@livewire('counter')

<!-- With parameters -->
<livewire:show-post :post="$post" />
@livewire('show-post', ['post' => $post])
```

**As Full-Page Component (Route):**
```php
Route::get('/counter', Counter::class);
```

### 5. Data Binding with wire:model

**Real-time binding:**
```blade
<input type="text" wire:model="name">
<p>Hello {{ $name }}</p>
```

**Modifiers:**
- `wire:model.live` - Updates on every keystroke
- `wire:model.blur` - Updates on blur event
- `wire:model.debounce.500ms` - Debounces updates

```blade
<!-- Update as you type -->
<input wire:model.live="search">

<!-- Update on blur -->
<input wire:model.blur="email">

<!-- Update with debounce -->
<input wire:model.live.debounce.500ms="search">
```

### 6. Actions (Methods)

**wire:click** - Trigger methods on click:
```blade
<button wire:click="save">Save</button>
<button wire:click="delete({{ $id }})">Delete</button>
<button wire:click="$refresh">Refresh</button>
```

**Other action directives:**
- `wire:submit` - Form submission
- `wire:keydown` - Keyboard events
- `wire:change` - Change events

```blade
<form wire:submit="save">
    <input type="text" wire:model="name">
    <button type="submit">Save</button>
</form>

<input wire:keydown.enter="save">
```

### 7. Component Properties

**Public Properties** (automatically synced with frontend):
```php
class CreatePost extends Component
{
    public $title = '';
    public $content = '';
    public $published = false;
    
    public function save()
    {
        Post::create([
            'title' => $this->title,
            'content' => $this->content,
            'published' => $this->published,
        ]);
    }
}
```

**Protected/Private Properties** (server-side only):
```php
protected $userService;
private $tempData;
```

### 8. Validation

**Real-time Validation:**
```php
class ContactForm extends Component
{
    public $email = '';
    public $message = '';
    
    public function rules()
    {
        return [
            'email' => 'required|email',
            'message' => 'required|min:10',
        ];
    }
    
    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }
    
    public function save()
    {
        $validated = $this->validate();
        
        Contact::create($validated);
        
        session()->flash('message', 'Message sent!');
    }
}
```

**Display Errors in View:**
```blade
<form wire:submit="save">
    <input type="email" wire:model="email">
    @error('email') <span class="error">{{ $message }}</span> @enderror
    
    <textarea wire:model="message"></textarea>
    @error('message') <span class="error">{{ $message }}</span> @enderror
    
    <button type="submit">Send</button>
</form>
```

### 9. Lifecycle Hooks

```php
class ShowPost extends Component
{
    public $post;
    
    // Called once when component is initialized
    public function mount($postId)
    {
        $this->post = Post::find($postId);
    }
    
    // Called before updating a property
    public function updating($name, $value)
    {
        // Runs before any update
    }
    
    // Called after updating a property
    public function updated($name, $value)
    {
        // Runs after any update
    }
    
    // Called before rendering
    public function rendering()
    {
        // Runs before render
    }
    
    // Called after rendering
    public function rendered()
    {
        // Runs after render
    }
}
```

### 10. Loading States

**Show loading indicators:**
```blade
<button wire:click="save" wire:loading.attr="disabled">
    Save
</button>

<div wire:loading>
    Processing...
</div>

<div wire:loading.remove>
    Content
</div>

<!-- Target specific actions -->
<div wire:loading wire:target="save">
    Saving...
</div>

<div wire:loading wire:target="photo">
    Uploading photo...
</div>
```

### 11. File Uploads

```php
use Livewire\WithFileUploads;

class UploadPhoto extends Component
{
    use WithFileUploads;
    
    public $photo;
    
    public function save()
    {
        $this->validate([
            'photo' => 'image|max:1024', // 1MB Max
        ]);
        
        $this->photo->store('photos');
    }
}
```

```blade
<form wire:submit="save">
    <input type="file" wire:model="photo">
    
    @if ($photo)
        <img src="{{ $photo->temporaryUrl() }}" width="200">
    @endif
    
    <button type="submit">Upload</button>
</form>
```

### 12. Pagination

```php
use Livewire\WithPagination;

class ShowPosts extends Component
{
    use WithPagination;
    
    public $search = '';
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function render()
    {
        return view('livewire.show-posts', [
            'posts' => Post::where('title', 'like', "%{$this->search}%")
                ->paginate(10)
        ]);
    }
}
```

```blade
<div>
    <input wire:model.live="search" placeholder="Search posts...">
    
    @foreach($posts as $post)
        <div>{{ $post->title }}</div>
    @endforeach
    
    {{ $posts->links() }}
</div>
```

### 13. Events

**Emit Events:**
```php
// From component
$this->dispatch('postCreated');
$this->dispatch('postCreated', postId: $post->id);

// Browser event
$this->dispatch('alert', message: 'Saved!');
```

**Listen to Events:**
```php
class Dashboard extends Component
{
    protected $listeners = ['postCreated' => 'refreshPosts'];
    
    public function refreshPosts()
    {
        // Refresh data
    }
}
```

**In Blade:**
```blade
<div x-on:alert="alert($event.detail.message)">
    <!-- Content -->
</div>
```

### 14. Nested Components

**Parent Component:**
```php
class TodoList extends Component
{
    public $todos;
    
    public function mount()
    {
        $this->todos = Todo::all();
    }
}
```

```blade
<div>
    @foreach($todos as $todo)
        <livewire:todo-item :todo="$todo" :key="$todo->id" />
    @endforeach
</div>
```

**Child Component:**
```php
class TodoItem extends Component
{
    public $todo;
    
    public function toggle()
    {
        $this->todo->update(['completed' => !$this->todo->completed]);
        $this->dispatch('todoUpdated');
    }
}
```

### 15. Wire:key for Lists

**Important**: Always use `wire:key` when rendering lists:
```blade
@foreach($posts as $post)
    <div wire:key="post-{{ $post->id }}">
        {{ $post->title }}
    </div>
@endforeach
```

### 16. JavaScript Integration (Alpine.js)

Livewire works seamlessly with Alpine.js:
```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    
    <div x-show="open">
        <input wire:model="name">
    </div>
</div>
```

## Common Patterns for Code Generation

### 1. CRUD Component
```php
class ManagePosts extends Component
{
    use WithPagination;
    
    public $postId;
    public $title = '';
    public $content = '';
    public $isOpen = false;
    
    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }
    
    public function openModal()
    {
        $this->isOpen = true;
    }
    
    public function closeModal()
    {
        $this->isOpen = false;
    }
    
    private function resetInputFields()
    {
        $this->title = '';
        $this->content = '';
        $this->postId = null;
    }
    
    public function store()
    {
        $this->validate([
            'title' => 'required',
            'content' => 'required',
        ]);
        
        Post::updateOrCreate(['id' => $this->postId], [
            'title' => $this->title,
            'content' => $this->content
        ]);
        
        session()->flash('message', 
            $this->postId ? 'Post Updated.' : 'Post Created.');
        
        $this->closeModal();
        $this->resetInputFields();
    }
    
    public function edit($id)
    {
        $post = Post::findOrFail($id);
        $this->postId = $id;
        $this->title = $post->title;
        $this->content = $post->content;
        $this->openModal();
    }
    
    public function delete($id)
    {
        Post::find($id)->delete();
        session()->flash('message', 'Post Deleted.');
    }
    
    public function render()
    {
        return view('livewire.manage-posts', [
            'posts' => Post::paginate(10)
        ]);
    }
}
```

### 2. Search Component
```php
class SearchPosts extends Component
{
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        
        $this->sortField = $field;
    }
    
    public function render()
    {
        return view('livewire.search-posts', [
            'posts' => Post::where('title', 'like', '%'.$this->search.'%')
                ->orderBy($this->sortField, $this->sortDirection)
                ->get()
        ]);
    }
}
```

### 3. Form with Validation
```php
class ContactForm extends Component
{
    public $name = '';
    public $email = '';
    public $message = '';
    
    protected $rules = [
        'name' => 'required|min:3',
        'email' => 'required|email',
        'message' => 'required|min:10',
    ];
    
    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }
    
    public function submit()
    {
        $validated = $this->validate();
        
        Contact::create($validated);
        
        $this->reset(['name', 'email', 'message']);
        
        session()->flash('success', 'Message sent successfully!');
    }
    
    public function render()
    {
        return view('livewire.contact-form');
    }
}
```

## Best Practices for LLM Code Generation

1. **Component Naming**: Use descriptive names (ShowPost, CreateUser, ManageTasks)
2. **Single Responsibility**: Each component should have one clear purpose
3. **Use wire:key**: Always add to items in loops for proper tracking
4. **Validation**: Validate on the server, use `validateOnly()` for real-time feedback
5. **Reset State**: Clear form fields after submission using `$this->reset()`
6. **Loading States**: Add `wire:loading` for better UX
7. **Security**: Livewire handles CSRF automatically, validate all inputs
8. **Performance**: Use `wire:model.blur` for less frequent updates
9. **Events**: Use for component communication
10. **Property Types**: Type-hint public properties when possible

## Installation & Configuration

```bash
# Install Livewire
composer require livewire/livewire

# Publish config (optional)
php artisan livewire:publish --config

# Publish assets (optional)
php artisan livewire:publish --assets
```

**Include in Layout:**
```blade
<html>
<head>
    @livewireStyles
</head>
<body>
    {{ $slot }}
    
    @livewireScripts
</body>
</html>
```

## Testing Livewire Components

```php
use Livewire\Livewire;

test('can create post', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'Test Post')
        ->set('content', 'Test content')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('postCreated');
    
    expect(Post::where('title', 'Test Post')->exists())->toBeTrue();
});

test('validates required fields', function () {
    Livewire::test(CreatePost::class)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title' => 'required']);
});
```

## Common Pitfalls to Avoid

1. **Don't use `$this` in Blade**: Use properties directly `{{ $count }}` not `{{ $this->count }}`
2. **Public property limits**: Can't use certain types (Eloquent models as properties can be problematic)
3. **Always use wire:key in loops**: Prevents rendering issues
4. **Don't manipulate DOM directly**: Use Livewire properties and methods
5. **Computed properties**: Use for calculated values instead of storing in properties

## Advanced Features

### Computed Properties
```php
use Livewire\Attributes\Computed;

class ShowPost extends Component
{
    public $postId;
    
    #[Computed]
    public function post()
    {
        return Post::find($this->postId);
    }
    
    // Use in blade as: {{ $this->post->title }}
}
```

### Lazy Loading
```blade
<livewire:show-posts lazy />
```

```php
use Livewire\Attributes\Lazy;

#[Lazy]
class ShowPosts extends Component
{
    public function placeholder()
    {
        return view('loading');
    }
}
```

### URL Query Parameters
```php
use Livewire\Attributes\Url;

class SearchPosts extends Component
{
    #[Url]
    public $search = '';
    
    #[Url(as: 'sort')]
    public $sortBy = 'name';
}
```

## Useful Resources

- Official Documentation: https://livewire.laravel.com
- Screencasts: https://laracasts.com/series/livewire
- Community Forum: https://github.com/livewire/livewire/discussions
- Examples: https://github.com/livewire/livewire/tree/main/legacy-tests/Browser
- Alpine.js (pairs well): https://alpinejs.dev

## Quick Reference

```bash
# Create component
php artisan make:livewire ComponentName

# List all components
php artisan livewire:list

# Delete component
php artisan livewire:delete ComponentName

# Copy component
php artisan livewire:copy SourceComponent TargetComponent

# Move component
php artisan livewire:move SourceComponent TargetComponent
```

This documentation provides LLMs with the essential knowledge to generate accurate and idiomatic Livewire code within Laravel applications.
