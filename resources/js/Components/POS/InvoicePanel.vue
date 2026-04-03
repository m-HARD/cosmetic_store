<script setup>
import InvoiceItem from './InvoiceItem.vue';
defineProps({ items: Array, total: Number });
const emit = defineEmits(['inc', 'dec', 'remove', 'pay']);
</script>

<template>
    <div class="h-full rounded-xl bg-white p-3 shadow">
        <h2 class="mb-3 font-semibold">الفاتورة الحالية</h2>
        <InvoiceItem
            v-for="item in items"
            :key="item.id"
            :item="item"
            @inc="emit('inc', $event)"
            @dec="emit('dec', $event)"
            @remove="emit('remove', $event)"
        />
        <div class="mt-4 border-t pt-3 font-bold">الإجمالي: {{ total }} ج.س</div>
        <button class="mt-3 w-full rounded-xl bg-emerald-600 py-3 font-semibold text-white" @click="emit('pay')">الدفع</button>
    </div>
</template>
