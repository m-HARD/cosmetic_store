<script setup>
defineProps({ categories: Array, activeCategory: [Number, String] });
const emit = defineEmits(['select']);
</script>

<template>
    <div class="h-full rounded-xl bg-white p-3 shadow">
        <h2 class="mb-3 font-semibold">الفئات</h2>
        <button v-for="c in categories" :key="c.id" class="mb-2 block w-full rounded-lg px-3 py-2 text-right"
            :class="activeCategory === c.id ? 'bg-indigo-600 text-white' : 'bg-slate-100'" @click="emit('select', c.id)">
            {{ c.name }}
        </button>
    </div>
</template>
