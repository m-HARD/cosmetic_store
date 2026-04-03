<script setup>
defineProps({ modelValue: String });
const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <input
        :value="modelValue"
        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"
        placeholder="بحث بالاسم أو الباركود..."
        @input="emit('update:modelValue', $event.target.value)"
    />
</template>
