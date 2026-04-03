<script setup>
defineProps({ product: Object });
const emit = defineEmits(['add']);
</script>

<template>
    <button
        type="button"
        class="rounded-xl bg-white p-3 text-right shadow disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="(product.stock ?? 0) <= 0"
        @click="emit('add', product)"
    >
        <img
            :src="product.image || 'https://via.placeholder.com/160x100'"
            class="mb-2 h-24 w-full rounded-md object-cover"
            alt=""
        />
        <p class="font-semibold">{{ product.name }}</p>
        <p class="text-sm text-slate-500">{{ product.sale_price }} ج.س</p>
        <p v-if="(product.stock ?? 0) <= 0" class="mt-1 text-xs font-medium text-red-600">نفد المخزون</p>
        <p v-else class="mt-1 text-xs text-slate-400">متاح: {{ product.stock }}</p>
    </button>
</template>
