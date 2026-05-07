<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\AdministrationController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\DocumentsController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\PlanningController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::get('/empresa/logo', [IntegrationController::class, 'companyLogo'])->name('company.logo');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [CrmController::class, 'dashboard'])->name('dashboard');

    Route::get('/clientes', [CrmController::class, 'customers'])->name('customers.index');
    Route::post('/clientes', [CrmController::class, 'storeCustomer'])->name('customers.store');
    Route::patch('/clientes/{entity}', [CrmController::class, 'updateCustomer'])->name('customers.update');
    Route::delete('/clientes/{entity}', [CrmController::class, 'destroyCustomer'])->name('customers.destroy');

    Route::get('/fornecedores', [CrmController::class, 'suppliers'])->name('suppliers.index');
    Route::post('/fornecedores', [CrmController::class, 'storeSupplier'])->name('suppliers.store');
    Route::patch('/fornecedores/{entity}', [CrmController::class, 'updateSupplier'])->name('suppliers.update');
    Route::delete('/fornecedores/{entity}', [CrmController::class, 'destroySupplier'])->name('suppliers.destroy');

    Route::get('/contactos', [CrmController::class, 'contacts'])->name('contacts.index');
    Route::post('/contactos', [CrmController::class, 'storeContact'])->name('contacts.store');
    Route::patch('/contactos/{contact}', [CrmController::class, 'updateContact'])->name('contacts.update');
    Route::delete('/contactos/{contact}', [CrmController::class, 'destroyContact'])->name('contacts.destroy');

    Route::get('/configuracoes/artigos', [CrmController::class, 'articles'])->name('articles.index');
    Route::post('/configuracoes/artigos', [CrmController::class, 'storeArticle'])->name('articles.store');
    Route::patch('/configuracoes/artigos/{article}', [CrmController::class, 'updateArticle'])->name('articles.update');
    Route::delete('/configuracoes/artigos/{article}', [CrmController::class, 'destroyArticle'])->name('articles.destroy');

    Route::get('/propostas', [DocumentsController::class, 'proposals'])->name('proposals.index');
    Route::post('/propostas', [DocumentsController::class, 'storeProposal'])->name('proposals.store');
    Route::patch('/propostas/{proposal}', [DocumentsController::class, 'updateProposal'])->name('proposals.update');
    Route::delete('/propostas/{proposal}', [DocumentsController::class, 'destroyProposal'])->name('proposals.destroy');
    Route::post('/propostas/{proposal}/converter', [DocumentsController::class, 'convertProposal'])->name('proposals.convert');
    Route::get('/propostas/{proposal}/pdf', [DocumentsController::class, 'downloadProposalPdf'])->name('proposals.pdf');

    Route::get('/encomendas-clientes', [DocumentsController::class, 'customerOrders'])->name('orders.customers.index');
    Route::post('/encomendas-clientes', [DocumentsController::class, 'storeCustomerOrder'])->name('orders.customers.store');
    Route::patch('/encomendas-clientes/{order}', [DocumentsController::class, 'updateCustomerOrder'])->name('orders.customers.update');
    Route::post('/encomendas-clientes/{order}/converter-fornecedores', [DocumentsController::class, 'convertToSupplierOrders'])->name('orders.customers.convert-suppliers');

    Route::get('/encomendas-fornecedores', [DocumentsController::class, 'supplierOrders'])->name('orders.suppliers.index');
    Route::post('/encomendas-fornecedores', [DocumentsController::class, 'storeSupplierOrder'])->name('orders.suppliers.store');
    Route::patch('/encomendas-fornecedores/{order}', [DocumentsController::class, 'updateSupplierOrder'])->name('orders.suppliers.update');

    Route::delete('/encomendas/{order}', [DocumentsController::class, 'destroyOrder'])->name('orders.destroy');
    Route::get('/encomendas/{order}/pdf', [DocumentsController::class, 'downloadOrderPdf'])->name('orders.pdf');

    Route::get('/financeiro/faturas-fornecedor', [DocumentsController::class, 'supplierInvoices'])->name('supplier-invoices.index');
    Route::post('/financeiro/faturas-fornecedor', [DocumentsController::class, 'storeSupplierInvoice'])->name('supplier-invoices.store');
    Route::patch('/financeiro/faturas-fornecedor/{supplierInvoice}', [DocumentsController::class, 'updateSupplierInvoice'])->name('supplier-invoices.update');
    Route::delete('/financeiro/faturas-fornecedor/{supplierInvoice}', [DocumentsController::class, 'destroySupplierInvoice'])->name('supplier-invoices.destroy');

    Route::get('/calendario', [PlanningController::class, 'index'])->name('calendar.index');
    Route::post('/calendario', [PlanningController::class, 'store'])->name('calendar.store');
    Route::patch('/calendario/{calendarEvent}', [PlanningController::class, 'update'])->name('calendar.update');
    Route::delete('/calendario/{calendarEvent}', [PlanningController::class, 'destroy'])->name('calendar.destroy');

    Route::get('/gestao-de-acessos/utilizadores', [AccessController::class, 'users'])->name('access.users.index');
    Route::post('/gestao-de-acessos/utilizadores', [AccessController::class, 'storeUser'])->name('access.users.store');
    Route::patch('/gestao-de-acessos/utilizadores/{user}', [AccessController::class, 'updateUser'])->name('access.users.update');
    Route::delete('/gestao-de-acessos/utilizadores/{user}', [AccessController::class, 'destroyUser'])->name('access.users.destroy');

    Route::get('/gestao-de-acessos/permissoes', [AccessController::class, 'permissionGroups'])->name('access.permissions.index');
    Route::post('/gestao-de-acessos/permissoes', [AccessController::class, 'storePermissionGroup'])->name('access.permissions.store');
    Route::patch('/gestao-de-acessos/permissoes/{role}', [AccessController::class, 'updatePermissionGroup'])->name('access.permissions.update');
    Route::delete('/gestao-de-acessos/permissoes/{role}', [AccessController::class, 'destroyPermissionGroup'])->name('access.permissions.destroy');

    Route::get('/configuracoes/entidades-paises', fn () => redirect('/configuracoes/listas/countries'));
    Route::get('/configuracoes/contactos-funcoes', fn () => redirect('/configuracoes/listas/contact-roles'));
    Route::get('/configuracoes/financeiro-iva', fn () => redirect('/configuracoes/listas/vat-rates'));
    Route::get('/configuracoes/calendario-tipos', fn () => redirect('/configuracoes/listas/calendar-types'));
    Route::get('/configuracoes/calendario-acoes', fn () => redirect('/configuracoes/listas/calendar-actions'));

    Route::get('/configuracoes/listas/{tab}', [AdministrationController::class, 'lookups'])->name('lookups.index');
    Route::post('/configuracoes/listas/{tab}', [AdministrationController::class, 'storeLookup'])->name('lookups.store');
    Route::patch('/configuracoes/listas/{tab}/{recordId}', [AdministrationController::class, 'updateLookup'])->name('lookups.update');
    Route::delete('/configuracoes/listas/{tab}/{recordId}', [AdministrationController::class, 'destroyLookup'])->name('lookups.destroy');

    Route::get('/configuracoes/empresa', [AdministrationController::class, 'company'])->name('company.edit');
    Route::post('/configuracoes/empresa', [AdministrationController::class, 'updateCompany'])->name('company.update');

    Route::get('/logs', [AdministrationController::class, 'logs'])->name('logs.index');

    Route::get('/integracoes/vies', [IntegrationController::class, 'vies'])->name('integrations.vies');
    Route::post('/integracoes/vies', [IntegrationController::class, 'vies']);

    Route::get('/ativos/artigos/{article}/foto', [IntegrationController::class, 'articlePhoto'])->name('assets.article-photo');
    Route::get('/ativos/faturas/{supplierInvoice}/documento', [IntegrationController::class, 'invoiceDocument'])->name('assets.invoice-document');
    Route::get('/ativos/faturas/{supplierInvoice}/comprovativo', [IntegrationController::class, 'invoicePaymentProof'])->name('assets.invoice-payment-proof');

    Route::get('/workspace/{module}', [AdministrationController::class, 'placeholder'])->name('workspace.placeholder');
});

require __DIR__.'/settings.php';
