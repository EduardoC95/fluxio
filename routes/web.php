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

    Route::get('/clientes', [CrmController::class, 'customers'])->middleware('can:clientes.read')->name('customers.index');
    Route::post('/clientes', [CrmController::class, 'storeCustomer'])->middleware('can:clientes.create')->name('customers.store');
    Route::patch('/clientes/{entity}', [CrmController::class, 'updateCustomer'])->middleware('can:clientes.update')->name('customers.update');
    Route::delete('/clientes/{entity}', [CrmController::class, 'destroyCustomer'])->middleware('can:clientes.delete')->name('customers.destroy');

    Route::get('/fornecedores', [CrmController::class, 'suppliers'])->middleware('can:fornecedores.read')->name('suppliers.index');
    Route::post('/fornecedores', [CrmController::class, 'storeSupplier'])->middleware('can:fornecedores.create')->name('suppliers.store');
    Route::patch('/fornecedores/{entity}', [CrmController::class, 'updateSupplier'])->middleware('can:fornecedores.update')->name('suppliers.update');
    Route::delete('/fornecedores/{entity}', [CrmController::class, 'destroySupplier'])->middleware('can:fornecedores.delete')->name('suppliers.destroy');

    Route::get('/contactos', [CrmController::class, 'contacts'])->middleware('can:contactos.read')->name('contacts.index');
    Route::post('/contactos', [CrmController::class, 'storeContact'])->middleware('can:contactos.create')->name('contacts.store');
    Route::patch('/contactos/{contact}', [CrmController::class, 'updateContact'])->middleware('can:contactos.update')->name('contacts.update');
    Route::delete('/contactos/{contact}', [CrmController::class, 'destroyContact'])->middleware('can:contactos.delete')->name('contacts.destroy');

    Route::get('/configuracoes/artigos', [CrmController::class, 'articles'])->middleware('can:artigos.read')->name('articles.index');
    Route::post('/configuracoes/artigos', [CrmController::class, 'storeArticle'])->middleware('can:artigos.create')->name('articles.store');
    Route::patch('/configuracoes/artigos/{article}', [CrmController::class, 'updateArticle'])->middleware('can:artigos.update')->name('articles.update');
    Route::delete('/configuracoes/artigos/{article}', [CrmController::class, 'destroyArticle'])->middleware('can:artigos.delete')->name('articles.destroy');

    Route::get('/propostas', [DocumentsController::class, 'proposals'])->middleware('can:propostas.read')->name('proposals.index');
    Route::post('/propostas', [DocumentsController::class, 'storeProposal'])->middleware('can:propostas.create')->name('proposals.store');
    Route::patch('/propostas/{proposal}', [DocumentsController::class, 'updateProposal'])->middleware('can:propostas.update')->name('proposals.update');
    Route::delete('/propostas/{proposal}', [DocumentsController::class, 'destroyProposal'])->middleware('can:propostas.delete')->name('proposals.destroy');
    Route::post('/propostas/{proposal}/converter', [DocumentsController::class, 'convertProposal'])->middleware('can:propostas.update')->name('proposals.convert');
    Route::get('/propostas/{proposal}/pdf', [DocumentsController::class, 'downloadProposalPdf'])->middleware('can:propostas.read')->name('proposals.pdf');

    Route::get('/encomendas-clientes', [DocumentsController::class, 'customerOrders'])->middleware('can:encomendas-clientes.read')->name('orders.customers.index');
    Route::post('/encomendas-clientes', [DocumentsController::class, 'storeCustomerOrder'])->middleware('can:encomendas-clientes.create')->name('orders.customers.store');
    Route::patch('/encomendas-clientes/{order}', [DocumentsController::class, 'updateCustomerOrder'])->middleware('can:encomendas-clientes.update')->name('orders.customers.update');
    Route::post('/encomendas-clientes/{order}/converter-fornecedores', [DocumentsController::class, 'convertToSupplierOrders'])->middleware('can:encomendas-clientes.update')->name('orders.customers.convert-suppliers');

    Route::get('/encomendas-fornecedores', [DocumentsController::class, 'supplierOrders'])->middleware('can:encomendas-fornecedores.read')->name('orders.suppliers.index');
    Route::post('/encomendas-fornecedores', [DocumentsController::class, 'storeSupplierOrder'])->middleware('can:encomendas-fornecedores.create')->name('orders.suppliers.store');
    Route::patch('/encomendas-fornecedores/{order}', [DocumentsController::class, 'updateSupplierOrder'])->middleware('can:encomendas-fornecedores.update')->name('orders.suppliers.update');

    Route::delete('/encomendas/{order}', [DocumentsController::class, 'destroyOrder'])->middleware('permission:encomendas-clientes.delete|encomendas-fornecedores.delete')->name('orders.destroy');
    Route::get('/encomendas/{order}/pdf', [DocumentsController::class, 'downloadOrderPdf'])->middleware('permission:encomendas-clientes.read|encomendas-fornecedores.read')->name('orders.pdf');

    Route::get('/financeiro/faturas-fornecedor', [DocumentsController::class, 'supplierInvoices'])->middleware('can:faturas-fornecedores.read')->name('supplier-invoices.index');
    Route::post('/financeiro/faturas-fornecedor', [DocumentsController::class, 'storeSupplierInvoice'])->middleware('can:faturas-fornecedores.create')->name('supplier-invoices.store');
    Route::patch('/financeiro/faturas-fornecedor/{supplierInvoice}', [DocumentsController::class, 'updateSupplierInvoice'])->middleware('can:faturas-fornecedores.update')->name('supplier-invoices.update');
    Route::delete('/financeiro/faturas-fornecedor/{supplierInvoice}', [DocumentsController::class, 'destroySupplierInvoice'])->middleware('can:faturas-fornecedores.delete')->name('supplier-invoices.destroy');

    Route::get('/ordens-trabalho', [AdministrationController::class, 'placeholder'])->defaults('module', 'ordens-trabalho')->middleware('can:ordens-trabalho.read')->name('work-orders.index');
    Route::get('/financeiro', [AdministrationController::class, 'placeholder'])->defaults('module', 'financeiro')->middleware('can:financeiro.read')->name('finance.index');
    Route::get('/financeiro/contas-bancarias', [AdministrationController::class, 'placeholder'])->defaults('module', 'contas-bancarias')->middleware('can:contas-bancarias.read')->name('bank-accounts.index');
    Route::get('/financeiro/conta-corrente-clientes', [AdministrationController::class, 'placeholder'])->defaults('module', 'conta-corrente-clientes')->middleware('can:conta-corrente-clientes.read')->name('customer-current-account.index');
    Route::get('/arquivo-digital', [AdministrationController::class, 'placeholder'])->defaults('module', 'arquivo-digital')->middleware('can:arquivo-digital.read')->name('digital-archive.index');

    Route::get('/calendario', [PlanningController::class, 'index'])->middleware('can:calendario.read')->name('calendar.index');
    Route::post('/calendario', [PlanningController::class, 'store'])->middleware('can:calendario.create')->name('calendar.store');
    Route::patch('/calendario/{calendarEvent}', [PlanningController::class, 'update'])->middleware('can:calendario.update')->name('calendar.update');
    Route::delete('/calendario/{calendarEvent}', [PlanningController::class, 'destroy'])->middleware('can:calendario.delete')->name('calendar.destroy');

    Route::get('/gestao-de-acessos/utilizadores', [AccessController::class, 'users'])->middleware('can:utilizadores.read')->name('access.users.index');
    Route::post('/gestao-de-acessos/utilizadores', [AccessController::class, 'storeUser'])->middleware('can:utilizadores.create')->name('access.users.store');
    Route::patch('/gestao-de-acessos/utilizadores/{user}', [AccessController::class, 'updateUser'])->middleware('can:utilizadores.update')->name('access.users.update');
    Route::delete('/gestao-de-acessos/utilizadores/{user}', [AccessController::class, 'destroyUser'])->middleware('can:utilizadores.delete')->name('access.users.destroy');

    Route::get('/gestao-de-acessos/permissoes', [AccessController::class, 'permissionGroups'])->middleware('can:permissoes.read')->name('access.permissions.index');
    Route::post('/gestao-de-acessos/permissoes', [AccessController::class, 'storePermissionGroup'])->middleware('can:permissoes.create')->name('access.permissions.store');
    Route::patch('/gestao-de-acessos/permissoes/{role}', [AccessController::class, 'updatePermissionGroup'])->middleware('can:permissoes.update')->name('access.permissions.update');
    Route::delete('/gestao-de-acessos/permissoes/{role}', [AccessController::class, 'destroyPermissionGroup'])->middleware('can:permissoes.delete')->name('access.permissions.destroy');

    Route::get('/configuracoes/entidades-paises', fn () => redirect('/configuracoes/listas/countries'));
    Route::get('/configuracoes/contactos-funcoes', fn () => redirect('/configuracoes/listas/contact-roles'));
    Route::get('/configuracoes/financeiro-iva', fn () => redirect('/configuracoes/listas/vat-rates'));
    Route::get('/configuracoes/calendario-tipos', fn () => redirect('/configuracoes/listas/calendar-types'));
    Route::get('/configuracoes/calendario-acoes', fn () => redirect('/configuracoes/listas/calendar-actions'));

    Route::get('/configuracoes/listas/{tab}', [AdministrationController::class, 'lookups'])->middleware('can:configuracoes.read')->name('lookups.index');
    Route::post('/configuracoes/listas/{tab}', [AdministrationController::class, 'storeLookup'])->middleware('can:configuracoes.create')->name('lookups.store');
    Route::patch('/configuracoes/listas/{tab}/{recordId}', [AdministrationController::class, 'updateLookup'])->middleware('can:configuracoes.update')->name('lookups.update');
    Route::delete('/configuracoes/listas/{tab}/{recordId}', [AdministrationController::class, 'destroyLookup'])->middleware('can:configuracoes.delete')->name('lookups.destroy');

    Route::get('/configuracoes/empresa', [AdministrationController::class, 'company'])->middleware('can:empresa.read')->name('company.edit');
    Route::post('/configuracoes/empresa', [AdministrationController::class, 'updateCompany'])->middleware('can:empresa.update')->name('company.update');

    Route::get('/logs', [AdministrationController::class, 'logs'])->middleware('can:logs.read')->name('logs.index');

    Route::post('/integracoes/vies', [IntegrationController::class, 'vies'])->name('integrations.vies');

    Route::get('/ativos/artigos/{article}/foto', [IntegrationController::class, 'articlePhoto'])->name('assets.article-photo');
    Route::get('/ativos/faturas/{supplierInvoice}/documento', [IntegrationController::class, 'invoiceDocument'])->name('assets.invoice-document');
    Route::get('/ativos/faturas/{supplierInvoice}/comprovativo', [IntegrationController::class, 'invoicePaymentProof'])->name('assets.invoice-payment-proof');

    Route::get('/workspace/{module}', [AdministrationController::class, 'placeholder'])->middleware('can:workspace.read')->name('workspace.placeholder');
});

require __DIR__.'/settings.php';
