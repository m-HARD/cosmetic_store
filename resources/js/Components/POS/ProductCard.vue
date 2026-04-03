<script setup>
defineProps({ product: Object });
const emit = defineEmits(['add']);
</script>

<template>
    <button class="rounded-xl bg-white p-3 text-right shadow" @click="emit('add', product)">
        <img :src="product.image || 'https://via.placeholder.com/160x100'" class="mb-2 h-24 w-full rounded-md object-cover" />
        <p class="font-semibold">{{ product.name }}</p>
        <p class="text-sm text-slate-500">{{ product.sale_price }} ج.س</p>
    </button>
</template>
