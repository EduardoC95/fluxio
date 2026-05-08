<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PageIntro from '@/components/fluxio/PageIntro.vue';
import StatusBadge from '@/components/fluxio/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    records: Array<Record<string, any>>;
    suppliers: Array<{ id: number; label: string }>;
    supplierOrders: Array<Record<string, any>>;
    defaults: Record<string, any>;
    endpoints: Record<string, string>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Faturas Fornecedor',
                href: '/financeiro/faturas-fornecedor',
            },
        ],
    },
});

const editingId = ref<number | null>(null);

const form = useForm({
    number: props.defaults.number,
    invoice_date: props.defaults.invoice_date,
    due_date: props.defaults.due_date,
    supplier_entity_id: null as number | null,
    supplier_order_id: null as number | null,
    total: 0,
    status: props.defaults.status,
    document: null as File | null,
    payment_proof: null as File | null,
});

const showPaymentPrompt = computed(() => form.status === 'paid');

function resetForm() {
    editingId.value = null;
    form.number = props.defaults.number;
    form.invoice_date = props.defaults.invoice_date;
    form.due_date = props.defaults.due_date;
    form.supplier_entity_id = null;
    form.supplier_order_id = null;
    form.total = 0;
    form.status = props.defaults.status;
    form.document = null;
    form.payment_proof = null;
}

function editRecord(record: Record<string, any>) {
    editingId.value = Number(record.id);
    form.number = record.number;
    form.invoice_date = record.invoice_date;
    form.due_date = record.due_date;
    form.supplier_entity_id = record.supplier_entity_id ?? null;
    form.supplier_order_id = record.supplier_order_id ?? null;
    form.total = Number(record.total ?? 0);
    form.status = record.status;
    form.document = null;
    form.payment_proof = null;
}

function submit() {
    if (editingId.value) {
        form.patch(`${props.endpoints.update}/${editingId.value}`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: resetForm,
        });

        return;
    }

    form.post(props.endpoints.store, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: resetForm,
    });
}

function destroyRecord(record: Record<string, any>) {
    router.delete(`${props.endpoints.delete}/${record.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Faturas Fornecedor" />

    <div class="space-y-6 px-4 py-6 md:px-6">
        <PageIntro
            eyebrow="Financeiro"
            title="Faturas de fornecedor"
            description="Documentos privados, comprovativos de pagamento e envio por email quando a fatura passa a paga."
        >
            <template #actions>
                <Button type="button" variant="secondary" @click="resetForm"
                    >Nova fatura</Button
                >
            </template>
        </PageIntro>

        <section class="grid gap-6 xl:grid-cols-[1.15fr_0.95fr]">
            <article
                class="overflow-hidden rounded-[2rem] border border-border/80 bg-card/95 shadow-[0_16px_40px_rgba(60,43,30,0.08)]"
            >
                <div class="overflow-x-auto p-6">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-muted-foreground">
                            <tr class="border-b border-border">
                                <th class="pb-3 font-medium">Data</th>
                                <th class="pb-3 font-medium">Número</th>
                                <th class="pb-3 font-medium">Fornecedor</th>
                                <th class="pb-3 font-medium">Encomenda</th>
                                <th class="pb-3 font-medium">Documento</th>
                                <th class="pb-3 font-medium">Valor total</th>
                                <th class="pb-3 font-medium">Estado</th>
                                <th class="pb-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="record in records"
                                :key="String(record.id)"
                                class="border-b border-border/60 last:border-none"
                            >
                                <td class="py-4">{{ record.invoice_date }}</td>
                                <td class="py-4 font-medium">
                                    {{ record.number }}
                                </td>
                                <td class="py-4">{{ record.supplier_name }}</td>
                                <td class="py-4">
                                    {{ record.supplier_order_number }}
                                </td>
                                <td class="py-4">
                                    <a
                                        v-if="record.document_url"
                                        :href="record.document_url"
                                        class="underline"
                                        >Abrir</a
                                    >
                                    <span v-else class="text-muted-foreground"
                                        >Sem ficheiro</span
                                    >
                                </td>
                                <td class="py-4">
                                    € {{ Number(record.total ?? 0).toFixed(2) }}
                                </td>
                                <td class="py-4">
                                    <StatusBadge :value="record.status" />
                                </td>
                                <td class="py-4">
                                    <div class="flex gap-2">
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="secondary"
                                            @click="editRecord(record)"
                                            >Editar</Button
                                        >
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="destructive"
                                            @click="destroyRecord(record)"
                                            >Apagar</Button
                                        >
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article
                class="rounded-[2rem] border border-border/80 bg-card/95 p-6 shadow-[0_16px_40px_rgba(60,43,30,0.08)]"
            >
                <h2 class="font-serif-display text-3xl text-foreground">
                    {{ editingId ? 'Editar fatura' : 'Nova fatura' }}
                </h2>

                <form class="mt-5 space-y-4" @submit.prevent="submit">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Número</span>
                            <Input v-model="form.number" />
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Fornecedor</span>
                            <select
                                v-model="form.supplier_entity_id"
                                class="flex h-10 w-full rounded-2xl border border-input bg-transparent px-4 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring/35"
                            >
                                <option :value="null">Selecionar</option>
                                <option
                                    v-for="supplier in suppliers"
                                    :key="supplier.id"
                                    :value="supplier.id"
                                >
                                    {{ supplier.label }}
                                </option>
                            </select>
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Data da fatura</span>
                            <Input v-model="form.invoice_date" type="date" />
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Data de vencimento</span>
                            <Input v-model="form.due_date" type="date" />
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm">
                            <span class="font-medium"
                                >Encomenda fornecedor</span
                            >
                            <select
                                v-model="form.supplier_order_id"
                                class="flex h-10 w-full rounded-2xl border border-input bg-transparent px-4 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring/35"
                            >
                                <option :value="null">Selecionar</option>
                                <option
                                    v-for="order in supplierOrders"
                                    :key="String(order.id)"
                                    :value="order.id"
                                >
                                    {{ order.label }}
                                </option>
                            </select>
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Valor total</span>
                            <Input
                                v-model="form.total"
                                type="number"
                                min="0"
                                step="0.01"
                            />
                        </label>
                    </div>

                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Documento</span>
                        <input
                            type="file"
                            class="block w-full text-sm"
                            @change="
                                form.document =
                                    ($event.target as HTMLInputElement)
                                        .files?.[0] ?? null
                            "
                        />
                    </label>

                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Estado</span>
                        <select
                            v-model="form.status"
                            class="flex h-10 w-full rounded-2xl border border-input bg-transparent px-4 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring/35"
                        >
                            <option value="pending">
                                Pendente de pagamento
                            </option>
                            <option value="paid">Paga</option>
                        </select>
                    </label>

                    <div
                        v-if="showPaymentPrompt"
                        class="rounded-[1.5rem] bg-secondary/45 p-4 text-sm text-muted-foreground"
                    >
                        <p class="font-medium text-foreground">
                            Pretende enviar o comprovativo ao fornecedor?
                        </p>
                        <p class="mt-1">
                            Ao anexar o comprovativo e guardar, o Fluxio tenta
                            enviar o email com o anexo para o fornecedor.
                        </p>
                        <div class="mt-3">
                            <input
                                type="file"
                                class="block w-full text-sm"
                                @change="
                                    form.payment_proof =
                                        ($event.target as HTMLInputElement)
                                            .files?.[0] ?? null
                                "
                            />
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <Button type="submit">{{
                            editingId ? 'Guardar alterações' : 'Criar fatura'
                        }}</Button>
                        <Button
                            type="button"
                            variant="secondary"
                            @click="resetForm"
                            >Limpar</Button
                        >
                    </div>
                </form>
            </article>
        </section>
    </div>
</template>
