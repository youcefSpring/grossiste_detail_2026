<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::post('locale', LocaleController::class)->name('locale');

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('ajax/products', [ProductController::class, 'search'])
        ->middleware('can:product.view')
        ->name('products.search');

    Route::get('products', [ProductController::class, 'index'])
        ->middleware('can:product.view')
        ->name('products.index');

    Route::middleware('can:product.manage')->group(function () {
        Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    Route::middleware('can:stock.view')->group(function () {
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
    });

    Route::middleware('can:stock.adjust')->group(function () {
        Route::get('inventory/{product}/adjust', [InventoryController::class, 'editStock'])->name('inventory.adjust');
        Route::put('inventory/{product}', [InventoryController::class, 'updateStock'])->name('inventory.update');
    });

    Route::middleware('can:supplier.view')->group(function () {
        Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])
            ->whereNumber('supplier')->name('suppliers.show');
    });

    Route::middleware('can:supplier.manage')->group(function () {
        Route::get('suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    });

    Route::post('suppliers/{supplier}/pay', [SupplierController::class, 'pay'])
        ->middleware('can:payment.record')->name('suppliers.pay');

    Route::middleware('can:purchase.view')->group(function () {
        Route::get('purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('purchases/{purchase}', [PurchaseController::class, 'show'])
            ->whereNumber('purchase')->name('purchases.show');
    });

    Route::middleware('can:purchase.create')->group(function () {
        Route::get('purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('purchases', [PurchaseController::class, 'store'])->name('purchases.store');
    });

    Route::middleware('can:sale.view')->group(function () {
        Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('sales/{sale}', [SaleController::class, 'show'])->whereNumber('sale')->name('sales.show');
        Route::get('sales/{sale}/invoice', [SaleController::class, 'invoice'])
            ->whereNumber('sale')->name('sales.invoice');
    });

    Route::middleware('can:sale.create')->group(function () {
        Route::get('pos', [SaleController::class, 'create'])->name('sales.create');
        Route::post('sales', [SaleController::class, 'store'])->name('sales.store');
    });

    Route::post('sales/{sale}/void', [SaleController::class, 'void'])
        ->middleware('can:sale.void')->name('sales.void');

    Route::middleware('can:customer.view')->group(function () {
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])
            ->whereNumber('customer')->name('customers.show');
    });

    Route::middleware('can:customer.manage')->group(function () {
        Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    });

    Route::post('customers/{customer}/collect', [CustomerController::class, 'collect'])
        ->middleware('can:payment.record')->name('customers.collect');

    Route::middleware('can:sale.return')->group(function () {
        Route::get('returns', [ReturnController::class, 'index'])->name('returns.index');
        Route::get('returns/new', [ReturnController::class, 'createSaleReturn'])->name('returns.create');
        Route::post('returns/sale/{sale}', [ReturnController::class, 'storeSaleReturn'])->name('returns.store');
        Route::get('returns/{return}', [ReturnController::class, 'show'])->whereNumber('return')->name('returns.show');
    });

    Route::middleware('can:sale.exchange')->group(function () {
        Route::get('exchanges/new', [ReturnController::class, 'createExchange'])->name('exchanges.create');
        Route::post('exchanges/sale/{sale}', [ReturnController::class, 'storeExchange'])->name('exchanges.store');
    });

    Route::middleware('can:purchase.return')->group(function () {
        Route::get('purchases/{purchase}/return', [ReturnController::class, 'createPurchaseReturn'])
            ->name('purchase-returns.create');
        Route::post('purchases/{purchase}/return', [ReturnController::class, 'storePurchaseReturn'])
            ->name('purchase-returns.store');
    });

    Route::get('expenses', [ExpenseController::class, 'index'])
        ->middleware('can:expense.view')->name('expenses.index');

    Route::middleware('can:expense.manage')->group(function () {
        Route::get('expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    });

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');

    Route::get('audit', [AuditController::class, 'index'])
        ->middleware('can:audit.view')->name('audit.index');

    Route::middleware('can:user.manage')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
    });

    Route::middleware('can:settings.manage')->group(function () {
        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});
