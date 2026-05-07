<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import ptLocale from '@fullcalendar/core/locales/pt';
import { computed, ref } from 'vue';
import PageIntro from '@/components/fluxio/PageIntro.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    records: Array<Record<string, any>>;
    users: Array<Record<string, any>>;
    entities: Array<Record<string, any>>;
    types: Array<Record<string, any>>;
    actions: Array<Record<string, any>>;
    filters: Record<string, any>;
    defaults: Record<string, any>;
    endpoints: Record<string, string>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Calendário', href: '/calendario' }],
    },
});

const editingId = ref<number | null>(null);

const filters = useForm({
    user_id: props.filters.user_id ?? null,
    entity_id: props.filters.entity_id ?? null,
});

const form = useForm({
    user_id: props.defaults.user_id ?? null,
    entity_id: null as number | null,
    calendar_type_id: null as number | null,
    calendar_action_id: null as number | null,
    scheduled_for: props.defaults.scheduled_for,
    duration_minutes: props.defaults.duration_minutes,
    shared: Boolean(props.defaults.shared),
    knowledge: Boolean(props.defaults.knowledge),
    description: '',
    status: props.defaults.status,
});

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'timeGridWeek',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },
    events: props.records,
    height: 'auto',
    locale: ptLocale,
    eventClick: ({ event }: any) => {
        const match = props.records.find((record) => Number(record.id) === Number(event.id));
        if (match) {
            editRecord(match);
        }
    },
}));

function applyFilters() {
    router.get(props.endpoints.filter, filters.data(), {
        preserveState: true,
        preserveScroll: true,
    });
}

function resetForm() {
    editingId.value = null;
    form.user_id = props.defaults.user_id ?? null;
    form.entity_id = null;
    form.calendar_type_id = null;
    form.calendar_action_id = null;
    form.scheduled_for = props.defaults.scheduled_for;
    form.duration_minutes = props.defaults.duration_minutes;
    form.shared = Boolean(props.defaults.shared);
    form.knowledge = Boolean(props.defaults.knowledge);
    form.description = '';
    form.status = props.defaults.status;
}

function editRecord(record: Record<string, any>) {
    editingId.value = Number(record.id);
    form.user_id = record.user_id ?? null;
    form.entity_id = record.entity_id ?? null;
    form.calendar_type_id = record.calendar_type_id ?? null;
    form.calendar_action_id = record.calendar_action_id ?? null;
    form.scheduled_for = record.scheduled_for;
    form.duration_minutes = Number(record.duration_minutes ?? 60);
    form.shared = Boolean(record.shared);
    form.knowledge = Boolean(record.knowledge);
    form.description = record.description ?? '';
    form.status = record.status ?? 'scheduled';
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

function destroyRecord() {
    if (!editingId.value) return;
    router.delete(`${props.endpoints.delete}/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: resetForm,
    });
}
</script>

<template>
    <Head title="Calendário" />

    <div class="space-y-6 px-4 py-6 md:px-6">
        <PageIntro
            eyebrow="Planeamento"
            title="Calendário"
            description="FullCalendar com partilha, conhecimento, entidade, tipo, ação e filtros por utilizador e entidade."
        />

        <section class="grid gap-6 xl:grid-cols-[1.35fr_0.9fr]">
            <article class="rounded-[2rem] border border-border/80 bg-card/95 p-6 shadow-[0_16px_40px_rgba(60,43,30,0.08)]">
                <div class="grid gap-4 md:grid-cols-3">
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Filtrar por utilizador</span>
                        <select v-model="filters.user_id" class="border-input flex h-10 w-full rounded-2xl border bg-transparent px-4 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring/35">
                            <option :value="null">Todos</option>
                            <option v-for="user in users" :key="String(user.id)" :value="user.id">{{ user.name }}</option>
                        </select>
                    </label>
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Filtrar por entidade</span>
                        <select v-model="filters.entity_id" class="border-input flex h-10 w-full rounded-2xl border bg-transparent px-4 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring/35">
                            <option :value="null">Todas</option>
                            <option v-for="entity in entities" :key="String(entity.id)" :value="entity.id">{{ entity.label }}</option>
                        </select>
                    </label>
                    <div class="flex items-end">
                        <Button type="button" @click="applyFilters">Aplicar filtros</Button>
                    </div>
                </div>

                <div class="mt-6 overflow-hidden rounded-[1.8rem] border border-border/70 bg-background/60 p-3">
                    <FullCalendar :options="calendarOptions" />
                </div>
            </article>

            <article class="rounded-[2rem] border border-border/80 bg-card/95 p-6 shadow-[0_16px_40px_rgba(60,43,30,0.08)]">
                <h2 class="font-serif-display text-3xl text-foreground">{{ editingId ? 'Editar atividade' : 'Nova atividade' }}</h2>

                <form class="mt-5 space-y-4" @submit.prevent="submit">
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Utilizador</span>
                        <select v-model="form.user_id" class="border-input flex h-10 w-full rounded-2xl border bg-transparent px-4 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring/35">
                            <option :value="null">Selecionar</option>
                            <option v-for="user in users" :key="String(user.id)" :value="user.id">{{ user.name }}</option>
                        </select>
                    </label>
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Entidade</span>
                        <select v-model="form.entity_id" class="border-input flex h-10 w-full rounded-2xl border bg-transparent px-4 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring/35">
                            <option :value="null">Sem entidade</option>
                            <option v-for="entity in entities" :key="String(entity.id)" :value="entity.id">{{ entity.label }}</option>
                        </select>
                    </label>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Tipo</span>
                            <select v-model="form.calendar_type_id" class="border-input flex h-10 w-full rounded-2xl border bg-transparent px-4 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring/35">
                                <option :value="null">Selecionar</option>
                                <option v-for="type in types" :key="String(type.id)" :value="type.id">{{ type.name }}</option>
                            </select>
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Ação</span>
                            <select v-model="form.calendar_action_id" class="border-input flex h-10 w-full rounded-2xl border bg-transparent px-4 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring/35">
                                <option :value="null">Selecionar</option>
                                <option v-for="action in actions" :key="String(action.id)" :value="action.id">{{ action.name }}</option>
                            </select>
                        </label>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Data e hora</span>
                            <Input v-model="form.scheduled_for" type="datetime-local" />
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Duração (min)</span>
                            <Input v-model="form.duration_minutes" type="number" min="5" />
                        </label>
                    </div>
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Descrição</span>
                        <Textarea v-model="form.description" />
                    </label>
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Estado</span>
                        <select v-model="form.status" class="border-input flex h-10 w-full rounded-2xl border bg-transparent px-4 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring/35">
                            <option value="scheduled">Agendada</option>
                            <option value="completed">Concluída</option>
                            <option value="cancelled">Cancelada</option>
                        </select>
                    </label>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="flex items-center gap-3 rounded-2xl bg-secondary/45 px-4 py-3 text-sm">
                            <input v-model="form.shared" type="checkbox" class="size-4 rounded" />
                            Partilha
                        </label>
                        <label class="flex items-center gap-3 rounded-2xl bg-secondary/45 px-4 py-3 text-sm">
                            <input v-model="form.knowledge" type="checkbox" class="size-4 rounded" />
                            Conhecimento
                        </label>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <Button type="submit">{{ editingId ? 'Guardar alterações' : 'Criar atividade' }}</Button>
                        <Button type="button" variant="secondary" @click="resetForm">Limpar</Button>
                        <Button v-if="editingId" type="button" variant="destructive" @click="destroyRecord">Apagar</Button>
                    </div>
                </form>
            </article>
        </section>
    </div>
</template>
