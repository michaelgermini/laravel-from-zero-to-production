<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Form extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'slug',
        'status',
        'is_public',
        'allow_multiple_submissions',
        'max_submissions',
        'expires_at',
        'user_id',
        'settings',
        'theme',
        'notification_email',
        'success_message',
        'redirect_url'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'allow_multiple_submissions' => 'boolean',
        'expires_at' => 'datetime',
        'settings' => 'array',
        'max_submissions' => 'integer'
    ];

    /**
     * Relation avec l'utilisateur créateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec les étapes du formulaire
     */
    public function steps(): HasMany
    {
        return $this->hasMany(FormStep::class)->orderBy('order');
    }

    /**
     * Relation avec les soumissions
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    /**
     * Relation avec les champs du formulaire
     */
    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class);
    }

    /**
     * Relation avec les validations personnalisées
     */
    public function validations(): HasMany
    {
        return $this->hasMany(FormValidation::class);
    }

    /**
     * Relation avec les conditions (logique conditionnelle)
     */
    public function conditions(): HasMany
    {
        return $this->hasMany(FormCondition::class);
    }

    /**
     * Relation avec les intégrations
     */
    public function integrations(): BelongsToMany
    {
        return $this->belongsToMany(Integration::class, 'form_integrations')
                    ->withPivot('settings', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Scope pour les formulaires publics
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope pour les formulaires actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope pour les formulaires non expirés
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope pour les formulaires par utilisateur
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour rechercher par titre ou description
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Accesseur pour vérifier si le formulaire est expiré
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Accesseur pour vérifier si le formulaire peut recevoir des soumissions
     */
    public function getCanAcceptSubmissionsAttribute(): bool
    {
        if ($this->is_expired) {
            return false;
        }

        if (!$this->allow_multiple_submissions && $this->submissions()->exists()) {
            return false;
        }

        if ($this->max_submissions && $this->submissions()->count() >= $this->max_submissions) {
            return false;
        }

        return true;
    }

    /**
     * Accesseur pour le nombre de soumissions
     */
    public function getSubmissionsCountAttribute(): int
    {
        return $this->submissions()->count();
    }

    /**
     * Accesseur pour le taux de conversion
     */
    public function getConversionRateAttribute(): float
    {
        $views = $this->submissions()->sum('views_count');
        $submissions = $this->submissions()->count();

        if ($views === 0) {
            return 0;
        }

        return round(($submissions / $views) * 100, 2);
    }

    /**
     * Accesseur pour le temps moyen de remplissage
     */
    public function getAverageCompletionTimeAttribute(): ?int
    {
        $completedSubmissions = $this->submissions()
                                   ->whereNotNull('completed_at')
                                   ->whereNotNull('started_at');

        if ($completedSubmissions->count() === 0) {
            return null;
        }

        $totalTime = $completedSubmissions->get()->sum(function ($submission) {
            return $submission->started_at->diffInSeconds($submission->completed_at);
        });

        return round($totalTime / $completedSubmissions->count());
    }

    /**
     * Accesseur pour le statut coloré
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'draft' => 'warning',
            'inactive' => 'danger',
            'archived' => 'secondary',
            default => 'info'
        };
    }

    /**
     * Accesseur pour le statut textuel
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'active' => 'Actif',
            'draft' => 'Brouillon',
            'inactive' => 'Inactif',
            'archived' => 'Archivé',
            default => 'Inconnu'
        };
    }

    /**
     * Méthode pour dupliquer un formulaire
     */
    public function duplicate(): Form
    {
        $newForm = $this->replicate();
        $newForm->title = $this->title . ' (Copie)';
        $newForm->slug = $this->slug . '-copy-' . time();
        $newForm->status = 'draft';
        $newForm->save();

        // Dupliquer les étapes
        foreach ($this->steps as $step) {
            $newStep = $step->replicate();
            $newStep->form_id = $newForm->id;
            $newStep->save();

            // Dupliquer les champs de l'étape
            foreach ($step->fields as $field) {
                $newField = $field->replicate();
                $newField->form_id = $newForm->id;
                $newField->form_step_id = $newStep->id;
                $newField->save();
            }
        }

        // Dupliquer les validations
        foreach ($this->validations as $validation) {
            $newValidation = $validation->replicate();
            $newValidation->form_id = $newForm->id;
            $newValidation->save();
        }

        return $newForm;
    }

    /**
     * Méthode pour obtenir les statistiques du formulaire
     */
    public function getStatistics(): array
    {
        $submissions = $this->submissions();
        $recentSubmissions = $submissions->where('created_at', '>=', now()->subDays(30));

        return [
            'total_submissions' => $submissions->count(),
            'recent_submissions' => $recentSubmissions->count(),
            'conversion_rate' => $this->conversion_rate,
            'average_completion_time' => $this->average_completion_time,
            'completion_rate' => $this->getCompletionRate(),
            'abandonment_rate' => $this->getAbandonmentRate(),
            'popular_fields' => $this->getPopularFields(),
            'submissions_by_day' => $this->getSubmissionsByDay(),
        ];
    }

    /**
     * Méthode pour calculer le taux de completion
     */
    private function getCompletionRate(): float
    {
        $total = $this->submissions()->count();
        $completed = $this->submissions()->whereNotNull('completed_at')->count();

        if ($total === 0) {
            return 0;
        }

        return round(($completed / $total) * 100, 2);
    }

    /**
     * Méthode pour calculer le taux d'abandon
     */
    private function getAbandonmentRate(): float
    {
        $total = $this->submissions()->count();
        $abandoned = $this->submissions()->whereNull('completed_at')->count();

        if ($total === 0) {
            return 0;
        }

        return round(($abandoned / $total) * 100, 2);
    }

    /**
     * Méthode pour obtenir les champs les plus populaires
     */
    private function getPopularFields(): array
    {
        return $this->fields()
                   ->withCount('submissions')
                   ->orderBy('submissions_count', 'desc')
                   ->limit(5)
                   ->get()
                   ->map(function ($field) {
                       return [
                           'name' => $field->label,
                           'count' => $field->submissions_count,
                           'percentage' => round(($field->submissions_count / $this->submissions()->count()) * 100, 2)
                       ];
                   })
                   ->toArray();
    }

    /**
     * Méthode pour obtenir les soumissions par jour
     */
    private function getSubmissionsByDay(): array
    {
        return $this->submissions()
                   ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                   ->where('created_at', '>=', now()->subDays(30))
                   ->groupBy('date')
                   ->orderBy('date')
                   ->get()
                   ->pluck('count', 'date')
                   ->toArray();
    }

    /**
     * Boot method pour générer automatiquement le slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($form) {
            if (empty($form->slug)) {
                $form->slug = \Str::slug($form->title);
            }
        });

        static::updating(function ($form) {
            if ($form->isDirty('title') && empty($form->slug)) {
                $form->slug = \Str::slug($form->title);
            }
        });
    }
}
