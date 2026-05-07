<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="mx-4 mt-4 flex min-h-18 shrink-0 items-center gap-2 rounded-[2rem] border border-border/70 bg-sidebar px-6 text-sidebar-foreground shadow-[0_18px_40px_rgba(45,43,56,0.16)] transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-14 md:mx-6"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1 rounded-xl bg-[rgba(245,235,219,0.96)] text-sidebar hover:bg-[rgba(245,235,219,1)]" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
    </header>
</template>
