<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DashboardRedirect;
use App\Http\Controllers\ItemPhotoController;
use App\Livewire\Auth\LoginPage;
use App\Livewire\Admin\Documents\IndexPage as AdminDocumentsIndexPage;
use App\Livewire\Admin\Documents\ShowPage as AdminDocumentsShowPage;
use App\Livewire\Admin\Logs\IndexPage as AdminLogsIndexPage;
use App\Livewire\Admin\Users\CreatePage as AdminUsersCreatePage;
use App\Livewire\Admin\Users\EditPage as AdminUsersEditPage;
use App\Livewire\Admin\Users\IndexPage as AdminUsersIndexPage;
use App\Livewire\Finance\Documents\ShowPage as FinanceDocumentsShowPage;
use App\Livewire\Finance\HistoryPage as FinanceHistoryPage;
use App\Livewire\Finance\RequestPage as FinanceRequestPage;
use App\Livewire\Purchasing\DashboardPage as PurchasingDashboardPage;
use App\Livewire\Purchasing\Documents\ShowPage as PurchasingDocumentsShowPage;
use App\Livewire\Spv\Documents\ShowPage as SpvDocumentsShowPage;
use App\Livewire\Spv\HistoryPage as SpvHistoryPage;
use App\Livewire\Spv\NonClosePage as SpvNonClosePage;
use App\Livewire\Spv\NonValidPage as SpvNonValidPage;
use App\Livewire\Spv\RequestPage as SpvRequestPage;
use App\Livewire\Warehouse\Documents\EditPage as WarehouseDocumentsEditPage;
use App\Livewire\Warehouse\Documents\ShowPage as WarehouseDocumentsShowPage;
use App\Livewire\Warehouse\HistoryPage as WarehouseHistoryPage;
use App\Livewire\Warehouse\InputPage as WarehouseInputPage;
use App\Livewire\Warehouse\NonValidPage as WarehouseNonValidPage;
use Illuminate\Support\Facades\Route;

Route::view('/', '/login')->name('login');

Route::middleware('guest')->group(function () {
    Route::get('/login', LoginPage::class)->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardRedirect::class)->name('dashboard');
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::get('/item-photos/{photo}', [ItemPhotoController::class, 'show'])->name('item-photos.show');

    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/documents', AdminDocumentsIndexPage::class)->name('admin.documents.index');
        Route::get('/documents/{document}', AdminDocumentsShowPage::class)->name('admin.documents.show');
        Route::get('/users', AdminUsersIndexPage::class)->name('admin.users.index');
        Route::get('/users/create', AdminUsersCreatePage::class)->name('admin.users.create');
        Route::get('/users/{user}/edit', AdminUsersEditPage::class)->name('admin.users.edit');
        Route::get('/logs', AdminLogsIndexPage::class)->name('admin.logs.index');
    });

    Route::prefix('warehouse')->middleware('role:warehouse')->group(function () {
        Route::get('/input', WarehouseInputPage::class)->name('warehouse.input');
        Route::get('/history', WarehouseHistoryPage::class)->name('warehouse.history');
        Route::get('/non-valid', WarehouseNonValidPage::class)->name('warehouse.non-valid');
        Route::get('/documents/{document}', WarehouseDocumentsShowPage::class)->name('warehouse.documents.show');
        Route::get('/documents/{document}/edit', WarehouseDocumentsEditPage::class)->name('warehouse.documents.edit');
    });

    Route::prefix('spv')->middleware('role:spv')->group(function () {
        Route::get('/request', SpvRequestPage::class)->name('spv.request');
        Route::get('/history', SpvHistoryPage::class)->name('spv.history');
        Route::get('/non-valid', SpvNonValidPage::class)->name('spv.non-valid');
        Route::get('/non-close', SpvNonClosePage::class)->name('spv.non-close');
        Route::get('/documents/{document}', SpvDocumentsShowPage::class)->name('spv.documents.show');
    });

    Route::prefix('finance')->middleware('role:finance')->group(function () {
        Route::get('/request', FinanceRequestPage::class)->name('finance.request');
        Route::get('/history', FinanceHistoryPage::class)->name('finance.history');
        Route::get('/documents/{document}', FinanceDocumentsShowPage::class)->name('finance.documents.show');
    });

    Route::prefix('purchasing')->middleware('role:purchasing')->group(function () {
        Route::get('/dashboard', PurchasingDashboardPage::class)->name('purchasing.dashboard');
        Route::get('/documents/{document}', PurchasingDocumentsShowPage::class)->name('purchasing.documents.show');
    });
});
