<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PageIntro from '@/components/fluxio/PageIntro.vue';
import StatusBadge from '@/components/fluxio/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

type CountryOption = { id: number; label: string; iso_code: string };
type EntityRecord = Record<string, any>;

const props = defineProps<{
    mode: 'customers' | 'suppliers';
    title: string;
    records: EntityRecord[];
    countries: CountryOption[];
    defaults: Record<string, any>;
    endpoints: Record<string, string>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Entidades', href: '/clientes' }],
    },
});

const editingId = ref<number | null>(null);
const viesMessage = ref<string>('');

const form = useForm({
    number: props.defaults.number,
    nif: '',
    name: '',
    address: '',
    postal_code: '',
    city: '',
    country_id: null as number | null,
    phone: '',
    mobile: '',
    website: '',
    email: '',
    gdpr_consent: Boolean(props.defaults.gdpr_consent),
    notes: '',
    is_active: Boolean(props.defaults.is_active),
    is_customer: Boolean(props.defaults.is_customer),
    is_supplier: Boolean(props.defaults.is_supplier),
});

const modeDescription = computed(() =>
    props.mode === 'customers'
        ? 'Tabela única de entidades filtrada para clientes, com validação de NIF e apoio VIES.'
        : 'Tabela única de entidades filtrada para fornecedores, mantendo a mesma base de dados.',
);

function resetForm() {
    editingId.value = null;
    viesMessage.value = '';
    form.reset();
    form.number = props.defaults.number;
    form.gdpr_consent = Boolean(props.defaults.gdpr_consent);
    form.is_active = Boolean(props.defaults.is_active);
    form.is_customer = Boolean(props.defaults.is_customer);
    form.is_supplier = Boolean(props.defaults.is_supplier);
}

function editRecord(record: EntityRecord) {
    editingId.value = Number(record.id);
    viesMessage.value = '';
    form.number = Number(record.number);
    form.nif = record.nif ?? '';
    form.name = record.name ?? '';
    form.address = record.address ?? '';
    form.postal_code = record.postal_code ?? '';
    form.city = record.city ?? '';
    form.country_id = record.country_id ?? null;
    form.phone = record.phone ?? '';
    form.mobile = record.mobile ?? '';
    form.website = record.website ?? '';
    form.email = record.email ?? '';
    form.gdpr_consent = Boolean(record.gdpr_consent);
    form.notes = record.notes ?? '';
    form.is_active = Boolean(record.is_active);
    form.is_customer = Boolean(record.is_customer);
    form.is_supplier = Boolean(record.is_supplier);
}

function submit() {
    if (editingId.value) {
        form.patch(`${props.endpoints.update}/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
        return;
    }

    form.post(props.endpoints.store, {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
}

function destroyRecord(record: EntityRecord) {
    router.delete(`${props.endpoints.delete}/${record.id}`, { preserveScroll: true });
}

async function lookupVies() {
    viesMessage.value = '';

    const country = props.countries.find((option) => option.id === Number(form.country_id));

    if (!country || !form.nif) {
        viesMessage.value = 'Selecione um país e introduza o NIF para usar o VIES.';
        return;
    }

    const token = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
    const response = await fetch(props.endpoints.vies, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({
            country_code: country.iso_code,
            vat_number: form.nif,
        }),
    });

    const data = await response.json();

    if (!response.ok) {
        viesMessage.value = data.message ?? 'Não foi possível consultar o VIES.';
        return;
    }

    form.name = data.name || form.name;
    form.address = data.address || form.address;
    viesMessage.value = data.valid
        ? 'Consulta VIES concluída. Os dados disponíveis foram pré-preenchidos.'
        : 'O VIES respondeu, mas o número indicado não foi validado.';
}
</script>

<template>
    <Head :title="title" />

    <div class="space-y-6 px-4 py-6 md:px-6">
        <PageIntro eyebrow="Entidades" :title="title" :description="modeDescription">
            <template #actions>
                <Button type="button" variant="secondary" @click="resetForm">Nova ficha</Button>
            </template>
        </PageIntro>

        <section class="grid gap-6 xl:grid-cols-[1.25fr_0.95fr]">
            <article class="overflow-hidden rounded-[2rem] border border-border/80 bg-card/95 shadow-[0_16px_40px_rgba(60,43,30,0.08)]">
                <div class="overflow-x-auto p-6">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-muted-foreground">
                            <tr class="border-b border-border">
                                <th class="pb-3 font-medium">NIF</th>
                                <th class="pb-3 font-medium">Nome</th>
                                <th class="pb-3 font-medium">Telefone</th>
                                <th class="pb-3 font-medium">Telemóvel</th>
                                <th class="pb-3 font-medium">Website</th>
                                <th class="pb-3 font-medium">Email</th>
                                <th class="pb-3 font-medium">Estado</th>
                                <th class="pb-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="record in records" :key="String(record.id)" class="border-b border-border/60 last:border-none">
                                <td class="py-4">{{ record.nif }}</td>
                                <td class="py-4 font-medium">{{ record.name }}</td>
                                <td class="py-4">{{ record.phone }}</td>
                                <td class="py-4">{{ record.mobile }}</td>
                                <td class="py-4">{{ record.website }}</td>
                                <td class="py-4">{{ record.email }}</td>
                                <td class="py-4"><StatusBadge :value="record.is_active ? 'Ativo' : 'Inativo'" /></td>
                                <td class="py-4">
                                    <div class="flex gap-2">
                                        <Button type="button" size="sm" variant="secondary" @click="editRecord(record)">Editar</Button>
                                        <Button type="button" size="sm" variant="destructive" @click="destroyRecord(record)">Apagar</Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="rounded-[2rem] border border-border/80 bg-card/95 p-6 shadow-[0_16px_40px_rgba(60,43,30,0.08)]">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="font-serif-display text-3xl text-foreground">
                            {{ editingId ? 'Editar entidade' : 'Nova entidade' }}
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Uma entidade pode existir como cliente e fornecedor em simultâneo.
                        </p>
                    </div>
                    <Button type="button" variant="secondary" @click="lookupVies">VIES</Button>
                </div>

                <p v-if="viesMessage" class="mt-4 rounded-2xl bg-secondary/55 px-4 py-3 text-sm text-muted-foreground">
                    {{ viesMessage }}
                </p>

                <form class="mt-5 space-y-4" @submit.prevent="submit">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Número</span>
                            <Input v-model="form.number" type="number" min="1" />
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">NIF</span>
                            <Input v-model="form.nif" />
                        </label>
                    </div>

                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Nome</span>
                        <Input v-model="form.name" />
                    </label>

                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Morada</span>
                        <Input v-model="form.address" />
                    </label>

                    <div class="grid gap-4 md:grid-cols-3">
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Código postal</span>
                            <Input v-model="form.postal_code" placeholder="0000-000" />
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Localidade</span>
                            <Input v-model="form.city" />
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">País</span>
                            <select v-model="form.country_id" class="border-input flex h-10 w-full rounded-2xl border bg-transparent px-4 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring/35">
                                <option :value="null">Selecionar</option>
                                <option v-for="country in countries" :key="country.id" :value="country.id">
                                    {{ country.label }}
                                </option>
                            </select>
                        </label>
                    </div>

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

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Website</span>
                            <Input v-model="form.website" />
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Email</span>
                            <Input v-model="form.email" type="email" />
                        </label>
                    </div>

                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Observações</span>
                        <Textarea v-model="form.notes" />
                    </label>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="flex items-center gap-3 rounded-2xl bg-secondary/45 px-4 py-3 text-sm">
                            <input v-model="form.gdpr_consent" type="checkbox" class="size-4 rounded" />
                            Consentimento RGPD
                        </label>
                        <label class="flex items-center gap-3 rounded-2xl bg-secondary/45 px-4 py-3 text-sm">
                            <input v-model="form.is_active" type="checkbox" class="size-4 rounded" />
                            Ativo
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="flex items-center gap-3 rounded-2xl bg-secondary/45 px-4 py-3 text-sm">
                            <input v-model="form.is_customer" type="checkbox" class="size-4 rounded" />
                            Cliente
                        </label>
                        <label class="flex items-center gap-3 rounded-2xl bg-secondary/45 px-4 py-3 text-sm">
                            <input v-model="form.is_supplier" type="checkbox" class="size-4 rounded" />
                            Fornecedor
                        </label>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <Button type="submit">{{ editingId ? 'Guardar alterações' : 'Criar entidade' }}</Button>
                        <Button type="button" variant="secondary" @click="resetForm">Limpar</Button>
                    </div>
                </form>
            </article>
        </section>
    </div>
</template>
