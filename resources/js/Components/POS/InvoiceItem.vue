<script setup>
defineProps({ item: Object });
const emit = defineEmits(['inc', 'dec', 'remove']);
</script>

<template>
    <div class="mb-2 rounded-lg bg-slate-100 p-2">
        <div class="flex items-center justify-between">
            <strong>{{ item.name }}</strong>
            <button class="text-red-600" @click="emit('remove', item.id)">حذف</button>
        </div>
        <div class="mt-2 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <button class="rounded bg-white px-2" @click="emit('dec', item.id)">-</button>
                <span>{{ item.quantity }}</span>
                <button class="rounded bg-white px-2" @click="emit('inc', item.id)">+</button>
            </div>
            <span>{{ item.line_total }} ج.س</span>
        </div>
    </div>
</template>
