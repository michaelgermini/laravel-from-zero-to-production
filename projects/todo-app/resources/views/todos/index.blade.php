@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">
                        <i class="fas fa-tasks text-primary"></i> Todo List
                    </h2>
                    <a href="{{ route('todos.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Todo
                    </a>
                </div>
                <div class="card-body">
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Total</h5>
                                    <h3>{{ $todos->total() }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Completed</h5>
                                    <h3>{{ $completedCount }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Pending</h5>
                                    <h3>{{ $pendingCount }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Overdue</h5>
                                    <h3>{{ $overdueCount }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Buttons -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="btn-group" role="group">
                                <a href="{{ route('todos.index') }}" class="btn btn-outline-primary">All</a>
                                <a href="{{ route('todos.pending') }}" class="btn btn-outline-warning">Pending</a>
                                <a href="{{ route('todos.completed') }}" class="btn btn-outline-success">Completed</a>
                                <a href="{{ route('todos.overdue') }}" class="btn btn-outline-danger">Overdue</a>
                            </div>
                        </div>
                    </div>

                    <!-- Todo List -->
                    @if($todos->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Status</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Priority</th>
                                        <th>Due Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($todos as $todo)
                                        <tr class="{{ $todo->completed ? 'table-success' : '' }}">
                                            <td>
                                                <form action="{{ route('todos.toggle', $todo) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm {{ $todo->completed ? 'btn-success' : 'btn-warning' }}">
                                                        <i class="fas {{ $todo->completed ? 'fa-check' : 'fa-clock' }}"></i>
                                                    </button>
                                                </form>
                                            </td>
                                            <td>
                                                <strong>{{ $todo->title }}</strong>
                                            </td>
                                            <td>
                                                {{ Str::limit($todo->description, 50) }}
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $todo->priority_color }}">
                                                    {{ ucfirst($todo->priority) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($todo->due_date)
                                                    <span class="{{ $todo->due_date->isPast() && !$todo->completed ? 'text-danger' : '' }}">
                                                        {{ $todo->due_date->format('M d, Y') }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">No due date</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('todos.show', $todo) }}" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('todos.edit', $todo) }}" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('todos.destroy', $todo) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $todos->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No todos found</h4>
                            <p class="text-muted">Start by creating your first todo!</p>
                            <a href="{{ route('todos.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Todo
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
