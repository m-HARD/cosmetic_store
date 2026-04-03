<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import FluxIcon from '@/Components/Nav/FluxIcon.vue';

const COLLAPSE_STORAGE = 'cosmetic_sidebar_collapsed';
const GROUPS_STORAGE = 'cosmetic_sidebar_groups';
const THEME_STORAGE = 'cosmetic_theme';

const page = usePage();
const collapsed = ref(true);
const mobileOpen = ref(false);
const expandedGroups = ref({});
const profileMenuOpen = ref(false);
const isDark = ref(false);
const profileMenuRef = ref(null);

const roles = computed(() => page.props.auth?.user?.roles ?? []);
const userName = computed(() => page.props.auth?.user?.name ?? '');
const userEmail = computed(() => page.props.auth?.user?.email ?? '');
const currentUrl = computed(() => page.url.split('?')[0]);

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
        icon: 'home',
        items: [{ label: 'لوحة التحكم', href: '/dashboard', roles: ['SUPER ADMIN', 'INVENTORY MANAGER', 'CASHIER', 'ACCOUNTS'], icon: 'home' }],
    },
    {
        id: 'sales',
        heading: 'المبيعات',
        icon: 'sales',
        items: [
            { label: 'نقطة البيع', href: '/pos', roles: ['SUPER ADMIN', 'INVENTORY MANAGER', 'CASHIER', 'ACCOUNTS'], icon: 'sales' },
            { label: 'المرتجعات', href: '/refunds', roles: ['SUPER ADMIN', 'CASHIER'], icon: 'sales' },
        ],
    },
    {
        id: 'inventory',
        heading: 'المخزون',
        icon: 'products',
        items: [
            { label: 'المنتجات', href: '/products', roles: ['SUPER ADMIN', 'INVENTORY MANAGER'], icon: 'products' },
            { label: 'الموردون', href: '/suppliers', roles: ['SUPER ADMIN', 'INVENTORY MANAGER'], icon: 'suppliers' },
            { label: 'المخزون والصلاحية', href: '/inventory', roles: ['SUPER ADMIN', 'INVENTORY MANAGER'], icon: 'products' },
        ],
    },
    {
        id: 'finance',
        heading: 'المالية',
        icon: 'reports',
        items: [
            { label: 'التقارير', href: '/reports', roles: ['SUPER ADMIN', 'INVENTORY MANAGER', 'CASHIER', 'ACCOUNTS'], icon: 'reports' },
            { label: 'المصروفات', href: '/expenses', roles: ['SUPER ADMIN', 'ACCOUNTS'], icon: 'expenses' },
        ],
    },
    {
        id: 'system',
        heading: 'النظام',
        icon: 'users',
        items: [{ label: 'المستخدمون والأدوار', href: '/users', roles: ['SUPER ADMIN'], icon: 'users' }],
    },
]);

const bottomNavItems = computed(() =>
    [
        { label: 'إعدادات النظام', href: '#', roles: ['SUPER ADMIN'], icon: 'settings' },
        { label: isDark.value ? 'الوضع الفاتح' : 'الوضع الداكن', href: '#', roles: ['SUPER ADMIN', 'INVENTORY MANAGER', 'CASHIER', 'ACCOUNTS'], icon: 'settings', action: 'theme' },
    ].filter((item) => hasAccess(item.roles))
);

const visibleGroups = computed(() =>
    navGroups.value
        .map((group) => ({ ...group, items: group.items.filter((item) => hasAccess(item.roles)) }))
        .filter((group) => group.items.length > 0)
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
    expandedGroups.value[groupId] = !(expandedGroups.value[groupId] ?? true);
}

function isGroupExpanded(groupId) {
    if (collapsed.value && !mobileOpen.value) {
        return false;
    }
    return expandedGroups.value[groupId] ?? true;
}

function ensureCurrentRouteGroupExpanded() {
    for (const group of visibleGroups.value) {
        if (group.items.some((item) => isCurrent(item.href))) {
            expandedGroups.value[group.id] = true;
        }
    }
}

function applyTheme() {
    document.documentElement.classList.toggle('dark', isDark.value);
}

function toggleTheme() {
    isDark.value = !isDark.value;
    localStorage.setItem(THEME_STORAGE, isDark.value ? 'dark' : 'light');
    applyTheme();
}

function handleBottomItem(item) {
    if (item.action === 'theme') {
        toggleTheme();
    }
}

function toggleProfileMenu() {
    profileMenuOpen.value = !profileMenuOpen.value;
}

function closeProfileMenu() {
    profileMenuOpen.value = false;
}

function onClickOutside(event) {
    if (!profileMenuRef.value) {
        return;
    }
    if (!profileMenuRef.value.contains(event.target)) {
        closeProfileMenu();
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

    const savedTheme = localStorage.getItem(THEME_STORAGE);
    if (savedTheme) {
        isDark.value = savedTheme === 'dark';
    } else {
        isDark.value = document.documentElement.classList.contains('dark');
    }
    applyTheme();

    ensureCurrentRouteGroupExpanded();
    document.addEventListener('click', onClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside);
});

watch(collapsed, (value) => {
    localStorage.setItem(COLLAPSE_STORAGE, value ? '1' : '0');
});

watch(
    expandedGroups,
    (value) => {
        localStorage.setItem(GROUPS_STORAGE, JSON.stringify(value));
    },
    { deep: true }
);
</script>

<template>
    <div class="flex min-h-screen bg-white text-zinc-800 antialiased dark:bg-zinc-800 dark:text-zinc-100" dir="rtl">
        <div
            v-if="mobileOpen"
            class="fixed inset-0 z-40 bg-black/40 lg:hidden"
            aria-hidden="true"
            @click="closeMobile"
        />

        <aside
            :class="[
                'fixed inset-y-0 right-0 z-50 flex shrink-0 flex-col border-l border-zinc-200 bg-zinc-50 transition-[width,transform] duration-200 ease-out dark:border-zinc-700 dark:bg-zinc-900 lg:sticky lg:top-0 lg:z-30 lg:h-screen',
                mobileOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0',
                collapsed ? 'lg:w-16' : 'lg:w-72',
                'w-72',
            ]"
        >
            <div class="flex h-14 shrink-0 items-center gap-2 border-b border-zinc-200 px-3 dark:border-zinc-700">
                <Link
                    href="/dashboard"
                    class="flex min-w-0 flex-1 items-center gap-2 truncate rounded-lg px-2 py-1.5 text-sm font-semibold text-zinc-800 hover:bg-zinc-100 dark:text-zinc-100 dark:hover:bg-zinc-800"
                    @click="closeMobile"
                >
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-zinc-900 text-xs font-bold text-white dark:bg-zinc-100 dark:text-zinc-900">
                        CS
                    </span>
                    <span v-if="!collapsed || mobileOpen" class="truncate">Cosmetic Store</span>
                </Link>
                <button
                    type="button"
                    class="hidden h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 lg:flex"
                    :title="collapsed ? 'توسيع القائمة' : 'طي القائمة'"
                    @click="toggleCollapse"
                >
                    <FluxIcon :name="collapsed ? 'chevronLeft' : 'chevronRight'" />
                </button>
                <button
                    type="button"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 lg:hidden"
                    title="إغلاق"
                    @click="closeMobile"
                >
                    <FluxIcon name="chevronRight" />
                </button>
            </div>

            <nav class="overflow-y-auto p-2">
                <template v-for="group in visibleGroups" :key="group.id">
                    <div class="mb-2">
                        <button
                            type="button"
                            class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-right text-xs font-medium text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
                            :title="collapsed && !mobileOpen ? group.heading : undefined"
                            @click="toggleGroup(group.id)"
                        >
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                <FluxIcon :name="group.icon" />
                            </span>
                            <span v-if="!collapsed || mobileOpen" class="flex-1 truncate">
                                {{ group.heading }}
                            </span>
                            <span v-if="!collapsed || mobileOpen" class="text-zinc-400">
                                <FluxIcon :name="isGroupExpanded(group.id) ? 'chevronDown' : 'chevronLeft'" />
                            </span>
                        </button>

                        <div v-show="isGroupExpanded(group.id)" class="mt-1 space-y-0.5">
                            <Link
                                v-for="item in group.items"
                                :key="item.href"
                                :href="item.href"
                                :class="[
                                    'flex items-center gap-3 rounded-lg py-2 text-sm font-medium transition',
                                    collapsed && !mobileOpen ? 'justify-center px-0' : 'px-3',
                                    isCurrent(item.href)
                                        ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900'
                                        : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800',
                                ]"
                                :title="collapsed && !mobileOpen ? item.label : undefined"
                                @click="closeMobile"
                            >
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                    <FluxIcon :name="item.icon" />
                                </span>
                                <span v-if="!collapsed || mobileOpen" class="truncate">{{ item.label }}</span>
                            </Link>
                        </div>
                    </div>
                </template>
            </nav>

            <div class="flex-1" />

            <nav class="border-t border-zinc-200 p-2 dark:border-zinc-700">
                <button
                    v-for="item in bottomNavItems"
                    :key="item.label"
                    type="button"
                    :class="[
                        'mb-1 flex w-full items-center gap-3 rounded-lg py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800',
                        collapsed && !mobileOpen ? 'justify-center px-0' : 'px-3',
                    ]"
                    :title="collapsed && !mobileOpen ? item.label : undefined"
                    @click="handleBottomItem(item)"
                >
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <FluxIcon :name="item.icon" />
                    </span>
                    <span v-if="!collapsed || mobileOpen" class="truncate">{{ item.label }}</span>
                </button>
            </nav>

            <div ref="profileMenuRef" class="border-t border-zinc-200 p-2 dark:border-zinc-700">
                <button
                    type="button"
                    :class="[
                        'flex w-full items-center gap-3 rounded-lg px-2 py-2 text-right hover:bg-zinc-100 dark:hover:bg-zinc-800',
                        collapsed && !mobileOpen ? 'justify-center' : '',
                    ]"
                    @click="toggleProfileMenu"
                >
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-xs font-semibold text-white dark:bg-zinc-100 dark:text-zinc-900">
                        {{ (userName || 'U').charAt(0) }}
                    </span>
                    <div v-if="!collapsed || mobileOpen" class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ userName }}</div>
                        <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ userEmail }}</div>
                    </div>
                </button>

                <div
                    v-if="profileMenuOpen && (!collapsed || mobileOpen)"
                    class="mt-2 rounded-lg border border-zinc-200 bg-white p-1 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
                >
                    <button
                        type="button"
                        class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30"
                        @click="logout"
                    >
                        <FluxIcon name="logout" />
                        <span>تسجيل الخروج</span>
                    </button>
                </div>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-14 shrink-0 items-center gap-2 border-b border-zinc-200 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900 lg:hidden">
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 text-zinc-700 dark:border-zinc-700 dark:text-zinc-300"
                    title="القائمة"
                    @click="toggleMobile"
                >
                    <FluxIcon name="bars" />
                </button>
                <div class="flex-1" />
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300"
                    title="الملف الشخصي"
                >
                    {{ (userName || 'U').charAt(0) }}
                </button>
            </header>

            <div class="min-h-0 min-w-0 flex-1 overflow-auto">
                <slot />
            </div>
        </div>
    </div>
</template>
