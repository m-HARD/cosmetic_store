<script setup>
import ProductCard from './ProductCard.vue';
defineProps({ products: Array });
const emit = defineEmits(['add']);
</script>

<template>
    <div class="grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-2 lg:grid-cols-3">
        <ProductCard v-for="p in products" :key="p.id" :product="p" @add="emit('add', $event)" />
    </div>
</template>
