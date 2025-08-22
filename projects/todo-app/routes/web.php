<?php

use App\Http\Controllers\TodoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('todos.index');
});

Route::resource('todos', TodoController::class);

// Additional routes for todo management
Route::patch('/todos/{todo}/toggle', [TodoController::class, 'toggle'])->name('todos.toggle');
Route::get('/todos/filter/completed', [TodoController::class, 'completed'])->name('todos.completed');
Route::get('/todos/filter/pending', [TodoController::class, 'pending'])->name('todos.pending');
Route::get('/todos/filter/overdue', [TodoController::class, 'overdue'])->name('todos.overdue');
