<script setup lang="ts">
defineProps<{
    title: string;
    description: string;
    eyebrow?: string;
}>();
</script>

<template>
    <section
        class="rounded-[2rem] border border-border/70 bg-card/95 px-6 py-6 shadow-[0_22px_60px_rgba(61,42,24,0.10)] backdrop-blur md:px-8"
    >
        <p
            v-if="eyebrow"
            class="text-xs font-semibold tracking-[0.32em] text-muted-foreground uppercase"
        >
            {{ eyebrow }}
        </p>
        <h1
            class="mt-2 font-serif-display text-3xl text-foreground md:text-4xl"
        >
            {{ title }}
        </h1>
        <p
            class="mt-3 max-w-3xl text-sm leading-6 text-muted-foreground md:text-base"
        >
            {{ description }}
        </p>
        <div v-if="$slots.actions" class="mt-5 flex flex-wrap gap-3">
            <slot name="actions" />
        </div>
    </section>
</template>
