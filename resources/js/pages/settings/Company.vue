<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PageIntro from '@/components/fluxio/PageIntro.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    record: Record<string, any>;
    endpoints: Record<string, string>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Empresa', href: '/configuracoes/empresa' }],
    },
});

const form = useForm({
    name: props.record.name ?? '',
    address: props.record.address ?? '',
    postal_code: props.record.postal_code ?? '',
    city: props.record.city ?? '',
    tax_number: props.record.tax_number ?? '',
    logo: null as File | null,
});

function submit() {
    form.post(props.endpoints.update, {
        preserveScroll: true,
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Empresa" />

    <div class="space-y-6 px-4 py-6 md:px-6">
        <PageIntro
            eyebrow="Configurações"
            title="Empresa"
            description="Dados institucionais utilizados no login, na intro e nos PDFs emitidos pela aplicação."
        />

        <section class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
            <article class="rounded-[2rem] border border-border/80 bg-card/95 p-6 shadow-[0_16px_40px_rgba(60,43,30,0.08)]">
                <p class="font-serif-display text-2xl text-foreground">Pré-visualização</p>
                <div class="mt-4 rounded-[1.8rem] bg-sidebar p-6 text-sidebar-foreground">
                    <img v-if="record.logo_url" :src="record.logo_url" alt="" class="h-14 rounded-2xl object-contain" />
                    <div v-else class="flex size-14 items-center justify-center rounded-2xl bg-[rgba(245,235,219,0.96)] text-sm font-bold tracking-[0.3em] text-sidebar">
                        FX
                    </div>
                    <p class="mt-5 font-serif-display text-3xl">{{ form.name || 'Fluxio' }}</p>
                    <p class="mt-2 text-sm leading-6 text-sidebar-foreground/70">{{ form.address }}</p>
                    <p class="text-sm leading-6 text-sidebar-foreground/70">{{ form.postal_code }} {{ form.city }}</p>
                    <p class="mt-2 text-sm leading-6 text-sidebar-foreground/70">NIF {{ form.tax_number }}</p>
                </div>
            </article>

            <article class="rounded-[2rem] border border-border/80 bg-card/95 p-6 shadow-[0_16px_40px_rgba(60,43,30,0.08)]">
                <form class="space-y-4" @submit.prevent="submit">
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Logotipo empresa</span>
                        <input type="file" accept="image/*" class="block w-full text-sm" @change="form.logo = (($event.target as HTMLInputElement).files?.[0] ?? null)" />
                    </label>
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Nome</span>
                        <Input v-model="form.name" />
                    </label>
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Morada</span>
                        <Input v-model="form.address" />
                    </label>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Código postal</span>
                            <Input v-model="form.postal_code" />
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="font-medium">Localidade</span>
                            <Input v-model="form.city" />
                        </label>
                    </div>
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Número contribuinte</span>
                        <Input v-model="form.tax_number" />
                    </label>
                    <div class="pt-2">
                        <Button type="submit">Guardar dados da empresa</Button>
                    </div>
                </form>
            </article>
        </section>
    </div>
</template>
