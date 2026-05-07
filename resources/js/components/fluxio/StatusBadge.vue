<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    value: string | boolean | null | undefined;
}>();

const theme = computed(() => {
    const value = String(props.value ?? '').toLowerCase();

    if (['active', 'ativo', 'paid', 'paga', 'closed', 'fechado', 'completed'].includes(value)) {
        return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    }

    if (['draft', 'rascunho', 'pending', 'pendente', 'scheduled'].includes(value)) {
        return 'bg-amber-100 text-amber-900 border-amber-200';
    }

    if (['inactive', 'inativo', 'cancelled', 'cancelado'].includes(value)) {
        return 'bg-slate-200 text-slate-700 border-slate-300';
    }

    if (props.value === true) {
        return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    }

    if (props.value === false) {
        return 'bg-rose-100 text-rose-700 border-rose-200';
    }

    return 'bg-secondary text-secondary-foreground border-border';
});
</script>

<template>
    <Badge class="rounded-full border px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em]" :class="theme">
        {{ typeof value === 'boolean' ? (value ? 'Sim' : 'Não') : value }}
    </Badge>
</template>
