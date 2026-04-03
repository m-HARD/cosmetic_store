<script setup>
defineProps({ tabs: Array, activeId: Number });
const emit = defineEmits(['select', 'create']);
</script>

<template>
    <div class="flex gap-2 overflow-x-auto rounded-xl bg-white p-2 shadow">
        <button v-for="tab in tabs" :key="tab.id" class="rounded-lg px-3 py-2 text-sm"
            :class="tab.id === activeId ? 'bg-slate-900 text-white' : 'bg-slate-100'"
            @click="emit('select', tab.id)">
            {{ tab.name }}
        </button>
        <button class="rounded-lg bg-emerald-600 px-3 py-2 text-sm text-white" @click="emit('create')">+ فاتورة جديدة</button>
    </div>
</template>
