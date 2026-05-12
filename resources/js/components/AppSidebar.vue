<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    CalendarDays,
    ChartLine,
    ClipboardList,
    FileStack,
    LayoutGrid,
    LockKeyhole,
    Logs,
    Package,
    ReceiptText,
    Archive,
    Landmark,
    Users,
    Wrench,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';

const { isCurrentOrParentUrl } = useCurrentUrl();
const page = usePage();

function can(permission?: string) {
    if (!permission) {
        return true;
    }

    const user = (page.props.auth as any)?.user;
    const roles = user?.roles ?? [];
    const permissions = user?.permissions ?? [];

    if (roles.includes('Administrador')) {
        return true;
    }

    return permissions.includes(permission);
}

const sections = [
    {
        label: 'Vis\u00e3o geral',
        items: [
            {
                title: 'Dashboard',
                href: '/dashboard',
                icon: LayoutGrid,
                permission: 'dashboard.read',
            },
            {
                title: 'Calend\u00e1rio',
                href: '/calendario',
                icon: CalendarDays,
                permission: 'calendario.read',
            },
            {
                title: 'Logs',
                href: '/logs',
                icon: Logs,
                permission: 'logs.read',
            },
        ],
    },
    {
        label: 'Opera\u00e7\u00f5es',
        items: [
            {
                title: 'Clientes',
                href: '/clientes',
                icon: Users,
                permission: 'clientes.read',
            },
            {
                title: 'Fornecedores',
                href: '/fornecedores',
                icon: Building2,
                permission: 'fornecedores.read',
            },
            {
                title: 'Contactos',
                href: '/contactos',
                icon: ClipboardList,
                permission: 'contactos.read',
            },
            {
                title: 'Artigos',
                href: '/configuracoes/artigos',
                icon: Package,
                permission: 'artigos.read',
            },
            {
                title: 'Propostas',
                href: '/propostas',
                icon: FileStack,
                permission: 'propostas.read',
            },
            {
                title: 'Encomendas Clientes',
                href: '/encomendas-clientes',
                icon: ChartLine,
                permission: 'encomendas-clientes.read',
            },
            {
                title: 'Encomendas Fornecedores',
                href: '/encomendas-fornecedores',
                icon: ChartLine,
                permission: 'encomendas-fornecedores.read',
            },
            {
                title: 'Ordens de Trabalho',
                href: '/ordens-trabalho',
                icon: Wrench,
                permission: 'ordens-trabalho.read',
            },
            {
                title: 'Arquivo Digital',
                href: '/arquivo-digital',
                icon: Archive,
                permission: 'arquivo-digital.read',
            },
        ],
    },
    {
        label: 'Financeiro',
        items: [
            {
                title: 'Financeiro',
                href: '/financeiro',
                icon: ChartLine,
                permission: 'financeiro.read',
            },
            {
                title: 'Contas Banc\u00e1rias',
                href: '/financeiro/contas-bancarias',
                icon: Landmark,
                permission: 'contas-bancarias.read',
            },
            {
                title: 'Conta Corrente Clientes',
                href: '/financeiro/conta-corrente-clientes',
                icon: ReceiptText,
                permission: 'conta-corrente-clientes.read',
            },
            {
                title: 'Faturas Fornecedor',
                href: '/financeiro/faturas-fornecedor',
                icon: ReceiptText,
                permission: 'faturas-fornecedores.read',
            },
        ],
    },
    {
        label: 'Administra\u00e7\u00e3o',
        items: [
            {
                title: 'Utilizadores',
                href: '/gestao-de-acessos/utilizadores',
                icon: Users,
                permission: 'utilizadores.read',
            },
            {
                title: 'Permiss\u00f5es',
                href: '/gestao-de-acessos/permissoes',
                icon: LockKeyhole,
                permission: 'permissoes.read',
            },
            {
                title: 'Empresa',
                href: '/configuracoes/empresa',
                icon: Building2,
                permission: 'empresa.read',
            },
        ],
    },
    {
        label: 'Configura\u00e7\u00f5es',
        items: [
            {
                title: 'Pa\u00edses',
                href: '/configuracoes/listas/countries',
                icon: Building2,
                permission: 'configuracoes.read',
            },
            {
                title: 'Fun\u00e7\u00f5es',
                href: '/configuracoes/listas/contact-roles',
                icon: ClipboardList,
                permission: 'configuracoes.read',
            },
            {
                title: 'IVA',
                href: '/configuracoes/listas/vat-rates',
                icon: ReceiptText,
                permission: 'configuracoes.read',
            },
            {
                title: 'Tipos Calend\u00e1rio',
                href: '/configuracoes/listas/calendar-types',
                icon: CalendarDays,
                permission: 'configuracoes.read',
            },
            {
                title: 'Ac\u00e7\u00f5es Calend\u00e1rio',
                href: '/configuracoes/listas/calendar-actions',
                icon: CalendarDays,
                permission: 'configuracoes.read',
            },
        ],
    },
];

const visibleSections = computed(() =>
    sections
        .map((section) => ({
            ...section,
            items: section.items.filter((entry) => can(entry.permission)),
        }))
        .filter((section) => section.items.length > 0),
);
</script>

<template>
    <Sidebar
        collapsible="icon"
        variant="floating"
        class="border-none bg-transparent"
    >
        <SidebarHeader
            class="shrink-0 overflow-hidden rounded-[2rem] bg-sidebar p-4 shadow-[0_24px_45px_rgba(40,38,48,0.28)] group-data-[collapsible=icon]:px-2"
        >
            <Link
                href="/dashboard"
                class="flex w-full items-center overflow-hidden rounded-[1.6rem] bg-[rgba(245,235,219,0.98)] px-4 py-4 transition-all group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0"
            >
                <AppLogo />
            </Link>
        </SidebarHeader>

        <SidebarContent
            class="mt-4 min-h-0 gap-4 overflow-x-hidden overflow-y-auto rounded-[2rem] bg-sidebar p-3 text-sidebar-foreground shadow-[0_20px_40px_rgba(40,38,48,0.22)]"
        >
            <SidebarGroup
                v-for="section in visibleSections"
                :key="section.label"
                class="rounded-[1.5rem] border border-sidebar-border/50 bg-sidebar/70 px-1 py-2 group-data-[collapsible=icon]:overflow-hidden group-data-[collapsible=icon]:px-0"
            >
                <SidebarGroupLabel
                    class="px-3 text-[10px] tracking-[0.26em] text-sidebar-foreground/55 uppercase"
                >
                    {{ section.label }}
                </SidebarGroupLabel>
                <SidebarGroupContent class="pt-1">
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in section.items"
                            :key="item.title"
                        >
                            <SidebarMenuButton
                                as-child
                                size="lg"
                                :tooltip="item.title"
                                :is-active="isCurrentOrParentUrl(item.href)"
                                class="rounded-2xl text-sidebar-foreground hover:bg-[rgba(245,235,219,0.14)] data-[active=true]:bg-[rgba(245,235,219,0.96)] data-[active=true]:text-sidebar"
                            >
                                <Link
                                    :href="item.href"
                                    class="flex h-full w-full min-w-0 items-center gap-2"
                                >
                                    <component :is="item.icon" class="size-4" />
                                    <span
                                        class="truncate group-data-[collapsible=icon]:hidden"
                                        >{{ item.title }}</span
                                    >
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter
            class="mt-4 shrink-0 overflow-hidden rounded-[2rem] bg-sidebar p-3 shadow-[0_16px_35px_rgba(40,38,48,0.22)] group-data-[collapsible=icon]:px-2"
        >
            <div
                class="overflow-hidden rounded-[1.6rem] bg-[linear-gradient(180deg,rgba(245,235,219,0.14),rgba(245,235,219,0.04))] p-3 group-data-[collapsible=icon]:hidden"
            >
                <p class="font-serif-display text-lg text-sidebar-foreground">
                    Fluxio
                </p>
                <p class="mt-1 text-xs leading-5 text-sidebar-foreground/65">
                    Gest&atilde;o comercial, operacional e financeira com foco
                    em clareza e controlo.
                </p>
            </div>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
