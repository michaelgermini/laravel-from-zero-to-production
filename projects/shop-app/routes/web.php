<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\HomeController;

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

// Routes publiques
Route::get('/', [HomeController::class, 'index'])->name('home');

// Routes des produits (publiques)
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/search', [ProductController::class, 'search'])->name('products.search');
Route::get('/products/category/{category:slug}', [ProductController::class, 'byCategory'])->name('products.category');
Route::get('/products/brand/{brand:slug}', [ProductController::class, 'byBrand'])->name('products.brand');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

// Routes d'authentification (Laravel Breeze)
require __DIR__.'/auth.php';

// Routes protégées (authentification requise)
Route::middleware('auth')->group(function () {
    
    // Routes du profil utilisateur
    Route::get('/profile', function () {
        return view('profile.edit');
    })->name('profile.edit');
    
    // Routes du panier
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('index');
        Route::post('/add/{product}', [CartController::class, 'add'])->name('add');
        Route::patch('/update/{product}', [CartController::class, 'update'])->name('update');
        Route::delete('/remove/{product}', [CartController::class, 'remove'])->name('remove');
        Route::delete('/clear', [CartController::class, 'clear'])->name('clear');
        Route::post('/apply-coupon', [CartController::class, 'applyCoupon'])->name('apply-coupon');
        Route::delete('/remove-coupon', [CartController::class, 'removeCoupon'])->name('remove-coupon');
        Route::post('/calculate-shipping', [CartController::class, 'calculateShipping'])->name('calculate-shipping');
        Route::get('/mini-cart', [CartController::class, 'miniCart'])->name('mini-cart');
        Route::post('/save-for-later', [CartController::class, 'saveForLater'])->name('save-for-later');
        Route::post('/restore', [CartController::class, 'restore'])->name('restore');
    });
    
    // Routes de checkout
    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::get('/', [CheckoutController::class, 'index'])->name('index');
        Route::post('/', [CheckoutController::class, 'store'])->name('store');
        Route::get('/success/{order}', [CheckoutController::class, 'success'])->name('success');
        Route::get('/cancel', [CheckoutController::class, 'cancel'])->name('cancel');
    });
    
    // Routes des commandes
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/search', [OrderController::class, 'search'])->name('search');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
        Route::get('/{order}/invoice', [OrderController::class, 'downloadInvoice'])->name('invoice');
    });
    
    // Routes de paiement
    Route::prefix('payment')->name('payment.')->group(function () {
        Route::post('/process', [PaymentController::class, 'process'])->name('process');
        Route::post('/webhook/stripe', [PaymentController::class, 'stripeWebhook'])->name('webhook.stripe');
        Route::get('/success', [PaymentController::class, 'success'])->name('success');
        Route::get('/cancel', [PaymentController::class, 'cancel'])->name('cancel');
    });
    
    // Routes des favoris
    Route::prefix('wishlist')->name('wishlist.')->group(function () {
        Route::get('/', [WishlistController::class, 'index'])->name('index');
        Route::post('/add/{product}', [WishlistController::class, 'add'])->name('add');
        Route::delete('/remove/{product}', [WishlistController::class, 'remove'])->name('remove');
        Route::delete('/clear', [WishlistController::class, 'clear'])->name('clear');
    });
    
    // Routes des avis
    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::post('/{product}', [ReviewController::class, 'store'])->name('store');
        Route::put('/{review}', [ReviewController::class, 'update'])->name('update');
        Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('destroy');
    });
});

// Routes admin (authentification + autorisation)
Route::middleware(['auth', 'can:manage-shop'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard admin
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // Gestion des produits
    Route::resource('products', AdminProductController::class);
    Route::post('products/{product}/toggle-featured', [AdminProductController::class, 'toggleFeatured'])->name('products.toggle-featured');
    Route::post('products/{product}/toggle-active', [AdminProductController::class, 'toggleActive'])->name('products.toggle-active');
    Route::post('products/bulk-action', [AdminProductController::class, 'bulkAction'])->name('products.bulk-action');
    
    // Gestion des catégories
    Route::resource('categories', CategoryController::class);
    
    // Gestion des marques
    Route::resource('brands', BrandController::class);
    
    // Gestion des commandes
    Route::resource('orders', AdminOrderController::class);
    Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('orders/{order}/ship', [AdminOrderController::class, 'markAsShipped'])->name('orders.ship');
    Route::post('orders/{order}/deliver', [AdminOrderController::class, 'markAsDelivered'])->name('orders.deliver');
    Route::get('orders/statistics', [AdminOrderController::class, 'statistics'])->name('orders.statistics');
    
    // Gestion des utilisateurs
    Route::resource('users', UserController::class);
    Route::patch('users/{user}/role', [UserController::class, 'updateRole'])->name('users.update-role');
    
    // Gestion des codes promo
    Route::resource('coupons', CouponController::class);
    
    // Rapports et statistiques
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/products', [ReportController::class, 'products'])->name('products');
        Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
        Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
        Route::get('/export/{type}', [ReportController::class, 'export'])->name('export');
    });
    
    // Paramètres de la boutique
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::patch('/general', [SettingController::class, 'updateGeneral'])->name('general');
        Route::patch('/shipping', [SettingController::class, 'updateShipping'])->name('shipping');
        Route::patch('/payment', [SettingController::class, 'updatePayment'])->name('payment');
        Route::patch('/tax', [SettingController::class, 'updateTax'])->name('tax');
        Route::patch('/email', [SettingController::class, 'updateEmail'])->name('email');
    });
});

// Routes API pour AJAX
Route::prefix('api')->name('api.')->group(function () {
    Route::get('/products/search', [ProductController::class, 'apiSearch'])->name('products.search');
    Route::get('/cart/count', [CartController::class, 'getCount'])->name('cart.count');
    Route::get('/cart/total', [CartController::class, 'getTotal'])->name('cart.total');
    Route::post('/cart/add', [CartController::class, 'apiAdd'])->name('cart.add');
    Route::delete('/cart/remove', [CartController::class, 'apiRemove'])->name('cart.remove');
});

// Routes de fallback
Route::fallback(function () {
    return view('errors.404');
});
