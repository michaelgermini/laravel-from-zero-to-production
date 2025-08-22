<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Services\FormService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FormController extends Controller
{
    protected $formService;

    public function __construct(FormService $formService)
    {
        $this->middleware('auth');
        $this->formService = $formService;
    }

    /**
     * Afficher la liste des formulaires
     */
    public function index(Request $request): View
    {
        $query = Form::with(['user', 'steps']);

        // Filtrage par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrage par utilisateur
        if (!auth()->user()->isAdmin()) {
            $query->byUser(auth()->id());
        }

        // Recherche
        if ($request->has('search')) {
            $query->search($request->search);
        }

        $forms = $query->orderBy('created_at', 'desc')->paginate(12);

        return view('forms.index', compact('forms'));
    }

    /**
     * Créer un nouveau formulaire
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
            'allow_multiple_submissions' => 'boolean',
            'max_submissions' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'draft';

        $form = Form::create($validated);

        return redirect()->route('forms.edit', $form)
                       ->with('success', 'Formulaire créé avec succès !');
    }

    /**
     * Afficher un formulaire
     */
    public function show(Form $form): View
    {
        if (!$form->is_public && $form->user_id !== auth()->id()) {
            abort(403);
        }

        $form->load(['steps.fields', 'submissions']);

        return view('forms.show', compact('form'));
    }

    /**
     * Mettre à jour un formulaire
     */
    public function update(Request $request, Form $form): RedirectResponse
    {
        if ($form->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,active,inactive,archived',
            'is_public' => 'boolean',
            'allow_multiple_submissions' => 'boolean',
            'max_submissions' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $form->update($validated);

        return back()->with('success', 'Formulaire mis à jour avec succès !');
    }

    /**
     * Supprimer un formulaire
     */
    public function destroy(Form $form): RedirectResponse
    {
        if ($form->user_id !== auth()->id()) {
            abort(403);
        }

        $form->delete();

        return redirect()->route('forms.index')
                       ->with('success', 'Formulaire supprimé avec succès !');
    }

    /**
     * Dupliquer un formulaire
     */
    public function duplicate(Form $form): RedirectResponse
    {
        if ($form->user_id !== auth()->id()) {
            abort(403);
        }

        $newForm = $form->duplicate();

        return redirect()->route('forms.edit', $newForm)
                       ->with('success', 'Formulaire dupliqué avec succès !');
    }

    /**
     * Publier un formulaire
     */
    public function publish(Form $form): RedirectResponse
    {
        if ($form->user_id !== auth()->id()) {
            abort(403);
        }

        if (!$form->steps()->exists()) {
            return back()->withErrors(['error' => 'Le formulaire doit avoir au moins une étape.']);
        }

        $form->update(['status' => 'active']);

        return back()->with('success', 'Formulaire publié avec succès !');
    }

    /**
     * Afficher les statistiques
     */
    public function statistics(Form $form): View
    {
        if ($form->user_id !== auth()->id()) {
            abort(403);
        }

        $statistics = $form->getStatistics();
        $recentSubmissions = $form->submissions()
                                ->orderBy('created_at', 'desc')
                                ->limit(10)
                                ->get();

        return view('forms.statistics', compact('form', 'statistics', 'recentSubmissions'));
    }

    /**
     * Afficher le formulaire pour remplissage (public)
     */
    public function fill(string $slug): View
    {
        $form = Form::where('slug', $slug)
                   ->where('is_public', true)
                   ->where('status', 'active')
                   ->notExpired()
                   ->firstOrFail();

        if (!$form->can_accept_submissions) {
            abort(403, 'Ce formulaire n\'accepte plus de nouvelles soumissions.');
        }

        $form->load(['steps.fields']);

        return view('forms.fill', compact('form'));
    }

    /**
     * Soumettre un formulaire
     */
    public function submit(Request $request, string $slug): RedirectResponse
    {
        $form = Form::where('slug', $slug)
                   ->where('is_public', true)
                   ->where('status', 'active')
                   ->notExpired()
                   ->firstOrFail();

        if (!$form->can_accept_submissions) {
            abort(403, 'Ce formulaire n\'accepte plus de nouvelles soumissions.');
        }

        $validationRules = $this->formService->getValidationRules($form);
        $validated = $request->validate($validationRules);

        $submission = $this->formService->createSubmission($form, $validated);

        if ($form->redirect_url) {
            return redirect($form->redirect_url);
        }

        return view('forms.success', compact('form', 'submission'));
    }
}
