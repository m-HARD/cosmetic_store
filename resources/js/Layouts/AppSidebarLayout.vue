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
const sidebarHovered = ref(false);
const hoveredCollapsedGroup = ref(null);
const hoveredPopover = ref(false);
const groupPopoverStyle = ref({});
const isDesktop = ref(false);
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

const currentGroupId = computed(() => {
    const group = visibleGroups.value.find((g) => g.items.some((item) => isCurrent(item.href)));
    return group?.id ?? null;
});

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

const POPOVER_WIDTH = 224;

/** عند الانتقال لصفحة: إغلاق كل المجموعات وفتح التي تحتوي المسار الحالي فقط (المستخدم يقدر يفتح باقي المجموعات يدويًا بعدها). */
function syncExpandedGroupsFromRoute() {
    const next = {};
    for (const group of visibleGroups.value) {
        next[group.id] = group.id === currentGroupId.value;
    }
    expandedGroups.value = next;
}

function updateViewportState() {
    isDesktop.value = window.innerWidth >= 1024;
}

function handleAsideClick() {
    if (isDesktop.value && collapsed.value && !mobileOpen.value) {
        collapsed.value = false;
    }
}

function handleCollapsedGroupEnter(group, event) {
    if (!isDesktop.value || !collapsed.value || mobileOpen.value) {
        return;
    }
    hoveredCollapsedGroup.value = group;
    const rect = event.currentTarget.getBoundingClientRect();
    groupPopoverStyle.value = {
        top: `${rect.top}px`,
        left: `${rect.left - POPOVER_WIDTH}px`,
    };
}

function clearCollapsedGroupHover() {
    if (hoveredPopover.value) {
        return;
    }
    hoveredCollapsedGroup.value = null;
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

    updateViewportState();
    syncExpandedGroupsFromRoute();
    document.addEventListener('click', onClickOutside);
    window.addEventListener('resize', updateViewportState);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside);
    window.removeEventListener('resize', updateViewportState);
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

watch(currentUrl, () => {
    syncExpandedGroupsFromRoute();
});
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
                collapsed && !mobileOpen ? 'lg:w-11' : 'lg:w-72',
                'w-72',
                collapsed && !mobileOpen ? 'lg:px-0' : '',
            ]"
            @mouseenter="sidebarHovered = true"
            @mouseleave="
                sidebarHovered = false;
                clearCollapsedGroupHover();
            "
            @click="handleAsideClick"
        >
            <div
                :class="[
                    'flex h-14 shrink-0 items-center border-b border-zinc-200 dark:border-zinc-700',
                    collapsed && !mobileOpen ? 'justify-center px-0 lg:px-0' : 'gap-2 px-3',
                ]"
                @click.stop
            >
                <template v-if="collapsed && !mobileOpen && isDesktop">
                    <div class="relative flex h-8 w-8 shrink-0 items-center justify-center">
                        <Link
                            v-show="!sidebarHovered"
                            href="/dashboard"
                            class="absolute inset-0 flex items-center justify-center rounded-lg bg-zinc-900 text-xs font-bold text-white dark:bg-zinc-100 dark:text-zinc-900"
                            @click="closeMobile"
                        >
                            CS
                        </Link>
                        <button
                            v-show="sidebarHovered"
                            type="button"
                            class="absolute inset-0 flex items-center justify-center rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            title="توسيع القائمة"
                            @click="toggleCollapse"
                        >
                            <FluxIcon name="chevronLeft" />
                        </button>
                    </div>
                </template>
                <template v-else>
                    <Link
                        href="/dashboard"
                        class="flex min-w-0 flex-1 items-center gap-2 truncate rounded-lg px-2 py-1.5 text-sm font-semibold text-zinc-800 hover:bg-zinc-100 dark:text-zinc-100 dark:hover:bg-zinc-800"
                        @click="closeMobile"
                    >
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-zinc-900 text-xs font-bold text-white dark:bg-zinc-100 dark:text-zinc-900">
                            CS
                        </span>
                        <span class="truncate">Cosmetic Store</span>
                    </Link>
                    <button
                        type="button"
                        class="hidden h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 lg:flex"
                        :title="collapsed ? 'توسيع القائمة' : 'طي القائمة'"
                        @click="toggleCollapse"
                    >
                        <FluxIcon :name="collapsed ? 'chevronLeft' : 'chevronRight'" />
                    </button>
                </template>
                <button
                    type="button"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 lg:hidden"
                    title="إغلاق"
                    @click="closeMobile"
                >
                    <FluxIcon name="chevronRight" />
                </button>
            </div>

            <nav
                :class="['overflow-y-auto', collapsed && !mobileOpen ? 'p-1 lg:p-1' : 'p-2']"
                @click.stop
            >
                <template v-for="group in visibleGroups" :key="group.id">
                    <div
                        class="relative mb-2"
                        @mouseenter="handleCollapsedGroupEnter(group, $event)"
                        @mouseleave="clearCollapsedGroupHover"
                    >
                        <button
                            type="button"
                            :class="[
                                'flex w-full items-center gap-2 rounded-lg py-2 text-right text-sm font-semibold text-zinc-800 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800',
                                collapsed && !mobileOpen ? 'justify-center px-0 lg:px-0' : 'px-2',
                            ]"
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

                        <div v-show="isGroupExpanded(group.id)" class="mt-1 flex gap-2 px-2">
                            <span class="h-auto w-7 shrink-0" aria-hidden="true" />
                            <div class="min-w-0 flex-1 space-y-0.5 border-r-2 border-zinc-300 pr-3 dark:border-zinc-600">
                                <Link
                                    v-for="item in group.items"
                                    :key="item.href"
                                    :href="item.href"
                                    :class="[
                                        'flex items-center rounded-lg py-2 text-sm font-medium transition',
                                        collapsed && !mobileOpen ? 'justify-center px-0' : 'px-2',
                                        isCurrent(item.href)
                                            ? 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200'
                                            : 'text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800',
                                    ]"
                                    :title="collapsed && !mobileOpen ? item.label : undefined"
                                    @click="closeMobile"
                                >
                                    <span v-if="!collapsed || mobileOpen" class="truncate">{{ item.label }}</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </template>
            </nav>

            <div class="flex-1" />

            <nav
                :class="['border-t border-zinc-200 dark:border-zinc-700', collapsed && !mobileOpen ? 'p-1 lg:p-1' : 'p-2']"
                @click.stop
            >
                <button
                    v-for="item in bottomNavItems"
                    :key="item.label"
                    type="button"
                    :class="[
                        'mb-1 flex w-full items-center gap-3 rounded-lg py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800',
                        collapsed && !mobileOpen ? 'justify-center px-0 lg:px-0' : 'px-3',
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

            <div
                ref="profileMenuRef"
                :class="['border-t border-zinc-200 dark:border-zinc-700', collapsed && !mobileOpen ? 'p-1 lg:p-1' : 'p-2']"
                @click.stop
            >
                <button
                    type="button"
                    :class="[
                        'flex w-full items-center gap-3 rounded-lg py-2 text-right hover:bg-zinc-100 dark:hover:bg-zinc-800',
                        collapsed && !mobileOpen ? 'justify-center px-0 lg:px-0' : 'px-2',
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

        <Teleport to="body">
            <div
                v-if="hoveredCollapsedGroup && collapsed && isDesktop && !mobileOpen"
                class="fixed z-120 w-56 rounded-xl border border-zinc-200 bg-white p-2 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900"
                :style="groupPopoverStyle"
                @mouseenter="hoveredPopover = true"
                @mouseleave="
                    hoveredPopover = false;
                    clearCollapsedGroupHover();
                "
            >
                <div class="mb-2 border-b border-zinc-100 px-2 pb-2 text-xs font-bold text-zinc-700 dark:border-zinc-800 dark:text-zinc-300">
                    {{ hoveredCollapsedGroup.heading }}
                </div>
                <div class="flex gap-2 px-1">
                    <span class="h-auto w-6 shrink-0" aria-hidden="true" />
                    <div class="min-w-0 flex-1 space-y-1 border-r-2 border-zinc-300 pr-2 dark:border-zinc-600">
                        <Link
                            v-for="item in hoveredCollapsedGroup.items"
                            :key="`quick-${item.href}`"
                            :href="item.href"
                            :class="[
                                'block rounded-md px-2 py-1.5 text-sm',
                                isCurrent(item.href)
                                    ? 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100'
                                    : 'text-zinc-500 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800',
                            ]"
                            @click="
                                hoveredPopover = false;
                                clearCollapsedGroupHover();
                            "
                        >
                            {{ item.label }}
                        </Link>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
