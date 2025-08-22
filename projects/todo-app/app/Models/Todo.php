<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'completed',
        'due_date',
        'priority'
    ];

    protected $casts = [
        'completed' => 'boolean',
        'due_date' => 'datetime',
    ];

    /**
     * Scope to get only completed todos
     */
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    /**
     * Scope to get only pending todos
     */
    public function scopePending($query)
    {
        return $query->where('completed', false);
    }

    /**
     * Scope to get todos by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to get overdue todos
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->where('completed', false);
    }

    /**
     * Get the priority badge color
     */
    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'high' => 'danger',
            'medium' => 'warning',
            'low' => 'success',
            default => 'secondary'
        };
    }

    /**
     * Get the status badge color
     */
    public function getStatusColorAttribute()
    {
        return $this->completed ? 'success' : 'warning';
    }

    /**
     * Get the status text
     */
    public function getStatusTextAttribute()
    {
        return $this->completed ? 'Completed' : 'Pending';
    }
}
