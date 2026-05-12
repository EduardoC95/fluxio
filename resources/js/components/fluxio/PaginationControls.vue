<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

defineProps<{
    pagination?: Record<string, any> | null;
}>();

function visit(url: string | null) {
    if (!url) {
        return;
    }

    router.get(url, {}, {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <nav v-if="pagination && pagination.total > 0" class="flex flex-wrap items-center justify-between gap-3 px-6 pb-6 text-sm text-muted-foreground">
        <span>{{ pagination.from }}-{{ pagination.to }} de {{ pagination.total }}</span>
        <div class="flex flex-wrap gap-2">
            <Button
                v-for="(link, index) in pagination.links"
                :key="`${link.label}-${index}`"
                type="button"
                size="sm"
                :variant="link.active ? 'default' : 'secondary'"
                :disabled="!link.url"
                @click="visit(link.url)"
            >
                {{ link.label }}
            </Button>
        </div>
    </nav>
</template>
