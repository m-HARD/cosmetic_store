<script setup>
defineProps({
    open: Boolean,
    total: Number,
    paymentMethod: String,
    bankakLast5: String,
});
const emit = defineEmits(['close', 'pay', 'update:paymentMethod', 'update:bankakLast5']);
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50 grid place-items-center bg-black/30">
        <div class="w-full max-w-md rounded-xl bg-white p-4">
            <h3 class="mb-3 text-lg font-bold">نافذة الدفع</h3>
            <p class="mb-3">الإجمالي: {{ total }} ج.س</p>
            <select class="mb-3 w-full rounded border p-2" :value="paymentMethod" @change="emit('update:paymentMethod', $event.target.value)">
                <option value="cash">Cash</option>
                <option value="bankak">Bankak</option>
            </select>
            <input
                v-if="paymentMethod === 'bankak'"
                class="mb-3 w-full rounded border p-2"
                :value="bankakLast5"
                maxlength="5"
                placeholder="آخر 5 أرقام"
                @input="emit('update:bankakLast5', $event.target.value)"
            />
            <div class="flex gap-2">
                <button class="flex-1 rounded bg-slate-200 py-2" @click="emit('close')">إلغاء</button>
                <button class="flex-1 rounded bg-emerald-600 py-2 text-white" @click="emit('pay')">تأكيد</button>
            </div>
        </div>
    </div>
</template>
