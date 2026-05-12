<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PageIntro from '@/components/fluxio/PageIntro.vue';
import PaginationControls from '@/components/fluxio/PaginationControls.vue';
import StatusBadge from '@/components/fluxio/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    records: Array<Record<string, any>>;
    pagination?: Record<string, any> | null;
    vatRates: Array<Record<string, any>>;
    defaults: Record<string, any>;
    endpoints: Record<string, string>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Artigos', href: '/configuracoes/artigos' }],
    },
});

const editingId = ref<number | null>(null);

const form = useForm({
    reference: '',
    name: '',
    description: '',
    price: 0,
    vat_rate_id: null as number | null,
    photo: null as File | null,
    notes: '',
    is_active: true,
});

function resetForm() {
    editingId.value = null;
    form.reset();
    form.is_active = true;
    form.price = 0;
    form.photo = null;
}

function editRecord(record: Record<string, any>) {
    editingId.value = Number(record.id);
    form.reference = record.reference ?? '';
    form.name = record.name ?? '';
    form.description = record.description ?? '';
    form.price = Number(record.price ?? 0);
    form.vat_rate_id = record.vat_rate_id ?? null;
    form.photo = null;
    form.notes = record.notes ?? '';
    form.is_active = Boolean(record.is_active);
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
    <Head title="Artigos" />

    <div class="space-y-6 px-4 py-6 md:px-6">
        <PageIntro
            eyebrow="Catálogo"
            title="Artigos"
            description="Referências, preço, IVA, fotografia e estado, preparados para propostas e encomendas."
        >
            <template #actions>
                <Button type="button" variant="secondary" @click="resetForm"
                    >Novo artigo</Button
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
                                <th class="pb-3 font-medium">Referência</th>
                                <th class="pb-3 font-medium">Foto</th>
                                <th class="pb-3 font-medium">Nome</th>
                                <th class="pb-3 font-medium">Descrição</th>
                                <th class="pb-3 font-medium">Preço</th>
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
                                <td class="py-4 font-medium">
                                    {{ record.reference }}
                                </td>
                                <td class="py-4">
                                    <img
                                        v-if="record.photo_url"
                                        :src="record.photo_url"
                                        alt=""
                                        class="size-12 rounded-2xl object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex size-12 items-center justify-center rounded-2xl bg-secondary/45 text-xs text-muted-foreground"
                                    >
                                        sem foto
                                    </div>
                                </td>
                                <td class="py-4">{{ record.name }}</td>
                                <td class="py-4">{{ record.description }}</td>
                                <td class="py-4">
                                    € {{ Number(record.price ?? 0).toFixed(2) }}
                                </td>
                                <td class="py-4">
                                    <StatusBadge
                                        :value="
                                            record.is_active
                                                ? 'Ativo'
                                                : 'Inativo'
                                        "
                                    />
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
                <PaginationControls :pagination="pagination" />
            </article>

            <article
                class="rounded-[2rem] border border-border/80 bg-card/95 p-6 shadow-[0_16px_40px_rgba(60,43,30,0.08)]"
            >
                <h2 class="font-serif-display text-3xl text-foreground">
                    {{ editingId ? 'Editar artigo' : 'Novo artigo' }}
                </h2>

                <form class="mt-5 space-y-4" @submit.prevent="submit">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Referência</span>
                            <Input v-model="form.reference" />
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Nome</span>
                            <Input v-model="form.name" />
                        </label>
                    </div>

                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Descrição</span>
                        <Textarea v-model="form.description" />
                    </label>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Preço</span>
                            <Input
                                v-model="form.price"
                                type="number"
                                min="0"
                                step="0.01"
                            />
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">IVA</span>
                            <select
                                v-model="form.vat_rate_id"
                                class="flex h-10 w-full rounded-2xl border border-input bg-transparent px-4 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring/35"
                            >
                                <option :value="null">Selecionar</option>
                                <option
                                    v-for="rate in vatRates"
                                    :key="String(rate.id)"
                                    :value="rate.id"
                                >
                                    {{ rate.label }}
                                </option>
                            </select>
                        </label>
                    </div>

                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Fotografia</span>
                        <input
                            type="file"
                            accept="image/*"
                            class="block w-full text-sm"
                            @change="
                                form.photo =
                                    ($event.target as HTMLInputElement)
                                        .files?.[0] ?? null
                            "
                        />
                    </label>

                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Observações</span>
                        <Textarea v-model="form.notes" />
                    </label>

                    <label
                        class="flex items-center gap-3 rounded-2xl bg-secondary/45 px-4 py-3 text-sm"
                    >
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="size-4 rounded"
                        />
                        Artigo ativo
                    </label>

                    <div class="flex gap-3 pt-2">
                        <Button type="submit">{{
                            editingId ? 'Guardar alterações' : 'Criar artigo'
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
