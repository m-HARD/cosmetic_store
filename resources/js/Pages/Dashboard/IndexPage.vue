<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    summary: { type: Object, default: () => ({}) },
});

function logout() {
    router.post('/logout');
}
</script>

<template>
    <main class="p-6">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold">لوحة التحكم</h1>
            <div class="flex flex-wrap gap-2">
                <a
                    href="/pos"
                    class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    نقطة البيع
                </a>
                <button
                    type="button"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    @click="logout"
                >
                    تسجيل الخروج
                </button>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-xl bg-white p-4 shadow">مبيعات اليوم: {{ props.summary.today_sales ?? 0 }}</div>
            <div class="rounded-xl bg-white p-4 shadow">مبيعات الشهر: {{ props.summary.month_sales ?? 0 }}</div>
            <div class="rounded-xl bg-white p-4 shadow">المصروفات: {{ props.summary.total_expenses ?? 0 }}</div>
            <div class="rounded-xl bg-white p-4 shadow">الخسائر: {{ props.summary.total_losses ?? 0 }}</div>
            <div class="rounded-xl bg-white p-4 shadow">صافي الربح: {{ props.summary.net_profit ?? 0 }}</div>
            <div class="rounded-xl bg-white p-4 shadow">منتجات منخفضة: {{ props.summary.low_stock_count ?? 0 }}</div>
        </div>
    </main>
</template>
