<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->middleware('auth');
        $this->orderService = $orderService;
    }

    /**
     * Afficher la liste des commandes
     */
    public function index(Request $request): View
    {
        $query = Order::with(['user', 'products']);

        // Filtrage par statut
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        // Filtrage par utilisateur (admin seulement)
        if (auth()->user()->isAdmin() && $request->has('user_id')) {
            $query->byUser($request->user_id);
        } else {
            // Utilisateurs normaux voient seulement leurs commandes
            $query->byUser(auth()->id());
        }

        // Tri
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $orders = $query->paginate(15);

        return view('orders.index', compact('orders'));
    }

    /**
     * Afficher une commande spécifique
     */
    public function show(Order $order): View
    {
        // Vérifier l'autorisation
        if (!auth()->user()->isAdmin() && $order->user_id !== auth()->id()) {
            abort(403);
        }

        $order->load(['user', 'products', 'orderItems', 'payments']);

        return view('orders.show', compact('order'));
    }

    /**
     * Créer une nouvelle commande
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shipping_address' => 'required|array',
            'billing_address' => 'required|array',
            'shipping_method' => 'required|string',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $order = $this->orderService->createOrder($validated);

            return redirect()->route('orders.show', $order)
                           ->with('success', 'Commande créée avec succès !');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Mettre à jour le statut d'une commande (admin seulement)
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'tracking_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $order->update($validated);

        // Envoyer une notification au client
        $order->user->notify(new OrderStatusUpdated($order));

        return back()->with('success', 'Statut de la commande mis à jour.');
    }

    /**
     * Annuler une commande
     */
    public function cancel(Order $order): RedirectResponse
    {
        // Vérifier que l'utilisateur peut annuler cette commande
        if (!auth()->user()->isAdmin() && $order->user_id !== auth()->id()) {
            abort(403);
        }

        // Vérifier que la commande peut être annulée
        if (!in_array($order->status, ['pending', 'processing'])) {
            return back()->withErrors(['error' => 'Cette commande ne peut plus être annulée.']);
        }

        $this->orderService->cancelOrder($order);

        return back()->with('success', 'Commande annulée avec succès.');
    }

    /**
     * Télécharger la facture
     */
    public function downloadInvoice(Order $order)
    {
        // Vérifier l'autorisation
        if (!auth()->user()->isAdmin() && $order->user_id !== auth()->id()) {
            abort(403);
        }

        return $this->orderService->generateInvoice($order);
    }

    /**
     * Afficher les statistiques des commandes (admin seulement)
     */
    public function statistics(): View
    {
        $this->authorize('viewStatistics', Order::class);

        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::byStatus('pending')->count(),
            'completed_orders' => Order::byStatus('delivered')->count(),
            'total_revenue' => Order::byStatus('delivered')->sum('total_amount'),
            'monthly_orders' => Order::whereMonth('created_at', now()->month)->count(),
            'monthly_revenue' => Order::whereMonth('created_at', now()->month)
                                   ->where('status', 'delivered')
                                   ->sum('total_amount'),
        ];

        $recentOrders = Order::with('user')
                           ->orderBy('created_at', 'desc')
                           ->limit(10)
                           ->get();

        return view('orders.statistics', compact('stats', 'recentOrders'));
    }

    /**
     * Rechercher des commandes
     */
    public function search(Request $request): View
    {
        $query = $request->get('q');
        
        $orders = Order::with(['user', 'products'])
                     ->where(function ($q) use ($query) {
                         $q->where('order_number', 'like', "%{$query}%")
                           ->orWhereHas('user', function ($userQuery) use ($query) {
                               $userQuery->where('name', 'like', "%{$query}%")
                                       ->orWhere('email', 'like', "%{$query}%");
                           });
                     })
                     ->orderBy('created_at', 'desc')
                     ->paginate(15);

        return view('orders.search', compact('orders', 'query'));
    }
}
