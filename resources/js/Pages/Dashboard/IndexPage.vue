<script setup>
import AppSidebarLayout from '../../Layouts/AppSidebarLayout.vue';

const props = defineProps({
    summary: { type: Object, default: () => ({}) },
});
</script>

<template>
    <AppSidebarLayout>
        <main class="p-6">
            <h1 class="mb-6 text-2xl font-bold">لوحة التحكم</h1>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-xl bg-white p-4 shadow">مبيعات اليوم: {{ props.summary.today_sales ?? 0 }}</div>
                <div class="rounded-xl bg-white p-4 shadow">مبيعات الشهر: {{ props.summary.month_sales ?? 0 }}</div>
                <div class="rounded-xl bg-white p-4 shadow">المصروفات: {{ props.summary.total_expenses ?? 0 }}</div>
                <div class="rounded-xl bg-white p-4 shadow">الخسائر: {{ props.summary.total_losses ?? 0 }}</div>
                <div class="rounded-xl bg-white p-4 shadow">صافي الربح: {{ props.summary.net_profit ?? 0 }}</div>
                <div class="rounded-xl bg-white p-4 shadow">منتجات منخفضة: {{ props.summary.low_stock_count ?? 0 }}</div>
            </div>
        </main>
    </AppSidebarLayout>
</template>
