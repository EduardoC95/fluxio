<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return match ($order->kind) {
            'customer' => $user->can('encomendas-clientes.read'),
            'supplier' => $user->can('encomendas-fornecedores.read'),
            default => false,
        };
    }
}
