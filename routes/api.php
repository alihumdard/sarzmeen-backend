<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Admin\AccountController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\LocationController;
use App\Http\Controllers\Api\Admin\ProjectCategoryController;
use App\Http\Controllers\Api\Admin\PropertyTypeController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\StatsController as AdminStatsController;
use App\Http\Controllers\Api\Admin\PropertyController as AdminPropertyController;
use App\Http\Controllers\Api\Agency\AgentController as AgencyAgentController;
use App\Http\Controllers\Api\Agency\PropertyController as AgencyPropertyController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\My\PropertyController as MyPropertyController;
use App\Http\Controllers\Api\My\StatsController as MyStatsController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\TestimonialController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Api\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Api\Admin\InquiryController as AdminInquiryController;
use App\Http\Controllers\Api\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Api\Admin\FaqController as AdminFaqController;
use Illuminate\Support\Facades\Route;

// Public taxonomy routes (read-only, cached)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/property-types', [PropertyTypeController::class, 'index']);
Route::get('/project-categories', [ProjectCategoryController::class, 'index']);
Route::get('/locations', [LocationController::class, 'index']);

// Public property routes
Route::get('/properties', [PropertyController::class, 'index']);
Route::get('/properties/{slug}', [PropertyController::class, 'show']);

// Public project routes
Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/{slug}', [ProjectController::class, 'show']);

// Public blog routes
Route::get('/blogs', [BlogController::class, 'index']);
Route::get('/blogs/{slug}', [BlogController::class, 'show']);
Route::get('/blog-categories', [BlogController::class, 'categories']);
Route::get('/blog-tags', [BlogController::class, 'tags']);

// Public content routes
Route::get('/testimonials', [TestimonialController::class, 'index']);
Route::get('/faqs', [FaqController::class, 'index']);

// Public inquiry submission
Route::post('/inquiries', [InquiryController::class, 'store'])
    ->middleware('throttle:5,1');

// Guest routes
Route::middleware('guest')->group(function () {
    Route::post('/register', RegisterController::class)
        ->middleware('throttle:3,1');

    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])
        ->middleware('throttle:3,1');

    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
});

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', MeController::class);
    Route::post('/logout', [LoginController::class, 'logout']);

    // Email verification
    Route::post('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Owner-scoped routes (any authenticated user)
    Route::prefix('my')->group(function () {
        Route::get('/stats', MyStatsController::class);
        Route::get('/properties', [MyPropertyController::class, 'index']);
        Route::post('/properties', [MyPropertyController::class, 'store']);
        Route::put('/properties/{property}', [MyPropertyController::class, 'update']);
        Route::delete('/properties/{property}', [MyPropertyController::class, 'destroy']);
    });

    // Agency routes (agency role only)
    Route::middleware('role:agency')->prefix('agency')->group(function () {
        Route::get('/agents', [AgencyAgentController::class, 'index']);
        Route::post('/agents', [AgencyAgentController::class, 'store']);
        Route::put('/agents/{agent}', [AgencyAgentController::class, 'update']);
        Route::delete('/agents/{agent}', [AgencyAgentController::class, 'destroy']);
        Route::get('/properties', [AgencyPropertyController::class, 'index']);
    });

    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/stats', AdminStatsController::class);
        Route::get('/accounts', [AccountController::class, 'index']);
        Route::patch('/accounts/{user}/status', [AccountController::class, 'updateStatus']);
        Route::get('/roles', [RoleController::class, 'index']);
        Route::get('/roles/{role}', [RoleController::class, 'show']);
        Route::put('/roles/{role}/permissions', [RoleController::class, 'updatePermissions']);
        Route::get('/permissions', [RoleController::class, 'permissions']);

        // Taxonomy CRUD
        Route::apiResource('categories', CategoryController::class)->except('index');
        Route::apiResource('property-types', PropertyTypeController::class)->except('index');
        Route::apiResource('project-categories', ProjectCategoryController::class)->except('index');
        Route::apiResource('locations', LocationController::class)->except('index');

        // Property moderation
        Route::get('/properties', [AdminPropertyController::class, 'index']);
        Route::patch('/properties/{property}/status', [AdminPropertyController::class, 'updateStatus']);

        // Project CRUD
        Route::apiResource('projects', AdminProjectController::class);

        // Blog CRUD
        Route::apiResource('blogs', AdminBlogController::class);

        // Inquiry management
        Route::get('/inquiries', [AdminInquiryController::class, 'index']);
        Route::get('/inquiries/{inquiry}', [AdminInquiryController::class, 'show']);
        Route::patch('/inquiries/{inquiry}/status', [AdminInquiryController::class, 'updateStatus']);
        Route::delete('/inquiries/{inquiry}', [AdminInquiryController::class, 'destroy']);

        // Testimonial CRUD
        Route::apiResource('testimonials', AdminTestimonialController::class)->except('show');

        // FAQ CRUD
        Route::apiResource('faqs', AdminFaqController::class)->except('show');
    });
});
