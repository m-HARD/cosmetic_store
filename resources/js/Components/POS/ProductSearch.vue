<script setup>
import { ref } from 'vue';

defineProps({ modelValue: String });
const emit = defineEmits(['update:modelValue', 'enter']);

const inputRef = ref(null);

function onKeydown(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        emit('enter');
    }
}

defineExpose({
    focus: () => inputRef.value?.focus(),
});
</script>

<template>
    <input
        ref="inputRef"
        :value="modelValue"
        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"
        placeholder="بحث بالاسم أو الباركود..."
        @input="emit('update:modelValue', $event.target.value)"
        @keydown="onKeydown"
    />
</template>
