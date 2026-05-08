<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number;
    helper?: string;
}>();
</script>

<template>
    <article
        class="rounded-[1.75rem] border border-border/80 bg-card/95 p-5 shadow-[0_16px_35px_rgba(60,43,30,0.08)]"
    >
        <p class="text-sm font-medium text-muted-foreground">{{ label }}</p>
        <p class="mt-3 font-serif-display text-4xl text-foreground">
            {{ value }}
        </p>
        <p v-if="helper" class="mt-2 text-xs text-muted-foreground">
            {{ helper }}
        </p>
    </article>
</template>
