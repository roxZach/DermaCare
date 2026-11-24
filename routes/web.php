<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Doctor;
use App\Http\Controllers\FileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Lab;
use App\Http\Controllers\Patient;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\Reception;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest (public) pages
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'home'])->name('home');
Route::get('/services', [HomeController::class, 'services'])->name('services');
Route::get('/doctors', [HomeController::class, 'doctors'])->name('doctors');
Route::get('/doctors/{doctor}', [HomeController::class, 'doctor'])->name('doctors.show');
Route::get('/doctors/{doctor}/slots', [HomeController::class, 'slots'])->name('doctors.slots');
Route::get('/articles', [HomeController::class, 'articles'])->name('articles');
Route::get('/articles/{article:slug}', [HomeController::class, 'article'])->name('articles.show');

// FAQ chatbot (Guests and Patients)
Route::post('/chat/ask', [ChatController::class, 'ask'])->name('chat.ask')->middleware('throttle:30,1');
Route::post('/chat/{log}/rate', [ChatController::class, 'rate'])->name('chat.rate');

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::get('/verify', [AuthController::class, 'showOtp'])->name('otp.show');
Route::post('/verify', [AuthController::class, 'verifyOtp'])->name('otp.verify')->middleware('throttle:10,1');
Route::post('/verify/resend', [AuthController::class, 'resendOtp'])->name('otp.resend')->middleware('throttle:3,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Shared pages for every logged-in user
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => redirect()->route(auth()->user()->homeRoute()))->name('dashboard');
    Route::get('/notifications', [AlertController::class, 'index'])->name('alerts.index');
    Route::get('/notifications/{alert}', [AlertController::class, 'open'])->name('alerts.open');
    Route::post('/notifications/read-all', [AlertController::class, 'readAll'])->name('alerts.readAll');

    // Protected files (images and reports are never public)
    Route::get('/files/lesion-image/{image}', [FileController::class, 'lesionImage'])->name('files.lesion');
    Route::get('/files/heatmap/{analysis}', [FileController::class, 'heatmap'])->name('files.heatmap');
    Route::get('/files/biopsy/{order}/{type}', [FileController::class, 'biopsy'])->name('files.biopsy');

    // Printable documents (browser "Save as PDF")
    Route::get('/print/prescription/{prescription}', [PrintController::class, 'prescription'])->name('print.prescription');
    Route::get('/print/report/{analysis}', [PrintController::class, 'report'])->name('print.report');
    Route::get('/print/invoice/{invoice}', [PrintController::class, 'invoice'])->name('print.invoice');
});

/*
|--------------------------------------------------------------------------
| Patient (Registered User)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:patient'])->prefix('patient')->name('patient.')->group(function () {
    Route::get('/', [Patient\PatientController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [Patient\PatientController::class, 'profile'])->name('profile');
    Route::put('/profile', [Patient\PatientController::class, 'updateProfile'])->name('profile.update');
    Route::get('/lesions', [Patient\PatientController::class, 'lesions'])->name('lesions');
    Route::get('/history', [Patient\PatientController::class, 'history'])->name('history');
    Route::get('/reports', [Patient\PatientController::class, 'reports'])->name('reports');
    Route::get('/chat-history', [ChatController::class, 'history'])->name('chat.history');

    Route::get('/appointments', [Patient\AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/book', [Patient\AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/appointments', [Patient\AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointments/{appointment}/reschedule', [Patient\AppointmentController::class, 'edit'])->name('appointments.edit');
    Route::put('/appointments/{appointment}', [Patient\AppointmentController::class, 'update'])->name('appointments.update');
    Route::post('/appointments/{appointment}/cancel', [Patient\AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/appointments/{appointment}/review', [Patient\AppointmentController::class, 'review'])->name('appointments.review');

    Route::get('/teledermatology', [Patient\TeleController::class, 'index'])->name('tele.index');
    Route::get('/teledermatology/new', [Patient\TeleController::class, 'create'])->name('tele.create');
    Route::post('/teledermatology', [Patient\TeleController::class, 'store'])->name('tele.store');
    Route::get('/teledermatology/{tele}', [Patient\TeleController::class, 'show'])->name('tele.show');

    Route::get('/bills', [Patient\BillingController::class, 'index'])->name('bills.index');
    Route::get('/bills/{invoice}/pay', [Patient\BillingController::class, 'gateway'])->name('bills.pay');
    Route::post('/bills/{invoice}/pay', [Patient\BillingController::class, 'confirm'])->name('bills.confirm');
});

/*
|--------------------------------------------------------------------------
| Dermatologist
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:dermatologist'])->prefix('doctor')->name('doctor.')->group(function () {
    Route::get('/', [Doctor\DoctorController::class, 'dashboard'])->name('dashboard');
    Route::get('/schedule', [Doctor\DoctorController::class, 'schedule'])->name('schedule');
    Route::get('/patients', [Doctor\DoctorController::class, 'patients'])->name('patients');
    Route::get('/patients/{patient}', [Doctor\DoctorController::class, 'patient'])->name('patients.show');
    Route::put('/patients/{patient}/skin', [Doctor\DoctorController::class, 'updateSkin'])->name('patients.skin');

    Route::get('/patients/{patient}/consult', [Doctor\ConsultationController::class, 'create'])->name('consult.create');
    Route::post('/patients/{patient}/consult', [Doctor\ConsultationController::class, 'store'])->name('consult.store');

    Route::post('/patients/{patient}/lesions', [Doctor\LesionController::class, 'store'])->name('lesions.store');
    Route::get('/lesions/{lesion}', [Doctor\LesionController::class, 'show'])->name('lesions.show');
    Route::post('/lesions/{lesion}/images', [Doctor\LesionController::class, 'upload'])->name('lesions.upload');
    Route::get('/lesions/{lesion}/compare', [Doctor\LesionController::class, 'compare'])->name('lesions.compare');
    Route::post('/images/{image}/analyze', [Doctor\LesionController::class, 'analyze'])->name('images.analyze');
    Route::get('/analyses', [Doctor\LesionController::class, 'pending'])->name('analyses.pending');
    Route::get('/analyses/{analysis}', [Doctor\LesionController::class, 'analysis'])->name('analyses.show');
    Route::post('/analyses/{analysis}/heatmap', [Doctor\LesionController::class, 'heatmap'])->name('analyses.heatmap');
    Route::post('/analyses/{analysis}/review', [Doctor\LesionController::class, 'review'])->name('analyses.review');

    Route::get('/patients/{patient}/prescribe', [Doctor\PrescriptionController::class, 'create'])->name('rx.create');
    Route::post('/patients/{patient}/prescribe', [Doctor\PrescriptionController::class, 'store'])->name('rx.store');

    Route::get('/patients/{patient}/biopsy', [Doctor\BiopsyController::class, 'create'])->name('biopsy.create');
    Route::post('/patients/{patient}/biopsy', [Doctor\BiopsyController::class, 'store'])->name('biopsy.store');
    Route::get('/biopsies', [Doctor\BiopsyController::class, 'index'])->name('biopsy.index');
    Route::get('/biopsies/{order}', [Doctor\BiopsyController::class, 'show'])->name('biopsy.show');
    Route::post('/biopsies/{order}/review', [Doctor\BiopsyController::class, 'review'])->name('biopsy.review');

    Route::get('/teledermatology', [Doctor\TeleController::class, 'index'])->name('tele.index');
    Route::get('/teledermatology/{tele}', [Doctor\TeleController::class, 'show'])->name('tele.show');
    Route::post('/teledermatology/{tele}/reply', [Doctor\TeleController::class, 'reply'])->name('tele.reply');
});

/*
|--------------------------------------------------------------------------
| Receptionist
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:receptionist'])->prefix('reception')->name('reception.')->group(function () {
    Route::get('/', [Reception\ReceptionController::class, 'dashboard'])->name('dashboard');
    Route::get('/patients', [Reception\ReceptionController::class, 'patients'])->name('patients');
    Route::get('/patients/new', [Reception\ReceptionController::class, 'createPatient'])->name('patients.create');
    Route::post('/patients', [Reception\ReceptionController::class, 'storePatient'])->name('patients.store');
    Route::get('/patients/{patient}', [Reception\ReceptionController::class, 'patient'])->name('patients.show');

    Route::get('/appointments', [Reception\ReceptionController::class, 'appointments'])->name('appointments');
    Route::get('/appointments/new', [Reception\ReceptionController::class, 'createAppointment'])->name('appointments.create');
    Route::post('/appointments', [Reception\ReceptionController::class, 'storeAppointment'])->name('appointments.store');
    Route::get('/appointments/{appointment}/reschedule', [Reception\ReceptionController::class, 'editAppointment'])->name('appointments.edit');
    Route::put('/appointments/{appointment}', [Reception\ReceptionController::class, 'updateAppointment'])->name('appointments.update');
    Route::post('/appointments/{appointment}/status', [Reception\ReceptionController::class, 'setStatus'])->name('appointments.status');
    Route::post('/reminders', [Reception\ReceptionController::class, 'sendReminders'])->name('reminders');

    Route::get('/invoices', [Reception\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/new', [Reception\InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [Reception\InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [Reception\InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/invoices/{invoice}/payment', [Reception\InvoiceController::class, 'pay'])->name('invoices.pay');
});

/*
|--------------------------------------------------------------------------
| Lab Technician
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:lab'])->prefix('lab')->name('lab.')->group(function () {
    Route::get('/', [Lab\LabController::class, 'dashboard'])->name('dashboard');
    Route::get('/history', [Lab\LabController::class, 'history'])->name('history');
    Route::get('/orders/{order}', [Lab\LabController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/status', [Lab\LabController::class, 'status'])->name('orders.status');
    Route::post('/orders/{order}/report', [Lab\LabController::class, 'report'])->name('orders.report');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports/export/{type}', [Admin\DashboardController::class, 'export'])->name('reports.export');
    Route::get('/audit-logs', [Admin\DashboardController::class, 'audit'])->name('audit');
    Route::get('/backup', [Admin\DashboardController::class, 'backup'])->name('backup');
    Route::get('/backup/download', [Admin\DashboardController::class, 'download'])->name('backup.download');
    Route::post('/backup/restore', [Admin\DashboardController::class, 'restore'])->name('backup.restore');

    Route::resource('users', Admin\UserController::class)->except(['show']);
    Route::get('/doctors', [Admin\DoctorController::class, 'index'])->name('doctors.index');
    Route::get('/doctors/{doctor}/edit', [Admin\DoctorController::class, 'edit'])->name('doctors.edit');
    Route::put('/doctors/{doctor}', [Admin\DoctorController::class, 'update'])->name('doctors.update');
    Route::resource('services', Admin\ServiceController::class)->except(['show']);
    Route::resource('articles', Admin\ArticleController::class)->except(['show']);
    Route::resource('faqs', Admin\FaqController::class)->except(['show']);
    Route::post('/faqs/rebuild', [Admin\FaqController::class, 'rebuild'])->name('faqs.rebuild');
    Route::get('/chatbot/unanswered', [Admin\FaqController::class, 'unanswered'])->name('faqs.unanswered');
    Route::post('/chatbot/unanswered/{log}/resolve', [Admin\FaqController::class, 'resolve'])->name('faqs.resolve');

    Route::get('/ai', [Admin\AiController::class, 'index'])->name('ai.index');
    Route::post('/ai/models', [Admin\AiController::class, 'store'])->name('ai.store');
    Route::post('/ai/models/{model}/activate', [Admin\AiController::class, 'activate'])->name('ai.activate');
    Route::get('/ai/performance', [Admin\AiController::class, 'performance'])->name('ai.performance');
});
