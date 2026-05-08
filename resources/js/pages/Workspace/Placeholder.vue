<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageIntro from '@/components/fluxio/PageIntro.vue';

defineProps<{
    module: string;
    summary: string;
}>();
</script>

<template>
    <Head :title="String(module)" />

    <div class="space-y-6 px-4 py-6 md:px-6">
        <PageIntro
            eyebrow="Roadmap"
            :title="String(module)"
            :description="summary"
        />

        <section
            class="rounded-[2rem] border border-dashed border-border bg-card/80 p-8 text-sm leading-7 text-muted-foreground"
        >
            Este espaço ficou preparado para acolher um módulo adicional com a
            mesma base visual, segurança HTTPS, storage privado e auditoria já
            definidos no Fluxio.
        </section>
    </div>
</template>
