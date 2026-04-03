<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const STORAGE_KEY = 'cosmetic_sidebar_collapsed';

const page = usePage();
const collapsed = ref(true);
const mobileOpen = ref(false);

const roles = computed(() => page.props.auth?.user?.roles ?? []);

/** المدير الأعلى يرى كل عناصر القائمة دون استثناء. */
function hasAccess(allowedRoles) {
    if (!allowedRoles?.length) {
        return true;
    }
    if (roles.value.includes('SUPER ADMIN')) {
        return true;
    }
    return allowedRoles.some((r) => roles.value.includes(r));
}

const currentUrl = computed(() => page.url.split('?')[0]);

function isCurrent(href) {
    return currentUrl.value === href || currentUrl.value.startsWith(`${href}/`);
}

onMounted(() => {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved !== null) {
        collapsed.value = saved === '1';
    }
});

watch(collapsed, (v) => {
    localStorage.setItem(STORAGE_KEY, v ? '1' : '0');
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

function logout() {
    router.post('/logout');
}

const navGroups = computed(() => [
    {
        heading: 'الرئيسية',
        items: [
            {
                label: 'لوحة التحكم',
                href: '/dashboard',
                roles: ['SUPER ADMIN', 'INVENTORY MANAGER', 'CASHIER', 'ACCOUNTS'],
                icon: 'home',
            },
        ],
    },
    {
        heading: 'المبيعات',
        items: [
            {
                label: 'نقطة البيع',
                href: '/pos',
                roles: ['SUPER ADMIN', 'INVENTORY MANAGER', 'CASHIER', 'ACCOUNTS'],
                icon: 'pos',
            },
            { label: 'المرتجعات', href: '/refunds', roles: ['SUPER ADMIN', 'CASHIER'], icon: 'refund' },
        ],
    },
    {
        heading: 'المخزون والمنتجات',
        items: [
            { label: 'المنتجات', href: '/products', roles: ['SUPER ADMIN', 'INVENTORY MANAGER'], icon: 'box' },
            { label: 'الموردون', href: '/suppliers', roles: ['SUPER ADMIN', 'INVENTORY MANAGER'], icon: 'truck' },
            {
                label: 'المخزون والصلاحية',
                href: '/inventory',
                roles: ['SUPER ADMIN', 'INVENTORY MANAGER'],
                icon: 'warehouse',
            },
        ],
    },
    {
        heading: 'المالية والتقارير',
        items: [
            {
                label: 'التقارير',
                href: '/reports',
                roles: ['SUPER ADMIN', 'INVENTORY MANAGER', 'CASHIER', 'ACCOUNTS'],
                icon: 'chart',
            },
            { label: 'المصروفات', href: '/expenses', roles: ['SUPER ADMIN', 'ACCOUNTS'], icon: 'wallet' },
        ],
    },
    {
        heading: 'النظام',
        items: [{ label: 'المستخدمون والأدوار', href: '/users', roles: ['SUPER ADMIN'], icon: 'users' }],
    },
]);

const userName = computed(() => page.props.auth?.user?.name ?? '');

const sidebarWidthLg = computed(() => (collapsed.value ? 'lg:w-16' : 'lg:w-64'));

function navIcon(type) {
    const map = {
        home: '⌂',
        pos: '¤',
        refund: '↩',
        box: '▦',
        truck: '⛟',
        warehouse: '▣',
        chart: '▤',
        wallet: '◎',
        users: '👤',
    };
    return map[type] ?? '•';
}
</script>

<template>
    <div class="flex min-h-screen bg-slate-100 text-slate-900" dir="rtl">
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
                'flex shrink-0 flex-col border-l border-slate-200 bg-white transition-[width,transform] duration-200 ease-out',
                'fixed inset-y-0 right-0 z-50 w-64 lg:relative lg:z-auto lg:h-screen',
                mobileOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0',
                sidebarWidthLg,
            ]"
        >
            <div class="flex h-14 shrink-0 items-center justify-between gap-2 border-b border-slate-200 px-2">
                <Link
                    href="/dashboard"
                    class="flex min-w-0 flex-1 items-center gap-2 truncate px-2 font-bold text-indigo-700"
                    @click="closeMobile"
                >
                    <!-- مطوي على سطح المكتب فقط: أيقونة مختصرة؛ الموبايل عند فتح الدرج يعرض الاسم كاملًا -->
                    <span
                        v-if="collapsed && !mobileOpen"
                        class="hidden h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-xs font-bold text-white lg:inline-flex"
                    >
                        م
                    </span>
                    <span v-else class="truncate">متجر التجميل</span>
                </Link>
                <button
                    type="button"
                    class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 lg:flex"
                    :title="collapsed ? 'توسيع القائمة' : 'طي القائمة'"
                    @click="toggleCollapse"
                >
                    <span class="text-lg leading-none">{{ collapsed ? '«' : '»' }}</span>
                </button>
                <button
                    type="button"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 lg:hidden"
                    title="إغلاق"
                    @click="closeMobile"
                >
                    ✕
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto py-3">
                <template v-for="(group, gi) in navGroups" :key="gi">
                    <div v-if="group.items.some((i) => hasAccess(i.roles))" class="mb-4">
                        <p
                            v-if="!collapsed || mobileOpen"
                            class="mb-2 px-4 text-xs font-semibold uppercase tracking-wide text-slate-400"
                        >
                            {{ group.heading }}
                        </p>
                        <div class="space-y-0.5 px-2">
                            <template v-for="item in group.items" :key="item.href">
                                <Link
                                    v-if="hasAccess(item.roles)"
                                    :href="item.href"
                                    :class="[
                                        'flex items-center gap-3 rounded-lg py-2.5 text-sm font-medium transition',
                                        collapsed && !mobileOpen ? 'justify-center px-0' : 'px-3',
                                        isCurrent(item.href)
                                            ? 'bg-indigo-50 text-indigo-800'
                                            : 'text-slate-700 hover:bg-slate-100',
                                    ]"
                                    :title="collapsed && !mobileOpen ? item.label : undefined"
                                    @click="closeMobile"
                                >
                                    <span
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-slate-100 text-slate-600"
                                    >
                                        {{ navIcon(item.icon) }}
                                    </span>
                                    <span v-if="!collapsed || mobileOpen" class="truncate">{{ item.label }}</span>
                                </Link>
                            </template>
                        </div>
                    </div>
                </template>
            </nav>

            <div class="border-t border-slate-200 p-2">
                <div
                    v-if="!collapsed || mobileOpen"
                    class="mb-2 truncate rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600"
                >
                    {{ userName }}
                </div>
                <button
                    type="button"
                    :class="[
                        'flex w-full items-center gap-2 rounded-lg border border-slate-200 py-2 text-sm font-medium text-red-700 hover:bg-red-50',
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
            <header class="flex h-14 shrink-0 items-center gap-2 border-b border-slate-200 bg-white px-3 lg:hidden">
                <button
                    type="button"
                    class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200"
                    title="القائمة"
                    @click="toggleMobile"
                >
                    ☰
                </button>
                <span class="font-semibold text-slate-800">التنقل</span>
            </header>

            <div class="min-h-0 min-w-0 flex-1 overflow-auto">
                <slot />
            </div>
        </div>
    </div>
</template>
