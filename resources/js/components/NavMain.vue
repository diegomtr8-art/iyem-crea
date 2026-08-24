<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import type { Component } from 'vue';
import { computed } from 'vue';

interface NavItem {
    title: string;
    url: string;
    icon: Component;
    badge?: number;
    group?: string;
}

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage<SharedData>();

const groups = computed(() => {
    const map = new Map<string, NavItem[]>();
    for (const item of props.items) {
        const key = item.group ?? 'Platform';
        if (!map.has(key)) map.set(key, []);
        map.get(key)!.push(item);
    }
    return Array.from(map.entries()).map(([label, items]) => ({ label, items }));
});
</script>

<template>
    <SidebarGroup v-for="grp in groups" :key="grp.label" class="px-2 py-0">
        <SidebarGroupLabel>{{ grp.label }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in grp.items" :key="item.title">
                <SidebarMenuButton as-child :is-active="item.url === page.url">
                    <Link :href="item.url">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                        <span
                            v-if="item.badge"
                            class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-xs font-semibold text-white"
                        >{{ item.badge }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
