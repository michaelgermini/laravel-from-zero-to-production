# Wizard Form - Laravel

A dynamic multi-step form builder built with Laravel, featuring conditional logic, custom validation, and comprehensive form management.

## 🚀 Features

### ✅ **Form Builder**
- **Multi-step forms** : Create complex multi-step forms
- **Dynamic fields** : Various field types and configurations
- **Conditional logic** : Show/hide fields based on conditions
- **Custom validation** : Advanced validation rules
- **Form templates** : Reusable form templates

### ✅ **Form Management**
- **Form creation** : Visual form builder interface
- **Step management** : Organize forms into logical steps
- **Field configuration** : Rich field options and settings
- **Form publishing** : Public and private form access
- **Form analytics** : Submission statistics and insights

### ✅ **Submission Handling**
- **Data collection** : Secure form submission processing
- **File uploads** : Support for file attachments
- **Email notifications** : Automatic notification system
- **Data export** : Export submissions in various formats
- **Submission management** : Review and manage responses

### ✅ **Advanced Features**
- **Conditional logic** : Complex conditional field display
- **Custom themes** : Form styling and branding
- **Progress tracking** : Visual progress indicators
- **Auto-save** : Save progress automatically
- **Form validation** : Real-time validation feedback

### ✅ **Admin Features**
- **Dashboard** : Form creation and management
- **Analytics** : Submission statistics and reports
- **User management** : Form access control
- **Template library** : Pre-built form templates
- **Export tools** : Data export and reporting

## 📁 Project Structure

```
wizard-form/
├── app/
│   ├── Http/Controllers/
│   │   ├── FormController.php       # Form management
│   │   ├── FormStepController.php   # Step management
│   │   ├── FormFieldController.php  # Field management
│   │   └── SubmissionController.php # Submission handling
│   ├── Models/
│   │   ├── Form.php                # Form model
│   │   ├── FormStep.php            # Step model
│   │   ├── FormField.php           # Field model
│   │   └── FormSubmission.php      # Submission model
│   ├── Services/
│   │   ├── FormService.php         # Form business logic
│   │   ├── ValidationService.php   # Custom validation
│   │   └── ExportService.php       # Data export
│   └── Policies/
│       └── FormPolicy.php          # Authorization policies
├── database/
│   └── migrations/
│       ├── create_forms_table.php
│       ├── create_form_steps_table.php
│       ├── create_form_fields_table.php
│       └── create_form_submissions_table.php
├── resources/
│   └── views/
│       ├── forms/
│       │   ├── index.blade.php     # Form listing
│       │   ├── create.blade.php    # Form builder
│       │   ├── show.blade.php      # Form display
│       │   └── edit.blade.php      # Form editor
│       ├── submissions/
│       │   ├── index.blade.php     # Submission listing
│       │   └── show.blade.php      # Submission details
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

### 2. **Installation**
```bash
# Clone the project
git clone <repository-url>
cd wizard-form

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
DB_DATABASE=wizard_form
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

# Start server
php artisan serve
```

### 3. **Access the application**
- **URL** : http://localhost:8000
- **Admin** : http://localhost:8000/admin (after login)

## 📚 Laravel Concepts Used

### **Eloquent Models with Relationships**
```php
class Form extends Model
{
    protected $fillable = [
        'title', 'description', 'slug', 'status', 'public',
        'multiple_submissions', 'max_submissions', 'expires_at'
    ];

    // Relationships
    public function steps()
    {
        return $this->hasMany(FormStep::class)->orderBy('order');
    }

    public function submissions()
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePublic($query)
    {
        return $query->where('public', true);
    }
}
```

### **Service Classes**
```php
class FormService
{
    public function createForm($data)
    {
        $form = Form::create($data);
        
        // Create default step
        $form->steps()->create([
            'title' => 'Step 1',
            'order' => 1,
            'required' => true
        ]);
        
        return $form;
    }

    public function addFieldToStep($stepId, $fieldData)
    {
        $step = FormStep::findOrFail($stepId);
        
        $field = $step->fields()->create([
            'label' => $fieldData['label'],
            'type' => $fieldData['type'],
            'required' => $fieldData['required'] ?? false,
            'options' => $fieldData['options'] ?? null,
            'validation_rules' => $fieldData['validation_rules'] ?? null,
            'order' => $step->fields()->count() + 1
        ]);
        
        return $field;
    }

    public function processSubmission($formId, $data)
    {
        $form = Form::findOrFail($formId);
        
        // Validate submission
        $this->validateSubmission($form, $data);
        
        // Create submission
        $submission = $form->submissions()->create([
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
        
        // Send notifications
        $this->sendNotifications($form, $submission);
        
        return $submission;
    }
}
```

### **Resource Controllers**
```php
class FormController extends Controller
{
    protected $formService;

    public function __construct(FormService $formService)
    {
        $this->middleware('auth');
        $this->formService = $formService;
    }

    public function index(): View
    {
        $forms = auth()->user()->forms()
                    ->withCount('submissions')
                    ->latest()
                    ->paginate(10);

        return view('forms.index', compact('forms'));
    }

    public function store(StoreFormRequest $request): RedirectResponse
    {
        $form = $this->formService->createForm($request->validated());

        return redirect()->route('forms.edit', $form)
                        ->with('success', 'Form created successfully!');
    }

    public function show(Form $form): View
    {
        $form->load(['steps.fields', 'submissions']);
        
        return view('forms.show', compact('form'));
    }
}
```

### **Form Requests for Validation**
```php
class StoreFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:draft,active,inactive',
            'public' => 'boolean',
            'multiple_submissions' => 'boolean',
            'max_submissions' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
        ];
    }
}
```

## 🎯 Detailed Features

### **Form Builder**
- **Visual editor** : Drag-and-drop form builder
- **Field types** : Text, email, number, select, checkbox, file upload
- **Field validation** : Custom validation rules per field
- **Conditional logic** : Show/hide fields based on conditions
- **Form templates** : Pre-built form templates

### **Multi-step Forms**
- **Step management** : Create and organize form steps
- **Progress tracking** : Visual progress indicators
- **Step validation** : Validate each step before proceeding
- **Step navigation** : Previous/next step navigation
- **Auto-save** : Save progress automatically

### **Conditional Logic**
- **Field conditions** : Show/hide fields based on other field values
- **Step conditions** : Conditional step display
- **Complex logic** : Multiple conditions and operators
- **Real-time updates** : Dynamic field updates
- **Validation rules** : Conditional validation

### **Submission Management**
- **Data collection** : Secure form data collection
- **File uploads** : Support for various file types
- **Email notifications** : Automatic email notifications
- **Data export** : Export in CSV, Excel, JSON formats
- **Submission review** : Review and manage submissions

### **Analytics and Reporting**
- **Submission statistics** : View counts and completion rates
- **Response analytics** : Field response analysis
- **Performance metrics** : Form performance tracking
- **Export reports** : Generate detailed reports
- **Data visualization** : Charts and graphs

## 🔧 Customization

### **Add new field types**
1. Create new field type classes
2. Add field type to the form builder
3. Create validation rules
4. Update the form processor

### **Custom themes**
1. Create new Blade layouts
2. Customize CSS and JavaScript
3. Add theme configuration options
4. Implement theme switching

### **Extend functionality**
- **API integration** : Connect to external services
- **Payment forms** : Payment processing integration
- **File storage** : Cloud storage integration
- **Advanced analytics** : Google Analytics integration
- **Multi-language** : Internationalization support

## 🧪 Testing

### **Unit Tests**
```bash
# Run tests
php artisan test

# Specific tests
php artisan test --filter=FormTest
```

### **Feature Tests**
```php
class FormTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_form()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/forms', [
            'title' => 'Test Form',
            'description' => 'Test form description',
            'status' => 'active',
            'public' => true
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('forms', ['title' => 'Test Form']);
    }

    public function test_can_submit_form()
    {
        $form = Form::factory()->create(['status' => 'active']);
        $step = $form->steps()->create(['title' => 'Step 1', 'order' => 1]);
        $field = $step->fields()->create([
            'label' => 'Name',
            'type' => 'text',
            'required' => true
        ]);

        $response = $this->post("/forms/{$form->id}/submit", [
            'step_1' => [
                'field_' . $field->id => 'John Doe'
            ]
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('form_submissions', ['form_id' => $form->id]);
    }
}
```

## 🚀 Deployment

### **Heroku**
```bash
# Create Heroku app
heroku create wizard-form-laravel

# Configure environment variables
heroku config:set APP_KEY=base64:your-key
heroku config:set DB_CONNECTION=postgresql
heroku config:set FILESYSTEM_DISK=s3

# Deploy
git push heroku main

# Run migrations
heroku run php artisan migrate
```

### **VPS/Dedicated Server**
```bash
# Clone on server
git clone <repository-url>
cd wizard-form

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

The wizard form can be extended with a REST API:

```php
// routes/api.php
Route::apiResource('forms', FormApiController::class);
Route::post('forms/{form}/submit', [FormApiController::class, 'submit']);
Route::get('forms/{form}/submissions', [FormApiController::class, 'submissions']);
```

### **Available endpoints**
- `GET /api/forms` - List forms
- `POST /api/forms` - Create form
- `GET /api/forms/{id}` - Get form details
- `POST /api/forms/{id}/submit` - Submit form
- `GET /api/forms/{id}/submissions` - Get form submissions

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

**Wizard Form** - Dynamic multi-step form builder with conditional logic 🧙‍♂️
