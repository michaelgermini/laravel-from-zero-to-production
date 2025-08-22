<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'title',
        'description',
        'order',
        'is_required',
        'settings',
        'validation_rules',
        'conditional_logic'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'settings' => 'array',
        'validation_rules' => 'array',
        'conditional_logic' => 'array'
    ];

    /**
     * Relation avec le formulaire parent
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Relation avec les champs de cette étape
     */
    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('order');
    }

    /**
     * Scope pour trier par ordre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Scope pour les étapes requises
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Accesseur pour vérifier si l'étape a des champs
     */
    public function getHasFieldsAttribute(): bool
    {
        return $this->fields()->exists();
    }

    /**
     * Accesseur pour le nombre de champs
     */
    public function getFieldsCountAttribute(): int
    {
        return $this->fields()->count();
    }

    /**
     * Accesseur pour les champs requis
     */
    public function getRequiredFieldsAttribute()
    {
        return $this->fields()->where('is_required', true)->get();
    }

    /**
     * Méthode pour obtenir l'étape suivante
     */
    public function getNextStep(): ?FormStep
    {
        return $this->form->steps()
                         ->where('order', '>', $this->order)
                         ->orderBy('order')
                         ->first();
    }

    /**
     * Méthode pour obtenir l'étape précédente
     */
    public function getPreviousStep(): ?FormStep
    {
        return $this->form->steps()
                         ->where('order', '<', $this->order)
                         ->orderBy('order', 'desc')
                         ->first();
    }

    /**
     * Méthode pour vérifier si c'est la première étape
     */
    public function isFirstStep(): bool
    {
        return $this->order === 1;
    }

    /**
     * Méthode pour vérifier si c'est la dernière étape
     */
    public function isLastStep(): bool
    {
        $maxOrder = $this->form->steps()->max('order');
        return $this->order === $maxOrder;
    }

    /**
     * Méthode pour obtenir le numéro d'étape (1, 2, 3...)
     */
    public function getStepNumberAttribute(): int
    {
        return $this->order;
    }

    /**
     * Méthode pour obtenir le pourcentage de progression
     */
    public function getProgressPercentageAttribute(): float
    {
        $totalSteps = $this->form->steps()->count();
        return round(($this->order / $totalSteps) * 100, 2);
    }

    /**
     * Méthode pour valider les données de l'étape
     */
    public function validateStepData(array $data): array
    {
        $rules = [];
        $messages = [];

        foreach ($this->fields as $field) {
            if ($field->is_required) {
                $rules[$field->name] = $field->validation_rules ?? 'required';
                $messages[$field->name . '.required'] = "Le champ {$field->label} est requis.";
            }
        }

        return [
            'rules' => $rules,
            'messages' => $messages
        ];
    }

    /**
     * Méthode pour évaluer la logique conditionnelle
     */
    public function evaluateConditionalLogic(array $formData): bool
    {
        if (empty($this->conditional_logic)) {
            return true;
        }

        $conditions = $this->conditional_logic;
        $result = true;

        foreach ($conditions as $condition) {
            $fieldValue = $formData[$condition['field']] ?? null;
            $operator = $condition['operator'];
            $expectedValue = $condition['value'];

            $conditionResult = match($operator) {
                'equals' => $fieldValue === $expectedValue,
                'not_equals' => $fieldValue !== $expectedValue,
                'contains' => str_contains($fieldValue, $expectedValue),
                'not_contains' => !str_contains($fieldValue, $expectedValue),
                'greater_than' => $fieldValue > $expectedValue,
                'less_than' => $fieldValue < $expectedValue,
                'greater_than_or_equal' => $fieldValue >= $expectedValue,
                'less_than_or_equal' => $fieldValue <= $expectedValue,
                'is_empty' => empty($fieldValue),
                'is_not_empty' => !empty($fieldValue),
                default => false
            };

            if ($condition['logic'] === 'AND') {
                $result = $result && $conditionResult;
            } else {
                $result = $result || $conditionResult;
            }
        }

        return $result;
    }

    /**
     * Méthode pour dupliquer une étape
     */
    public function duplicate(int $newOrder = null): FormStep
    {
        $newStep = $this->replicate();
        $newStep->title = $this->title . ' (Copie)';
        $newStep->order = $newOrder ?? ($this->form->steps()->max('order') + 1);
        $newStep->save();

        // Dupliquer les champs
        foreach ($this->fields as $field) {
            $newField = $field->replicate();
            $newField->form_step_id = $newStep->id;
            $newField->save();
        }

        return $newStep;
    }

    /**
     * Méthode pour réorganiser les étapes
     */
    public static function reorderSteps(int $formId, array $stepIds): void
    {
        foreach ($stepIds as $order => $stepId) {
            self::where('id', $stepId)
                ->where('form_id', $formId)
                ->update(['order' => $order + 1]);
        }
    }
}
