<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh items-center justify-center px-6 py-10">
        <div class="grid w-full max-w-6xl gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <aside class="hidden rounded-[2.4rem] bg-sidebar p-8 text-sidebar-foreground shadow-[0_24px_55px_rgba(45,43,56,0.26)] lg:flex lg:flex-col lg:justify-between">
                <Link :href="home()" class="flex items-center gap-4">
                    <div class="flex size-14 items-center justify-center rounded-[1.4rem] bg-[rgba(245,235,219,0.96)] text-sm font-bold tracking-[0.3em] text-sidebar">
                        FX
                    </div>
                    <div>
                        <p class="font-serif-display text-3xl">Fluxio</p>
                        <p class="text-sm text-sidebar-foreground/70">Secure operations hub</p>
                    </div>
                </Link>

                <div class="rounded-[2rem] bg-[rgba(245,235,219,0.96)] p-6 text-foreground">
                    <p class="text-xs font-semibold tracking-[0.28em] text-muted-foreground uppercase">Acesso protegido</p>
                    <h2 class="mt-3 font-serif-display text-4xl">Entrar com clareza e segurança.</h2>
                    <p class="mt-4 text-sm leading-7 text-muted-foreground">
                        O fluxo de autenticação usa Fortify com 2FA, palavras-passe fortes e redirecionamento HTTPS obrigatório.
                    </p>
                </div>
            </aside>

            <div class="rounded-[2.4rem] border border-border/80 bg-card/95 p-6 shadow-[0_18px_45px_rgba(58,42,29,0.10)] md:p-10">
                <div class="mx-auto flex w-full max-w-md flex-col gap-8">
                    <div class="flex flex-col gap-4">
                        <Link :href="home()" class="flex items-center gap-4 font-medium lg:hidden">
                            <div class="flex size-12 items-center justify-center rounded-[1.2rem] bg-sidebar text-sm font-bold tracking-[0.3em] text-sidebar-foreground">
                                FX
                            </div>
                            <span class="font-serif-display text-2xl">Fluxio</span>
                        </Link>
                        <div class="space-y-2">
                            <h1 class="font-serif-display text-4xl text-foreground">{{ title }}</h1>
                            <p class="text-sm leading-6 text-muted-foreground">
                                {{ description }}
                            </p>
                        </div>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
