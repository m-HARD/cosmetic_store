<script setup>
import AppSidebarLayout from '../../Layouts/AppSidebarLayout.vue';

const props = defineProps({
    summary: { type: Object, default: () => ({}) },
});
</script>

<template>
    <AppSidebarLayout>
        <main class="p-6">
            <h1 class="mb-6 text-2xl font-bold">التقارير المالية</h1>
            <div class="rounded-xl bg-white p-4 shadow">
                <p>مبيعات الشهر: {{ props.summary.month_sales ?? 0 }}</p>
                <p>إجمالي المصروفات: {{ props.summary.total_expenses ?? 0 }}</p>
                <p>إجمالي الخسائر: {{ props.summary.total_losses ?? 0 }}</p>
                <p>صافي الربح: {{ props.summary.net_profit ?? 0 }}</p>
            </div>
        </main>
    </AppSidebarLayout>
</template>
