<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

type ArticleOption = {
    id: number;
    reference: string;
    name: string;
    description?: string | null;
    price: number;
    vat_rate?: number | null;
};

type SupplierOption = {
    id: number;
    label: string;
};

type LineItem = {
    article_id?: number | null;
    supplier_entity_id?: number | null;
    reference?: string;
    name?: string;
    description?: string | null;
    quantity?: number;
    unit_price?: number;
    cost_price?: number;
    vat_rate?: number;
};

const props = defineProps<{
    modelValue: LineItem[];
    articles: ArticleOption[];
    suppliers: SupplierOption[];
    allowSupplierSelection?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: LineItem[]];
}>();

const rows = computed(() => props.modelValue ?? []);

function sync(next: LineItem[]) {
    emit('update:modelValue', next);
}

function addRow() {
    sync([
        ...rows.value,
        {
            article_id: null,
            supplier_entity_id: null,
            reference: '',
            name: '',
            description: '',
            quantity: 1,
            unit_price: 0,
            cost_price: 0,
            vat_rate: 23,
        },
    ]);
}

function removeRow(index: number) {
    sync(rows.value.filter((_, rowIndex) => rowIndex !== index));
}

function updateRow(index: number, patch: Partial<LineItem>) {
    sync(
        rows.value.map((row, rowIndex) =>
            rowIndex === index ? { ...row, ...patch } : row,
        ),
    );
}

function selectArticle(index: number, articleId: string) {
    const selected = props.articles.find(
        (article) => article.id === Number(articleId),
    );

    if (!selected) {
        updateRow(index, { article_id: null });

        return;
    }

    updateRow(index, {
        article_id: selected.id,
        reference: selected.reference,
        name: selected.name,
        description: selected.description ?? '',
        unit_price: selected.price,
        vat_rate: selected.vat_rate ?? 23,
    });
}

function lineTotal(row: LineItem) {
    const quantity = Number(row.quantity ?? 0);
    const unitPrice = Number(row.unit_price ?? 0);
    const vatRate = Number(row.vat_rate ?? 0);
    const subtotal = quantity * unitPrice;

    return subtotal + subtotal * (vatRate / 100);
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-serif-display text-2xl text-foreground">
                    Linhas de artigos
                </h3>
                <p class="text-sm text-muted-foreground">
                    Pesquise por referência ou nome, ajuste preços e associe
                    logo o fornecedor.
                </p>
            </div>
            <Button type="button" variant="secondary" @click="addRow">
                Adicionar linha
            </Button>
        </div>

        <div
            v-if="rows.length === 0"
            class="rounded-[1.5rem] border border-dashed border-border bg-secondary/45 p-6 text-sm text-muted-foreground"
        >
            Ainda não existem linhas de artigo nesta proposta/encomenda.
        </div>

        <div
            v-for="(row, index) in rows"
            :key="index"
            class="rounded-[1.5rem] border border-border/80 bg-card/90 p-4 shadow-sm"
        >
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="space-y-2 text-sm">
                    <span class="font-medium text-foreground">Artigo</span>
                    <select
                        class="flex h-11 w-full rounded-2xl border border-input bg-transparent px-4 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring/35"
                        :value="row.article_id ?? ''"
                        @change="
                            selectArticle(
                                index,
                                ($event.target as HTMLSelectElement).value,
                            )
                        "
                    >
                        <option value="">Selecionar artigo</option>
                        <option
                            v-for="article in articles"
                            :key="article.id"
                            :value="article.id"
                        >
                            {{ article.reference }} - {{ article.name }}
                        </option>
                    </select>
                </label>

                <label class="space-y-2 text-sm">
                    <span class="font-medium text-foreground">Referência</span>
                    <Input
                        :model-value="row.reference ?? ''"
                        @update:model-value="
                            updateRow(index, { reference: String($event) })
                        "
                    />
                </label>

                <label class="space-y-2 text-sm">
                    <span class="font-medium text-foreground">Nome</span>
                    <Input
                        :model-value="row.name ?? ''"
                        @update:model-value="
                            updateRow(index, { name: String($event) })
                        "
                    />
                </label>

                <label v-if="allowSupplierSelection" class="space-y-2 text-sm">
                    <span class="font-medium text-foreground">Fornecedor</span>
                    <select
                        class="flex h-11 w-full rounded-2xl border border-input bg-transparent px-4 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring/35"
                        :value="row.supplier_entity_id ?? ''"
                        @change="
                            updateRow(index, {
                                supplier_entity_id:
                                    Number(
                                        ($event.target as HTMLSelectElement)
                                            .value,
                                    ) || null,
                            })
                        "
                    >
                        <option value="">Sem fornecedor</option>
                        <option
                            v-for="supplier in suppliers"
                            :key="supplier.id"
                            :value="supplier.id"
                        >
                            {{ supplier.label }}
                        </option>
                    </select>
                </label>

                <label class="space-y-2 text-sm">
                    <span class="font-medium text-foreground">Quantidade</span>
                    <Input
                        type="number"
                        min="0"
                        step="0.01"
                        :model-value="row.quantity ?? 1"
                        @update:model-value="
                            updateRow(index, { quantity: Number($event) })
                        "
                    />
                </label>

                <label class="space-y-2 text-sm">
                    <span class="font-medium text-foreground">Preço venda</span>
                    <Input
                        type="number"
                        min="0"
                        step="0.01"
                        :model-value="row.unit_price ?? 0"
                        @update:model-value="
                            updateRow(index, { unit_price: Number($event) })
                        "
                    />
                </label>

                <label class="space-y-2 text-sm">
                    <span class="font-medium text-foreground">Preço custo</span>
                    <Input
                        type="number"
                        min="0"
                        step="0.01"
                        :model-value="row.cost_price ?? 0"
                        @update:model-value="
                            updateRow(index, { cost_price: Number($event) })
                        "
                    />
                </label>

                <label class="space-y-2 text-sm">
                    <span class="font-medium text-foreground">IVA (%)</span>
                    <Input
                        type="number"
                        min="0"
                        step="0.01"
                        :model-value="row.vat_rate ?? 23"
                        @update:model-value="
                            updateRow(index, { vat_rate: Number($event) })
                        "
                    />
                </label>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-[1fr_auto]">
                <label class="space-y-2 text-sm">
                    <span class="font-medium text-foreground">Descrição</span>
                    <Textarea
                        :model-value="row.description ?? ''"
                        @update:model-value="
                            updateRow(index, { description: String($event) })
                        "
                    />
                </label>

                <div
                    class="flex flex-col justify-between gap-4 rounded-[1.25rem] bg-secondary/45 p-4"
                >
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase"
                        >
                            Total linha
                        </p>
                        <p
                            class="mt-2 font-serif-display text-3xl text-foreground"
                        >
                            € {{ lineTotal(row).toFixed(2) }}
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="destructive"
                        @click="removeRow(index)"
                    >
                        Remover
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
