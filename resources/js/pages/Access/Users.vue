<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PageIntro from '@/components/fluxio/PageIntro.vue';
import PaginationControls from '@/components/fluxio/PaginationControls.vue';
import StatusBadge from '@/components/fluxio/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    records: Array<Record<string, any>>;
    pagination?: Record<string, any> | null;
    roles: Array<{ id: number; label: string }>;
    defaults: Record<string, any>;
    endpoints: Record<string, string>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Utilizadores', href: '/gestao-de-acessos/utilizadores' },
        ],
    },
});

const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    email: '',
    mobile: '',
    role_id: null as number | null,
    password: '',
    is_active: true,
});

function resetForm() {
    editingId.value = null;
    form.name = '';
    form.email = '';
    form.mobile = '';
    form.role_id = null;
    form.password = '';
    form.is_active = true;
}

function editRecord(record: Record<string, any>) {
    editingId.value = Number(record.id);
    form.name = record.name ?? '';
    form.email = record.email ?? '';
    form.mobile = record.mobile ?? '';
    form.role_id = record.role_id ?? null;
    form.password = '';
    form.is_active = Boolean(record.is_active);
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
    <Head title="Utilizadores" />

    <div class="space-y-6 px-4 py-6 md:px-6">
        <PageIntro
            eyebrow="Acessos"
            title="Utilizadores"
            description="Gestão de contas internas, grupo de permissões associado e estado ativo/inativo."
        />

        <section class="grid gap-6 xl:grid-cols-[1.1fr_0.95fr]">
            <article
                class="overflow-hidden rounded-[2rem] border border-border/80 bg-card/95 shadow-[0_16px_40px_rgba(60,43,30,0.08)]"
            >
                <div class="overflow-x-auto p-6">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-muted-foreground">
                            <tr class="border-b border-border">
                                <th class="pb-3 font-medium">Nome</th>
                                <th class="pb-3 font-medium">Email</th>
                                <th class="pb-3 font-medium">Telemóvel</th>
                                <th class="pb-3 font-medium">Grupo</th>
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
                                <td class="py-4">{{ record.email }}</td>
                                <td class="py-4">{{ record.mobile }}</td>
                                <td class="py-4">{{ record.role_name }}</td>
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
                    {{ editingId ? 'Editar utilizador' : 'Novo utilizador' }}
                </h2>

                <form class="mt-5 space-y-4" @submit.prevent="submit">
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Nome</span>
                        <Input v-model="form.name" />
                    </label>
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Email</span>
                        <Input v-model="form.email" type="email" />
                    </label>
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Telemóvel</span>
                        <Input v-model="form.mobile" />
                    </label>
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Grupo de permissões</span>
                        <select
                            v-model="form.role_id"
                            class="flex h-10 w-full rounded-2xl border border-input bg-transparent px-4 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring/35"
                        >
                            <option :value="null">Selecionar</option>
                            <option
                                v-for="role in roles"
                                :key="role.id"
                                :value="role.id"
                            >
                                {{ role.label }}
                            </option>
                        </select>
                    </label>
                    <label class="space-y-2 text-sm">
                        <span class="font-medium"
                            >Password {{ editingId ? '(opcional)' : '' }}</span
                        >
                        <Input v-model="form.password" type="password" />
                    </label>
                    <label
                        class="flex items-center gap-3 rounded-2xl bg-secondary/45 px-4 py-3 text-sm"
                    >
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="size-4 rounded"
                        />
                        Utilizador ativo
                    </label>
                    <div class="flex gap-3 pt-2">
                        <Button type="submit">{{
                            editingId
                                ? 'Guardar alterações'
                                : 'Criar utilizador'
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
