<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const companyName = computed(() => {
    const company = page.props.company as { name?: string } | undefined;

    return company?.name || 'Fluxio';
});
</script>

<template>
    <div class="flex min-w-0 items-center gap-3 group-data-[collapsible=icon]:justify-center">
        <div
            class="flex size-11 items-center justify-center rounded-2xl border border-sidebar-border/70 bg-[rgba(245,235,219,0.96)] text-[0.65rem] font-bold tracking-[0.28em] text-sidebar"
        >
            FX
        </div>
        <div class="min-w-0 group-data-[collapsible=icon]:hidden">
            <p class="truncate font-serif-display text-xl text-sidebar-primary-foreground">
                {{ companyName }}
            </p>
            <p class="text-[11px] tracking-[0.24em] text-sidebar-primary-foreground/65 uppercase">
                Operations Hub
            </p>
        </div>
    </div>
</template>
