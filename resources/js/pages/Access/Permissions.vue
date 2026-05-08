<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PageIntro from '@/components/fluxio/PageIntro.vue';
import StatusBadge from '@/components/fluxio/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    records: Array<Record<string, any>>;
    permissionModules: Array<Record<string, any>>;
    defaults: Record<string, any>;
    endpoints: Record<string, string>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Permissões', href: '/gestao-de-acessos/permissoes' },
        ],
    },
});

const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    is_active: true,
    permissions: [] as string[],
});

function resetForm() {
    editingId.value = null;
    form.name = '';
    form.is_active = true;
    form.permissions = [];
}

function togglePermission(value: string) {
    if (form.permissions.includes(value)) {
        form.permissions = form.permissions.filter(
            (permission) => permission !== value,
        );

        return;
    }

    form.permissions = [...form.permissions, value];
}

function editRecord(record: Record<string, any>) {
    editingId.value = Number(record.id);
    form.name = record.name ?? '';
    form.is_active = Boolean(record.is_active);
    form.permissions = [...(record.permissions ?? [])];
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
</script>

<template>
    <Head title="Permissões" />

    <div class="space-y-6 px-4 py-6 md:px-6">
        <PageIntro
            eyebrow="Acessos"
            title="Grupos de permissões"
            description="Matriz CRUD por menu, com base no ecossistema Spatie para papéis e permissões."
        />

        <section class="grid gap-6 xl:grid-cols-[1.05fr_1fr]">
            <article
                class="overflow-hidden rounded-[2rem] border border-border/80 bg-card/95 shadow-[0_16px_40px_rgba(60,43,30,0.08)]"
            >
                <div class="overflow-x-auto p-6">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-muted-foreground">
                            <tr class="border-b border-border">
                                <th class="pb-3 font-medium">Grupo</th>
                                <th class="pb-3 font-medium">Utilizadores</th>
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
                                <td class="py-4">{{ record.users_count }}</td>
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
                    {{ editingId ? 'Editar grupo' : 'Novo grupo' }}
                </h2>

                <form class="mt-5 space-y-4" @submit.prevent="submit">
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Nome do grupo</span>
                        <Input v-model="form.name" />
                    </label>
                    <label
                        class="flex items-center gap-3 rounded-2xl bg-secondary/45 px-4 py-3 text-sm"
                    >
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="size-4 rounded"
                        />
                        Grupo ativo
                    </label>

                    <div class="space-y-4">
                        <div
                            v-for="module in permissionModules"
                            :key="String(module.slug)"
                            class="rounded-[1.5rem] border border-border/70 p-4"
                        >
                            <p class="font-semibold text-foreground">
                                {{ module.label }}
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <label
                                    v-for="ability in module.abilities"
                                    :key="`${module.slug}.${ability}`"
                                    class="inline-flex items-center gap-2 rounded-full bg-secondary/45 px-3 py-2 text-xs font-semibold tracking-[0.12em] uppercase"
                                >
                                    <input
                                        :checked="
                                            form.permissions.includes(
                                                `${module.slug}.${ability}`,
                                            )
                                        "
                                        type="checkbox"
                                        class="size-4 rounded"
                                        @change="
                                            togglePermission(
                                                `${module.slug}.${ability}`,
                                            )
                                        "
                                    />
                                    {{ ability }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <Button type="submit">{{
                            editingId ? 'Guardar alterações' : 'Criar grupo'
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
