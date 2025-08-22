# E-commerce Shop - Laravel

A complete e-commerce platform built with Laravel, featuring product management, shopping cart, order processing, and Stripe payment integration.

## 🚀 Features

### ✅ **Product Management**
- **Complete CRUD** : Create, Read, Update, Delete products
- **Image management** : Multiple product images and galleries
- **Categories and brands** : Product organization system
- **Inventory tracking** : Stock management and alerts
- **Product variants** : Size, color, and other options

### ✅ **Shopping Experience**
- **Shopping cart** : Session-based cart management
- **Wishlist** : Save products for later
- **Product search** : Advanced search and filtering
- **Product reviews** : Customer ratings and feedback
- **Related products** : Smart product recommendations

### ✅ **Order Management**
- **Checkout process** : Multi-step checkout
- **Payment integration** : Stripe payment processing
- **Order tracking** : Status updates and notifications
- **Invoice generation** : PDF invoice creation
- **Order history** : Complete order management

### ✅ **User Management**
- **Customer accounts** : Registration and profiles
- **Address management** : Multiple shipping addresses
- **Order history** : Customer order tracking
- **Wishlist management** : Personal product lists

### ✅ **Admin Features**
- **Dashboard** : Sales analytics and reports
- **Order management** : Process and track orders
- **Inventory management** : Stock control
- **Customer management** : User administration
- **Sales reports** : Revenue and performance metrics

## 📁 Project Structure

```
shop-app/
├── app/
│   ├── Http/Controllers/
│   │   ├── ProductController.php    # Product management
│   │   ├── CartController.php       # Shopping cart
│   │   ├── OrderController.php      # Order processing
│   │   └── CheckoutController.php   # Checkout process
│   ├── Models/
│   │   ├── Product.php             # Product model
│   │   ├── Order.php               # Order model
│   │   ├── Cart.php                # Cart model
│   │   └── User.php                # User model
│   ├── Services/
│   │   ├── CartService.php         # Cart business logic
│   │   ├── OrderService.php        # Order processing
│   │   └── PaymentService.php      # Payment handling
│   └── Policies/
│       └── OrderPolicy.php         # Authorization policies
├── database/
│   └── migrations/
│       ├── create_products_table.php
│       ├── create_orders_table.php
│       └── create_cart_items_table.php
├── resources/
│   └── views/
│       ├── products/
│       │   ├── index.blade.php     # Product listing
│       │   ├── show.blade.php      # Product details
│       │   └── search.blade.php    # Search results
│       ├── cart/
│       │   ├── index.blade.php     # Shopping cart
│       │   └── checkout.blade.php  # Checkout form
│       ├── orders/
│       │   ├── index.blade.php     # Order history
│       │   └── show.blade.php      # Order details
│       └── admin/
│           └── dashboard.blade.php # Admin panel
├── routes/
│   └── web.php                    # Application routes
└── README.md                      # This file
```

## 🛠️ Installation

### 1. **Prerequisites**
- PHP 8.1+
- Composer
- MySQL/PostgreSQL/SQLite
- Laravel 10+
- Node.js (for frontend assets)
- Stripe account (for payments)

### 2. **Installation**
```bash
# Clone the project
git clone <repository-url>
cd shop-app

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shop_app
DB_USERNAME=root
DB_PASSWORD=

# Run migrations
php artisan migrate

# Install Laravel Breeze
composer require laravel/breeze --dev
php artisan breeze:install blade

# Install frontend assets
npm install
npm run build

# Create storage link
php artisan storage:link

# Configure Stripe (optional for testing)
STRIPE_KEY=pk_test_your_public_key
STRIPE_SECRET=sk_test_your_secret_key

# Start server
php artisan serve
```

### 3. **Access the application**
- **URL** : http://localhost:8000
- **Admin** : http://localhost:8000/admin (after login)

## 📚 Laravel Concepts Used

### **Eloquent Models with Relationships**
```php
class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'price', 'sale_price',
        'stock_quantity', 'sku', 'images', 'category_id', 'brand_id'
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items')
                    ->withPivot('quantity', 'price');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }
}
```

### **Service Classes**
```php
class CartService
{
    public function addToCart($productId, $quantity = 1)
    {
        $cart = session()->get('cart', []);
        
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $product = Product::find($productId);
            $cart[$productId] = [
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $quantity,
                'image' => $product->featured_image
            ];
        }
        
        session()->put('cart', $cart);
    }

    public function getCartTotal()
    {
        $cart = session()->get('cart', []);
        $total = 0;
        
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        return $total;
    }
}
```

### **Resource Controllers**
```php
class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::with(['category', 'brand'])->active();

        // Search
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        $products = $query->paginate(12);
        $categories = Category::all();

        return view('products.index', compact('products', 'categories'));
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'brand', 'reviews.user']);
        $relatedProducts = Product::where('category_id', $product->category_id)
                                 ->where('id', '!=', $product->id)
                                 ->limit(4)
                                 ->get();

        return view('products.show', compact('product', 'relatedProducts'));
    }
}
```

### **Form Requests for Validation**
```php
class StoreOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string|max:255',
            'shipping_address.email' => 'required|email',
            'shipping_address.phone' => 'required|string|max:20',
            'shipping_address.address' => 'required|string',
            'shipping_address.city' => 'required|string|max:100',
            'shipping_address.postal_code' => 'required|string|max:10',
            'shipping_address.country' => 'required|string|max:100',
            'billing_address' => 'required|array',
            'payment_method' => 'required|in:stripe,paypal',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
```

## 🎯 Detailed Features

### **Product Management**
- **Product catalog** : Complete product listing with filters
- **Product details** : Detailed product pages with images
- **Inventory management** : Stock tracking and alerts
- **Product variants** : Multiple options (size, color, etc.)
- **Product reviews** : Customer feedback system

### **Shopping Cart**
- **Session-based cart** : Persistent cart across sessions
- **Cart management** : Add, update, remove items
- **Cart calculations** : Subtotal, tax, shipping, total
- **Cart persistence** : Save cart for logged-in users
- **Mini cart** : Quick cart preview

### **Checkout Process**
- **Multi-step checkout** : Address, shipping, payment
- **Address management** : Multiple shipping addresses
- **Shipping options** : Different shipping methods
- **Payment processing** : Stripe integration
- **Order confirmation** : Email notifications

### **Order Management**
- **Order tracking** : Status updates and notifications
- **Order history** : Complete order management
- **Invoice generation** : PDF invoice creation
- **Order status** : Pending, processing, shipped, delivered
- **Return management** : Return and refund processing

### **Admin Features**
- **Sales dashboard** : Revenue and performance metrics
- **Order management** : Process and track orders
- **Inventory control** : Stock management
- **Customer management** : User administration
- **Reports** : Sales, inventory, customer reports

## 🔧 Customization

### **Add new product types**
1. Create new models and migrations
2. Add relationships to existing models
3. Create controllers and views
4. Update routes and policies

### **Customize payment methods**
1. Integrate additional payment gateways
2. Create custom payment services
3. Update checkout process
4. Add payment validation

### **Extend functionality**
- **Multi-vendor support** : Multiple sellers
- **Subscription products** : Recurring billing
- **Digital products** : File downloads
- **Gift cards** : Gift card system
- **Loyalty program** : Points and rewards

## 🧪 Testing

### **Unit Tests**
```bash
# Run tests
php artisan test

# Specific tests
php artisan test --filter=ProductTest
```

### **Feature Tests**
```php
class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_product()
    {
        $product = Product::factory()->create();

        $response = $this->get("/products/{$product->slug}");

        $response->assertStatus(200)
                ->assertSee($product->name);
    }

    public function test_can_add_to_cart()
    {
        $product = Product::factory()->create();

        $response = $this->post("/cart/add", [
            'product_id' => $product->id,
            'quantity' => 2
        ]);

        $response->assertRedirect();
        $this->assertSessionHas('cart');
    }
}
```

## 🚀 Deployment

### **Heroku**
```bash
# Create Heroku app
heroku create shop-app-laravel

# Configure environment variables
heroku config:set APP_KEY=base64:your-key
heroku config:set DB_CONNECTION=postgresql
heroku config:set STRIPE_KEY=your-stripe-key
heroku config:set STRIPE_SECRET=your-stripe-secret

# Deploy
git push heroku main

# Run migrations
heroku run php artisan migrate
```

### **VPS/Dedicated Server**
```bash
# Clone on server
git clone <repository-url>
cd shop-app

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 📝 API Endpoints

The shop can be extended with a REST API:

```php
// routes/api.php
Route::apiResource('products', ProductApiController::class);
Route::apiResource('orders', OrderApiController::class);
Route::post('cart/add', [CartApiController::class, 'add']);
Route::post('checkout', [CheckoutApiController::class, 'process']);
```

### **Available endpoints**
- `GET /api/products` - List products
- `GET /api/products/{id}` - Get product details
- `POST /api/cart/add` - Add to cart
- `GET /api/cart` - Get cart contents
- `POST /api/checkout` - Process checkout
- `GET /api/orders` - List orders

## 🤝 Contributing

1. Fork the project
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License. See the `LICENSE` file for details.

## 🆘 Support

For any questions or issues:
1. Check this README
2. Consult Laravel documentation
3. Open an issue on GitHub

---

**E-commerce Shop** - Complete Laravel e-commerce platform with payment integration 🛒
