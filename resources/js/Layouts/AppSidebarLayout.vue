<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const COLLAPSE_STORAGE = 'cosmetic_sidebar_collapsed';
const GROUPS_STORAGE = 'cosmetic_sidebar_groups';

const page = usePage();
const collapsed = ref(true);
const mobileOpen = ref(false);
const expandedGroups = ref({});

const roles = computed(() => page.props.auth?.user?.roles ?? []);
const userName = computed(() => page.props.auth?.user?.name ?? '');
const userEmail = computed(() => page.props.auth?.user?.email ?? '');
const currentUrl = computed(() => page.url.split('?')[0]);

/** المدير الأعلى يرى كل الصفحات. */
function hasAccess(allowedRoles) {
    if (!allowedRoles?.length || roles.value.includes('SUPER ADMIN')) {
        return true;
    }
    return allowedRoles.some((r) => roles.value.includes(r));
}

function isCurrent(href) {
    return currentUrl.value === href || currentUrl.value.startsWith(`${href}/`);
}

const navGroups = computed(() => [
    {
        id: 'main',
        heading: 'الرئيسية',
        icon: '⌂',
        items: [
            { label: 'لوحة التحكم', href: '/dashboard', roles: ['SUPER ADMIN', 'INVENTORY MANAGER', 'CASHIER', 'ACCOUNTS'], icon: '⌂' },
        ],
    },
    {
        id: 'sales',
        heading: 'المبيعات',
        icon: '¤',
        items: [
            { label: 'نقطة البيع', href: '/pos', roles: ['SUPER ADMIN', 'INVENTORY MANAGER', 'CASHIER', 'ACCOUNTS'], icon: '¤' },
            { label: 'المرتجعات', href: '/refunds', roles: ['SUPER ADMIN', 'CASHIER'], icon: '↩' },
        ],
    },
    {
        id: 'inventory',
        heading: 'المخزون والمنتجات',
        icon: '▦',
        items: [
            { label: 'المنتجات', href: '/products', roles: ['SUPER ADMIN', 'INVENTORY MANAGER'], icon: '▦' },
            { label: 'الموردون', href: '/suppliers', roles: ['SUPER ADMIN', 'INVENTORY MANAGER'], icon: '⛟' },
            { label: 'المخزون والصلاحية', href: '/inventory', roles: ['SUPER ADMIN', 'INVENTORY MANAGER'], icon: '▣' },
        ],
    },
    {
        id: 'finance',
        heading: 'المالية والتقارير',
        icon: '▤',
        items: [
            { label: 'التقارير', href: '/reports', roles: ['SUPER ADMIN', 'INVENTORY MANAGER', 'CASHIER', 'ACCOUNTS'], icon: '▤' },
            { label: 'المصروفات', href: '/expenses', roles: ['SUPER ADMIN', 'ACCOUNTS'], icon: '◎' },
        ],
    },
    {
        id: 'system',
        heading: 'النظام',
        icon: '⚙',
        items: [
            { label: 'المستخدمون والأدوار', href: '/users', roles: ['SUPER ADMIN'], icon: '👤' },
        ],
    },
]);

const visibleGroups = computed(() =>
    navGroups.value
        .map((g) => ({ ...g, items: g.items.filter((item) => hasAccess(item.roles)) }))
        .filter((g) => g.items.length > 0)
);

function toggleCollapse() {
    collapsed.value = !collapsed.value;
}

function toggleMobile() {
    mobileOpen.value = !mobileOpen.value;
}

function closeMobile() {
    mobileOpen.value = false;
}

function toggleGroup(groupId) {
    expandedGroups.value[groupId] = !expandedGroups.value[groupId];
}

function isGroupExpanded(group) {
    if (mobileOpen.value || !collapsed.value) {
        return expandedGroups.value[group.id] ?? true;
    }
    return false;
}

function ensureCurrentRouteGroupExpanded() {
    for (const group of visibleGroups.value) {
        const hasCurrent = group.items.some((item) => isCurrent(item.href));
        if (hasCurrent) {
            expandedGroups.value[group.id] = true;
        }
    }
}

function logout() {
    router.post('/logout');
}

onMounted(() => {
    const savedCollapse = localStorage.getItem(COLLAPSE_STORAGE);
    if (savedCollapse !== null) {
        collapsed.value = savedCollapse === '1';
    }

    const savedGroups = localStorage.getItem(GROUPS_STORAGE);
    if (savedGroups) {
        try {
            expandedGroups.value = JSON.parse(savedGroups);
        } catch {
            expandedGroups.value = {};
        }
    }

    // فتح مجموعة المسار الحالي لتوجيه المستخدم بصريًا.
    ensureCurrentRouteGroupExpanded();
});

watch(collapsed, (v) => {
    localStorage.setItem(COLLAPSE_STORAGE, v ? '1' : '0');
});

watch(
    expandedGroups,
    (v) => {
        localStorage.setItem(GROUPS_STORAGE, JSON.stringify(v));
    },
    { deep: true }
);
</script>

<template>
    <div class="flex min-h-screen bg-white text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100" dir="rtl">
        <div
            v-if="mobileOpen"
            class="fixed inset-0 z-40 bg-black/40 lg:hidden"
            aria-hidden="true"
            @click="closeMobile"
        />

        <!--
          سطح المكتب: السايدبار ضمن الصف (لا يغطي المحتوى).
          الموبايل: نفس العنصر fixed + إظهار/إخفاء بالتحريك؛ العرض الكامل 16 على lg عند الطي.
        -->
        <aside
            :class="[
                'flex shrink-0 flex-col border-l border-zinc-200 bg-zinc-50 transition-[width,transform] duration-200 ease-out dark:border-zinc-700 dark:bg-zinc-900',
                'fixed inset-y-0 right-0 z-50 w-72 lg:relative lg:z-auto lg:h-screen',
                mobileOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0',
                collapsed ? 'lg:w-16' : 'lg:w-72',
            ]"
        >
            <div class="flex h-14 shrink-0 items-center justify-between gap-2 border-b border-zinc-200 px-2 dark:border-zinc-700">
                <Link
                    href="/dashboard"
                    class="flex min-w-0 flex-1 items-center gap-2 truncate px-2 font-bold text-zinc-800 dark:text-zinc-100"
                    @click="closeMobile"
                >
                    <!-- عند الطي نظهر شعار مختصر مثل سلوك Flux -->
                    <span
                        v-if="collapsed && !mobileOpen"
                        class="hidden h-9 w-9 items-center justify-center rounded-lg bg-zinc-800 text-xs font-bold text-white lg:inline-flex dark:bg-zinc-100 dark:text-zinc-900"
                    >
                        م
                    </span>
                    <span v-else class="truncate">Cosmetic Store</span>
                </Link>
                <button
                    type="button"
                    class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 lg:flex"
                    :title="collapsed ? 'توسيع القائمة' : 'طي القائمة'"
                    @click="toggleCollapse"
                >
                    <span class="text-lg leading-none">{{ collapsed ? '«' : '»' }}</span>
                </button>
                <button
                    type="button"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 lg:hidden"
                    title="إغلاق"
                    @click="closeMobile"
                >
                    ✕
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto py-3">
                <template v-for="group in visibleGroups" :key="group.id">
                    <div class="mb-3 px-2">
                        <button
                            type="button"
                            class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
                            :title="collapsed && !mobileOpen ? group.heading : undefined"
                            @click="toggleGroup(group.id)"
                        >
                            <span
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"
                            >
                                {{ group.icon }}
                            </span>
                            <span v-if="!collapsed || mobileOpen" class="flex-1 truncate">
                                {{ group.heading }}
                            </span>
                            <span v-if="!collapsed || mobileOpen" class="text-xs">{{ isGroupExpanded(group) ? '▾' : '▸' }}</span>
                        </button>

                        <div v-show="isGroupExpanded(group)" class="mt-1 space-y-0.5">
                            <Link
                                v-for="item in group.items"
                                :key="item.href"
                                :href="item.href"
                                :class="[
                                    'flex items-center gap-3 rounded-lg py-2.5 text-sm font-medium transition',
                                    collapsed && !mobileOpen ? 'justify-center px-0' : 'px-3',
                                    isCurrent(item.href)
                                        ? 'bg-zinc-200 text-zinc-900 dark:bg-zinc-700 dark:text-zinc-100'
                                        : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800',
                                ]"
                                :title="collapsed && !mobileOpen ? item.label : undefined"
                                @click="closeMobile"
                            >
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"
                                >
                                    {{ item.icon }}
                                </span>
                                <span v-if="!collapsed || mobileOpen" class="truncate">{{ item.label }}</span>
                            </Link>
                        </div>
                    </div>
                </template>
            </nav>

            <div class="border-t border-zinc-200 p-2 dark:border-zinc-700">
                <div
                    v-if="!collapsed || mobileOpen"
                    class="mb-2 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                >
                    <div class="truncate font-semibold">{{ userName }}</div>
                    <div class="truncate text-[11px] opacity-80">{{ userEmail }}</div>
                </div>
                <button
                    type="button"
                    :class="[
                        'flex w-full items-center gap-2 rounded-lg border border-zinc-200 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-zinc-700 dark:text-red-400 dark:hover:bg-red-950/30',
                        collapsed && !mobileOpen ? 'justify-center px-0' : 'px-3',
                    ]"
                    :title="collapsed && !mobileOpen ? 'تسجيل الخروج' : undefined"
                    @click="logout"
                >
                    <span class="text-base">⎋</span>
                    <span v-if="!collapsed || mobileOpen">تسجيل الخروج</span>
                </button>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-14 shrink-0 items-center gap-2 border-b border-zinc-200 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900 lg:hidden">
                <button
                    type="button"
                    class="flex h-10 w-10 items-center justify-center rounded-lg border border-zinc-200 dark:border-zinc-700"
                    title="القائمة"
                    @click="toggleMobile"
                >
                    ☰
                </button>
                <span class="font-semibold text-zinc-800 dark:text-zinc-100">التنقل</span>
            </header>

            <div class="min-h-0 min-w-0 flex-1 overflow-auto">
                <slot />
            </div>
        </div>
    </div>
</template>
