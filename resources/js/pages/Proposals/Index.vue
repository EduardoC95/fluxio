<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import LineItemsEditor from '@/components/fluxio/LineItemsEditor.vue';
import PageIntro from '@/components/fluxio/PageIntro.vue';
import PaginationControls from '@/components/fluxio/PaginationControls.vue';
import StatusBadge from '@/components/fluxio/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type ArticleOption = {
    id: number;
    reference: string;
    name: string;
    description?: string | null;
    price: number;
    vat_rate?: number | null;
};

const props = defineProps<{
    records: Array<Record<string, any>>;
    pagination?: Record<string, any> | null;
    customers: Array<{ id: number; label: string }>;
    suppliers: Array<{ id: number; label: string }>;
    articles: ArticleOption[];
    defaults: Record<string, any>;
    endpoints: Record<string, string>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Propostas', href: '/propostas' }],
    },
});

const editingId = ref<number | null>(null);

const form = useForm({
    number: props.defaults.number,
    proposal_date: props.defaults.proposal_date,
    valid_until: props.defaults.valid_until,
    entity_id: null as number | null,
    status: props.defaults.status,
    line_items: [] as Record<string, any>[],
});

const totals = computed(() => {
    return form.line_items.reduce(
        (acc, row) => {
            const quantity = Number(row.quantity ?? 0);
            const unitPrice = Number(row.unit_price ?? 0);
            const vatRate = Number(row.vat_rate ?? 0);
            const subtotal = quantity * unitPrice;
            acc.subtotal += subtotal;
            acc.tax += subtotal * (vatRate / 100);
            acc.total += subtotal + subtotal * (vatRate / 100);

            return acc;
        },
        { subtotal: 0, tax: 0, total: 0 },
    );
});

function resetForm() {
    editingId.value = null;
    form.number = props.defaults.number;
    form.proposal_date = props.defaults.proposal_date;
    form.valid_until = props.defaults.valid_until;
    form.entity_id = null;
    form.status = props.defaults.status;
    form.line_items = [];
}

function editRecord(record: Record<string, any>) {
    editingId.value = Number(record.id);
    form.number = record.number;
    form.proposal_date = record.proposal_date;
    form.valid_until = record.valid_until;
    form.entity_id = record.entity_id;
    form.status = record.status;
    form.line_items = [...(record.line_items ?? [])];
}

function submit() {
    if (editingId.value) {
        form.patch(`${props.endpoints.update}/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: resetForm,
        });

        return;
    }

    form.post(props.endpoints.store, {
        preserveScroll: true,
        onSuccess: resetForm,
    });
}

function destroyRecord(record: Record<string, any>) {
    router.delete(`${props.endpoints.delete}/${record.id}`, {
        preserveScroll: true,
    });
}

function convertRecord(record: Record<string, any>) {
    router.post(
        `${props.endpoints.convert}/${record.id}/converter`,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Propostas" />

    <div class="space-y-6 px-4 py-6 md:px-6">
        <PageIntro
            eyebrow="Comercial"
            title="Propostas"
            description="Gestão de propostas com validade, linhas de artigos, custos, fornecedor por linha e exportação PDF."
        >
            <template #actions>
                <Button type="button" variant="secondary" @click="resetForm"
                    >Nova proposta</Button
                >
            </template>
        </PageIntro>

        <section class="space-y-6">
            <article
                class="overflow-hidden rounded-[2rem] border border-border/80 bg-card/95 shadow-[0_16px_40px_rgba(60,43,30,0.08)]"
            >
                <div class="overflow-x-auto p-6">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-muted-foreground">
                            <tr class="border-b border-border">
                                <th class="pb-3 font-medium">Data</th>
                                <th class="pb-3 font-medium">Número</th>
                                <th class="pb-3 font-medium">Validade</th>
                                <th class="pb-3 font-medium">Cliente</th>
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
                                <td class="py-4">{{ record.proposal_date }}</td>
                                <td class="py-4 font-medium">
                                    {{ record.number }}
                                </td>
                                <td class="py-4">{{ record.valid_until }}</td>
                                <td class="py-4">{{ record.customer_name }}</td>
                                <td class="py-4">
                                    €
                                    {{
                                        Number(
                                            record.totals?.total ?? 0,
                                        ).toFixed(2)
                                    }}
                                </td>
                                <td class="py-4">
                                    <StatusBadge :value="record.status" />
                                </td>
                                <td class="py-4">
                                    <div class="flex flex-wrap gap-2">
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
                                            variant="secondary"
                                            @click="convertRecord(record)"
                                            >Converter</Button
                                        >
                                        <a
                                            :href="`${props.endpoints.pdf}/${record.id}/pdf`"
                                            class="inline-flex items-center rounded-full border border-border px-3 py-2 text-xs font-semibold"
                                            >PDF</a
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
                <PaginationControls :pagination="pagination" />
            </article>

            <article
                class="rounded-[2rem] border border-border/80 bg-card/95 p-6 shadow-[0_16px_40px_rgba(60,43,30,0.08)]"
            >
                <div class="grid gap-4 md:grid-cols-4">
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Número</span>
                        <Input v-model="form.number" />
                    </label>
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Data da proposta</span>
                        <Input v-model="form.proposal_date" type="date" />
                    </label>
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Validade</span>
                        <Input v-model="form.valid_until" type="date" />
                    </label>
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Cliente</span>
                        <select
                            v-model="form.entity_id"
                            class="flex h-10 w-full rounded-2xl border border-input bg-transparent px-4 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring/35"
                        >
                            <option :value="null">Selecionar</option>
                            <option
                                v-for="customer in customers"
                                :key="customer.id"
                                :value="customer.id"
                            >
                                {{ customer.label }}
                            </option>
                        </select>
                    </label>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Estado</span>
                        <select
                            v-model="form.status"
                            class="flex h-10 w-full rounded-2xl border border-input bg-transparent px-4 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring/35"
                        >
                            <option value="draft">Rascunho</option>
                            <option value="closed">Fechado</option>
                        </select>
                    </label>
                    <div
                        class="rounded-[1.5rem] bg-secondary/45 px-4 py-3 text-sm"
                    >
                        <p class="font-medium text-foreground">
                            Totais calculados
                        </p>
                        <p class="mt-2 text-muted-foreground">
                            Subtotal € {{ totals.subtotal.toFixed(2) }} · IVA €
                            {{ totals.tax.toFixed(2) }} · Total €
                            {{ totals.total.toFixed(2) }}
                        </p>
                    </div>
                </div>

                <div class="mt-6">
                    <LineItemsEditor
                        v-model="form.line_items"
                        :articles="articles"
                        :suppliers="suppliers"
                        :allow-supplier-selection="true"
                    />
                </div>

                <div class="mt-6 flex gap-3">
                    <Button type="button" @click="submit">{{
                        editingId ? 'Guardar alterações' : 'Criar proposta'
                    }}</Button>
                    <Button type="button" variant="secondary" @click="resetForm"
                        >Limpar</Button
                    >
                </div>
            </article>
        </section>
    </div>
</template>
