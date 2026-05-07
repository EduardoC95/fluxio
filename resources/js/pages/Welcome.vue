<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, CalendarDays, ReceiptText, ShieldCheck, Users } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    { canRegister: true },
);
</script>

<template>
    <Head title="Fluxio" />

    <div class="min-h-screen px-6 py-8 text-foreground lg:px-10">
        <div class="mx-auto max-w-7xl">
            <header class="flex flex-col gap-4 rounded-[2rem] border border-border/70 bg-card/90 p-5 shadow-[0_18px_45px_rgba(58,42,29,0.10)] md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex size-14 items-center justify-center rounded-[1.4rem] bg-sidebar text-sm font-bold tracking-[0.3em] text-sidebar-foreground">
                        FX
                    </div>
                    <div>
                        <h1 class="font-serif-display text-3xl">Fluxio</h1>
                        <p class="text-sm text-muted-foreground">Gestão comercial, operacional e financeira.</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Link href="/login" class="rounded-full border border-border px-5 py-3 text-sm font-semibold transition hover:bg-secondary">
                        Entrar
                    </Link>
                    <Link v-if="canRegister" href="/register" class="rounded-full bg-sidebar px-5 py-3 text-sm font-semibold text-sidebar-foreground transition hover:opacity-95">
                        Criar conta inicial
                    </Link>
                </div>
            </header>

            <main class="grid gap-8 py-10 lg:grid-cols-[1.08fr_0.92fr]">
                <section class="space-y-8">
                    <div class="space-y-5">
                        <p class="text-xs font-semibold tracking-[0.36em] text-muted-foreground uppercase">Plataforma central</p>
                        <h2 class="max-w-4xl font-serif-display text-5xl leading-tight md:text-7xl">
                            Controlo claro para clientes, propostas, encomendas e calendário.
                        </h2>
                        <p class="max-w-2xl text-lg leading-8 text-muted-foreground">
                            Fluxio foi desenhada com uma linguagem quente e elegante, inspirada na referência visual, mantendo a base estrutural do ecossistema shadcn-vue.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Link href="/login" class="inline-flex items-center gap-2 rounded-full bg-sidebar px-6 py-3 text-sm font-semibold text-sidebar-foreground">
                            Abrir painel
                            <ArrowRight class="size-4" />
                        </Link>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <article class="rounded-[1.8rem] border border-border/80 bg-card/95 p-5">
                            <Users class="size-5 text-primary" />
                            <h3 class="mt-4 font-serif-display text-2xl">CRM</h3>
                            <p class="mt-2 text-sm leading-6 text-muted-foreground">Clientes, fornecedores e contactos numa base unificada.</p>
                        </article>
                        <article class="rounded-[1.8rem] border border-border/80 bg-card/95 p-5">
                            <ReceiptText class="size-5 text-primary" />
                            <h3 class="mt-4 font-serif-display text-2xl">Documentos</h3>
                            <p class="mt-2 text-sm leading-6 text-muted-foreground">Propostas, encomendas, PDFs e anexos privados.</p>
                        </article>
                        <article class="rounded-[1.8rem] border border-border/80 bg-card/95 p-5">
                            <CalendarDays class="size-5 text-primary" />
                            <h3 class="mt-4 font-serif-display text-2xl">Agenda</h3>
                            <p class="mt-2 text-sm leading-6 text-muted-foreground">Planeamento com FullCalendar e filtros por utilizador e entidade.</p>
                        </article>
                    </div>
                </section>

                <section class="rounded-[2.4rem] bg-sidebar p-5 text-sidebar-foreground shadow-[0_25px_55px_rgba(45,43,56,0.26)]">
                    <div class="grid gap-5 rounded-[2rem] bg-[rgba(245,235,219,0.96)] p-6 text-foreground">
                        <div>
                            <p class="text-xs font-semibold tracking-[0.26em] text-muted-foreground uppercase">Segurança nativa</p>
                            <h3 class="mt-2 font-serif-display text-4xl">2FA, HTTPS, cifragem e storage privado.</h3>
                        </div>
                        <div class="space-y-4">
                            <div class="rounded-[1.5rem] bg-secondary/50 p-4">
                                <ShieldCheck class="size-5 text-primary" />
                                <p class="mt-3 text-sm leading-7 text-muted-foreground">
                                    Laravel Fortify com autenticação de dois fatores, documentos fora da área pública e proteção padrão contra CSRF, XSS e SQL injection.
                                </p>
                            </div>
                            <div class="rounded-[1.5rem] border border-border/70 p-4">
                                <p class="text-sm leading-7 text-muted-foreground">
                                    A primeira conta pode ser criada a partir deste ecrã e recebe permissões administrativas para concluir a configuração inicial.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
</template>
