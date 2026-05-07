<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageIntro from '@/components/fluxio/PageIntro.vue';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Logs', href: '/logs' }],
    },
});

defineProps<{
    records: Array<Record<string, unknown>>;
}>();
</script>

<template>
    <Head title="Logs" />

    <div class="space-y-6 px-4 py-6 md:px-6">
        <PageIntro
            eyebrow="Auditoria"
            title="Registo de atividade"
            description="Histórico operacional com data, utilizador, menu, ação, dispositivo e IP."
        />

        <section class="overflow-hidden rounded-[2rem] border border-border/80 bg-card/95 shadow-[0_16px_40px_rgba(60,43,30,0.08)]">
            <div class="overflow-x-auto p-6">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-muted-foreground">
                        <tr class="border-b border-border">
                            <th class="pb-3 font-medium">Data</th>
                            <th class="pb-3 font-medium">Hora</th>
                            <th class="pb-3 font-medium">Utilizador</th>
                            <th class="pb-3 font-medium">Menu</th>
                            <th class="pb-3 font-medium">Acção</th>
                            <th class="pb-3 font-medium">Dispositivo</th>
                            <th class="pb-3 font-medium">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in records" :key="String(row.id)" class="border-b border-border/60 last:border-none">
                            <td class="py-4">{{ row.date }}</td>
                            <td class="py-4">{{ row.time }}</td>
                            <td class="py-4">{{ row.user }}</td>
                            <td class="py-4">{{ row.menu }}</td>
                            <td class="py-4">{{ row.action }}</td>
                            <td class="py-4">{{ row.device }}</td>
                            <td class="py-4">{{ row.ip }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
