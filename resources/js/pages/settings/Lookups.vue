<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PageIntro from '@/components/fluxio/PageIntro.vue';
import StatusBadge from '@/components/fluxio/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    activeTab: string;
    tabs: Array<{ key: string; label: string }>;
    datasets: Record<string, Array<Record<string, any>>>;
    endpoints: Record<string, string>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Listas base', href: '/configuracoes/listas/countries' },
        ],
    },
});

const activeTab = ref(props.activeTab);
const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    iso_code: '',
    phone_prefix: '',
    description: '',
    rate: 0,
    color: '#b08968',
    is_active: true,
});

const records = computed(() => props.datasets[activeTab.value] ?? []);

function switchTab(tab: string) {
    activeTab.value = tab;
    editingId.value = null;
    form.reset();
    router.get(
        `/configuracoes/listas/${tab}`,
        {},
        { preserveScroll: true },
    );
}

function editRecord(record: Record<string, any>) {
    editingId.value = Number(record.id);
    form.name = record.name ?? '';
    form.iso_code = record.iso_code ?? '';
    form.phone_prefix = record.phone_prefix ?? '';
    form.description = record.description ?? '';
    form.rate = Number(record.rate ?? 0);
    form.color = record.color ?? '#b08968';
    form.is_active = Boolean(record.is_active);
}

function resetForm() {
    editingId.value = null;
    form.name = '';
    form.iso_code = '';
    form.phone_prefix = '';
    form.description = '';
    form.rate = 0;
    form.color = '#b08968';
    form.is_active = true;
}

function submit() {
    if (editingId.value) {
        form.patch(
            `${props.endpoints.update}/${activeTab.value}/${editingId.value}`,
            {
                preserveScroll: true,
                onSuccess: resetForm,
            },
        );

        return;
    }

    form.post(`${props.endpoints.store}/${activeTab.value}`, {
        preserveScroll: true,
        onSuccess: resetForm,
    });
}

function destroyRecord(record: Record<string, any>) {
    router.delete(`${props.endpoints.delete}/${activeTab.value}/${record.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Listas base" />

    <div class="space-y-6 px-4 py-6 md:px-6">
        <PageIntro
            eyebrow="Configurações"
            title="Listas base"
            description="Países, funções, IVA e tipologias alimentadas diretamente a partir da área de configuração."
        />

        <div class="flex flex-wrap gap-3">
            <Button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                :variant="activeTab === tab.key ? 'default' : 'secondary'"
                @click="switchTab(tab.key)"
            >
                {{ tab.label }}
            </Button>
        </div>

        <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <article
                class="overflow-hidden rounded-[2rem] border border-border/80 bg-card/95 shadow-[0_16px_40px_rgba(60,43,30,0.08)]"
            >
                <div class="overflow-x-auto p-6">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-muted-foreground">
                            <tr class="border-b border-border">
                                <th class="pb-3 font-medium">Nome</th>
                                <th class="pb-3 font-medium">Detalhe</th>
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
                                    {{ record.name }}
                                </td>
                                <td class="py-4">
                                    {{
                                        record.iso_code ||
                                        record.description ||
                                        record.rate ||
                                        record.color
                                    }}
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
            </article>

            <article
                class="rounded-[2rem] border border-border/80 bg-card/95 p-6 shadow-[0_16px_40px_rgba(60,43,30,0.08)]"
            >
                <h2 class="font-serif-display text-3xl text-foreground">
                    {{ editingId ? 'Editar registo' : 'Novo registo' }}
                </h2>

                <form class="mt-5 space-y-4" @submit.prevent="submit">
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Nome</span>
                        <Input v-model="form.name" />
                    </label>

                    <label
                        v-if="activeTab === 'countries'"
                        class="space-y-2 text-sm"
                    >
                        <span class="font-medium">ISO</span>
                        <Input v-model="form.iso_code" />
                    </label>
                    <label
                        v-if="activeTab === 'countries'"
                        class="space-y-2 text-sm"
                    >
                        <span class="font-medium">Prefixo telefónico</span>
                        <Input v-model="form.phone_prefix" />
                    </label>
                    <label
                        v-if="activeTab === 'contact-roles'"
                        class="space-y-2 text-sm"
                    >
                        <span class="font-medium">Descrição</span>
                        <Input v-model="form.description" />
                    </label>
                    <label
                        v-if="activeTab === 'vat-rates'"
                        class="space-y-2 text-sm"
                    >
                        <span class="font-medium">Taxa</span>
                        <Input v-model="form.rate" type="number" step="0.01" />
                    </label>
                    <label
                        v-if="
                            activeTab === 'calendar-types' ||
                            activeTab === 'calendar-actions'
                        "
                        class="space-y-2 text-sm"
                    >
                        <span class="font-medium">Cor</span>
                        <Input v-model="form.color" type="color" />
                    </label>

                    <label
                        class="flex items-center gap-3 rounded-2xl bg-secondary/45 px-4 py-3 text-sm"
                    >
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="size-4 rounded"
                        />
                        Registo ativo
                    </label>

                    <div class="flex gap-3 pt-2">
                        <Button type="submit">{{
                            editingId ? 'Guardar alterações' : 'Criar registo'
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
