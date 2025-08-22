<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TodoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $todos = Todo::latest()->paginate(10);
        $completedCount = Todo::completed()->count();
        $pendingCount = Todo::pending()->count();
        $overdueCount = Todo::overdue()->count();

        return view('todos.index', compact('todos', 'completedCount', 'pendingCount', 'overdueCount'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('todos.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date|after:today',
            'priority' => 'required|in:low,medium,high',
        ]);

        Todo::create($validated);

        return redirect()->route('todos.index')
            ->with('success', 'Todo created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Todo $todo): View
    {
        return view('todos.show', compact('todo'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Todo $todo): View
    {
        return view('todos.edit', compact('todo'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Todo $todo): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high',
            'completed' => 'boolean',
        ]);

        $todo->update($validated);

        return redirect()->route('todos.index')
            ->with('success', 'Todo updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Todo $todo): RedirectResponse
    {
        $todo->delete();

        return redirect()->route('todos.index')
            ->with('success', 'Todo deleted successfully!');
    }

    /**
     * Toggle the completed status of a todo
     */
    public function toggle(Todo $todo): RedirectResponse
    {
        $todo->update(['completed' => !$todo->completed]);

        $status = $todo->completed ? 'completed' : 'marked as pending';
        
        return redirect()->route('todos.index')
            ->with('success', "Todo {$status} successfully!");
    }

    /**
     * Show completed todos
     */
    public function completed(): View
    {
        $todos = Todo::completed()->latest()->paginate(10);
        
        return view('todos.completed', compact('todos'));
    }

    /**
     * Show pending todos
     */
    public function pending(): View
    {
        $todos = Todo::pending()->latest()->paginate(10);
        
        return view('todos.pending', compact('todos'));
    }

    /**
     * Show overdue todos
     */
    public function overdue(): View
    {
        $todos = Todo::overdue()->latest()->paginate(10);
        
        return view('todos.overdue', compact('todos'));
    }
}
