<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->middleware('auth');
        $this->cartService = $cartService;
    }

    /**
     * Afficher le panier
     */
    public function index(): View
    {
        $cart = $this->cartService->getCart();
        $cartTotal = $this->cartService->getCartTotal();
        $cartCount = $this->cartService->getCartCount();

        return view('cart.index', compact('cart', 'cartTotal', 'cartCount'));
    }

    /**
     * Ajouter un produit au panier
     */
    public function add(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:' . $product->stock_quantity,
        ]);

        try {
            $this->cartService->addToCart($product->id, $validated['quantity']);

            return back()->with('success', "{$product->name} ajouté au panier !");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Mettre à jour la quantité d'un produit
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0|max:' . $product->stock_quantity,
        ]);

        if ($validated['quantity'] == 0) {
            $this->cartService->removeFromCart($product->id);
            return back()->with('success', "{$product->name} retiré du panier.");
        }

        $this->cartService->updateQuantity($product->id, $validated['quantity']);

        return back()->with('success', "Quantité de {$product->name} mise à jour.");
    }

    /**
     * Retirer un produit du panier
     */
    public function remove(Product $product): RedirectResponse
    {
        $this->cartService->removeFromCart($product->id);

        return back()->with('success', "{$product->name} retiré du panier.");
    }

    /**
     * Vider le panier
     */
    public function clear(): RedirectResponse
    {
        $this->cartService->clearCart();

        return back()->with('success', 'Panier vidé.');
    }

    /**
     * Appliquer un code promo
     */
    public function applyCoupon(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'coupon_code' => 'required|string|max:50',
        ]);

        try {
            $discount = $this->cartService->applyCoupon($validated['coupon_code']);

            return back()->with('success', "Code promo appliqué ! Réduction : {$discount}€");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Retirer le code promo
     */
    public function removeCoupon(): RedirectResponse
    {
        $this->cartService->removeCoupon();

        return back()->with('success', 'Code promo retiré.');
    }

    /**
     * Calculer les frais de livraison
     */
    public function calculateShipping(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'zip_code' => 'required|string|max:10',
            'country' => 'required|string|max:2',
        ]);

        $shippingCost = $this->cartService->calculateShipping(
            $validated['zip_code'],
            $validated['country']
        );

        return response()->json([
            'shipping_cost' => $shippingCost,
            'total_with_shipping' => $this->cartService->getCartTotal() + $shippingCost,
        ]);
    }

    /**
     * Afficher le mini-panier (AJAX)
     */
    public function miniCart(): \Illuminate\Http\JsonResponse
    {
        $cart = $this->cartService->getCart();
        $cartCount = $this->cartService->getCartCount();
        $cartTotal = $this->cartService->getCartTotal();

        return response()->json([
            'cart' => $cart,
            'count' => $cartCount,
            'total' => $cartTotal,
        ]);
    }

    /**
     * Sauvegarder le panier pour plus tard
     */
    public function saveForLater(): RedirectResponse
    {
        $this->cartService->saveForLater();

        return back()->with('success', 'Panier sauvegardé pour plus tard.');
    }

    /**
     * Restaurer un panier sauvegardé
     */
    public function restore(): RedirectResponse
    {
        $this->cartService->restoreFromSaved();

        return back()->with('success', 'Panier restauré.');
    }
}
