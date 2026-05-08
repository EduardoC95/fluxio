<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MetricCard from '@/components/fluxio/MetricCard.vue';
import PageIntro from '@/components/fluxio/PageIntro.vue';
import StatusBadge from '@/components/fluxio/StatusBadge.vue';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }],
    },
});

defineProps<{
    stats: Array<{ label: string; value: number | string }>;
    proposalPipeline: Array<Record<string, unknown>>;
    pendingInvoices: Array<Record<string, unknown>>;
    upcomingEvents: Array<Record<string, unknown>>;
}>();
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-6 px-4 py-6 md:px-6">
        <PageIntro
            eyebrow="Fluxio"
            title="Painel operacional"
            description="Uma visão consolidada do comercial, compras, calendário e financeiro."
        />

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <MetricCard
                v-for="item in stats"
                :key="item.label"
                :label="item.label"
                :value="item.value"
            />
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.3fr_1fr]">
            <article
                class="rounded-[2rem] border border-border/80 bg-card/95 p-6 shadow-[0_16px_40px_rgba(60,43,30,0.08)]"
            >
                <h2 class="font-serif-display text-3xl text-foreground">
                    Propostas recentes
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Acompanhamento do pipeline comercial.
                </p>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-muted-foreground">
                            <tr class="border-b border-border">
                                <th class="pb-3 font-medium">Número</th>
                                <th class="pb-3 font-medium">Cliente</th>
                                <th class="pb-3 font-medium">Data</th>
                                <th class="pb-3 font-medium">Estado</th>
                                <th class="pb-3 text-right font-medium">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="proposal in proposalPipeline"
                                :key="String(proposal.id)"
                                class="border-b border-border/60 last:border-none"
                            >
                                <td class="py-4 font-medium">
                                    {{ proposal.number }}
                                </td>
                                <td class="py-4">{{ proposal.customer }}</td>
                                <td class="py-4">
                                    {{ proposal.proposal_date }}
                                </td>
                                <td class="py-4">
                                    <StatusBadge
                                        :value="String(proposal.status)"
                                    />
                                </td>
                                <td class="py-4 text-right">
                                    €
                                    {{ Number(proposal.total ?? 0).toFixed(2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <div class="space-y-6">
                <article
                    class="rounded-[2rem] border border-border/80 bg-card/95 p-6 shadow-[0_16px_40px_rgba(60,43,30,0.08)]"
                >
                    <h2 class="font-serif-display text-2xl text-foreground">
                        Financeiro pendente
                    </h2>
                    <div class="mt-4 space-y-4">
                        <div
                            v-for="invoice in pendingInvoices"
                            :key="String(invoice.id)"
                            class="rounded-[1.4rem] bg-secondary/45 p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-foreground">
                                        {{ invoice.number }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ invoice.supplier }}
                                    </p>
                                </div>
                                <StatusBadge :value="String(invoice.status)" />
                            </div>
                            <div
                                class="mt-3 flex items-center justify-between text-sm"
                            >
                                <span class="text-muted-foreground"
                                    >Vence em {{ invoice.due_date }}</span
                                >
                                <span class="font-semibold text-foreground"
                                    >€
                                    {{
                                        Number(invoice.total ?? 0).toFixed(2)
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>
                </article>

                <article
                    class="rounded-[2rem] border border-border/80 bg-card/95 p-6 shadow-[0_16px_40px_rgba(60,43,30,0.08)]"
                >
                    <h2 class="font-serif-display text-2xl text-foreground">
                        Próximas atividades
                    </h2>
                    <div class="mt-4 space-y-4">
                        <div
                            v-for="event in upcomingEvents"
                            :key="String(event.id)"
                            class="rounded-[1.4rem] border border-border/70 bg-background/65 p-4"
                        >
                            <p class="font-semibold text-foreground">
                                {{ event.title }}
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ event.scheduled_for }}
                            </p>
                            <p
                                class="mt-2 text-sm leading-6 text-muted-foreground"
                            >
                                {{ event.description }}
                            </p>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </div>
</template>
