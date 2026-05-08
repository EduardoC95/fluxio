<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PageIntro from '@/components/fluxio/PageIntro.vue';
import StatusBadge from '@/components/fluxio/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    records: Array<Record<string, any>>;
    entities: Array<{ id: number; label: string }>;
    roles: Array<{ id: number; label: string }>;
    defaults: Record<string, any>;
    endpoints: Record<string, string>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Contactos', href: '/contactos' }],
    },
});

const editingId = ref<number | null>(null);

const form = useForm({
    number: props.defaults.number,
    entity_id: null as number | null,
    first_name: '',
    last_name: '',
    contact_role_id: null as number | null,
    phone: '',
    mobile: '',
    email: '',
    gdpr_consent: false,
    notes: '',
    is_active: true,
});

function resetForm() {
    editingId.value = null;
    form.reset();
    form.number = props.defaults.number;
    form.gdpr_consent = false;
    form.is_active = true;
}

function editRecord(record: Record<string, any>) {
    editingId.value = Number(record.id);
    form.number = Number(record.number);
    form.entity_id = record.entity_id;
    form.first_name = record.first_name ?? '';
    form.last_name = record.last_name ?? '';
    form.contact_role_id = record.role_id ?? null;
    form.phone = record.phone ?? '';
    form.mobile = record.mobile ?? '';
    form.email = record.email ?? '';
    form.gdpr_consent = Boolean(record.gdpr_consent);
    form.notes = record.notes ?? '';
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
    <Head title="Contactos" />

    <div class="space-y-6 px-4 py-6 md:px-6">
        <PageIntro
            eyebrow="CRM"
            title="Contactos"
            description="Relações por entidade, função, consentimento RGPD e estado operacional."
        >
            <template #actions>
                <Button type="button" variant="secondary" @click="resetForm"
                    >Novo contacto</Button
                >
            </template>
        </PageIntro>

        <section class="grid gap-6 xl:grid-cols-[1.2fr_0.95fr]">
            <article
                class="overflow-hidden rounded-[2rem] border border-border/80 bg-card/95 shadow-[0_16px_40px_rgba(60,43,30,0.08)]"
            >
                <div class="overflow-x-auto p-6">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-muted-foreground">
                            <tr class="border-b border-border">
                                <th class="pb-3 font-medium">Nome</th>
                                <th class="pb-3 font-medium">Apelido</th>
                                <th class="pb-3 font-medium">Função</th>
                                <th class="pb-3 font-medium">Entidade</th>
                                <th class="pb-3 font-medium">Telefone</th>
                                <th class="pb-3 font-medium">Telemóvel</th>
                                <th class="pb-3 font-medium">Email</th>
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
                                    {{ record.first_name }}
                                </td>
                                <td class="py-4">{{ record.last_name }}</td>
                                <td class="py-4">{{ record.role_name }}</td>
                                <td class="py-4">{{ record.entity_name }}</td>
                                <td class="py-4">{{ record.phone }}</td>
                                <td class="py-4">{{ record.mobile }}</td>
                                <td class="py-4">{{ record.email }}</td>
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
                    {{ editingId ? 'Editar contacto' : 'Novo contacto' }}
                </h2>

                <form class="mt-5 space-y-4" @submit.prevent="submit">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Número</span>
                            <Input
                                v-model="form.number"
                                type="number"
                                min="1"
                            />
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Entidade</span>
                            <select
                                v-model="form.entity_id"
                                class="flex h-10 w-full rounded-2xl border border-input bg-transparent px-4 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring/35"
                            >
                                <option :value="null">Selecionar</option>
                                <option
                                    v-for="entity in entities"
                                    :key="entity.id"
                                    :value="entity.id"
                                >
                                    {{ entity.label }}
                                </option>
                            </select>
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Nome</span>
                            <Input v-model="form.first_name" />
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Apelido</span>
                            <Input v-model="form.last_name" />
                        </label>
                    </div>

                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Função</span>
                        <select
                            v-model="form.contact_role_id"
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

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Telefone</span>
                            <Input v-model="form.phone" />
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Telemóvel</span>
                            <Input v-model="form.mobile" />
                        </label>
                    </div>

                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Email</span>
                        <Input v-model="form.email" type="email" />
                    </label>

                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Observações</span>
                        <Textarea v-model="form.notes" />
                    </label>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label
                            class="flex items-center gap-3 rounded-2xl bg-secondary/45 px-4 py-3 text-sm"
                        >
                            <input
                                v-model="form.gdpr_consent"
                                type="checkbox"
                                class="size-4 rounded"
                            />
                            Consentimento RGPD
                        </label>
                        <div
                            class="flex items-center justify-between rounded-2xl bg-secondary/45 px-4 py-3 text-sm"
                        >
                            <span>Estado</span>
                            <StatusBadge
                                :value="form.is_active ? 'Ativo' : 'Inativo'"
                            />
                        </div>
                    </div>

                    <label
                        class="flex items-center gap-3 rounded-2xl bg-secondary/45 px-4 py-3 text-sm"
                    >
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="size-4 rounded"
                        />
                        Contacto ativo
                    </label>

                    <div class="flex gap-3 pt-2">
                        <Button type="submit">{{
                            editingId ? 'Guardar alterações' : 'Criar contacto'
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
