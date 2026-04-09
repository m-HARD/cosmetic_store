<script setup>
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import AppSidebarLayout from '../../Layouts/AppSidebarLayout.vue';

const loading = ref(true);
const err = ref('');
const expiringDays = ref(30);
const expiring = ref([]);
const lowStock = ref([]);
const allBatches = ref([]);
const productsForSelect = ref([]);

const lossOpen = ref(false);
const lossSaving = ref(false);
const lossForm = ref({
    product_id: '',
    batch_id: '',
    quantity: 1,
    loss_value: '',
    reason: '',
});
const lossErrors = ref({});
const batchesForLoss = ref([]);

const fmtDate = (d) => (d ? String(d).slice(0, 10) : '—');

async function refresh() {
    loading.value = true;
    err.value = '';
    try {
        const [ex, low, bat, prod] = await Promise.all([
            axios.get('/api/inventory/expiring', { params: { days: expiringDays.value } }),
            axios.get('/api/inventory/low-stock'),
            axios.get('/api/inventory/batches'),
            axios.get('/api/products', { params: { per_page: 100 } }),
        ]);
        expiring.value = ex.data;
        lowStock.value = low.data;
        allBatches.value = bat.data;
        productsForSelect.value = prod.data.data ?? prod.data ?? [];
    } catch (e) {
        err.value = e.response?.data?.message ?? 'تعذر تحميل بيانات المخزون.';
    } finally {
        loading.value = false;
    }
}

async function loadBatchesForProduct(productId) {
    if (!productId) {
        batchesForLoss.value = [];
        return;
    }
    const { data } = await axios.get(`/api/products/${productId}/batches`);
    batchesForLoss.value = data;
}

async function submitLoss() {
    lossSaving.value = true;
    lossErrors.value = {};
    try {
        await axios.post('/api/inventory/losses', {
            product_id: Number(lossForm.value.product_id),
            batch_id: lossForm.value.batch_id ? Number(lossForm.value.batch_id) : null,
            quantity: Number(lossForm.value.quantity),
            loss_value: Number(lossForm.value.loss_value),
            reason: lossForm.value.reason,
        });
        lossOpen.value = false;
        lossForm.value = { product_id: '', batch_id: '', quantity: 1, loss_value: '', reason: '' };
        batchesForLoss.value = [];
        await refresh();
    } catch (e) {
        if (e.response?.status === 422) {
            lossErrors.value = e.response.data.errors ?? {};
        } else {
            err.value = e.response?.data?.message ?? 'فشل تسجيل الخسارة.';
        }
    } finally {
        lossSaving.value = false;
    }
}

function openLoss() {
    lossForm.value = { product_id: '', batch_id: '', quantity: 1, loss_value: '', reason: '' };
    lossErrors.value = {};
    batchesForLoss.value = [];
    lossOpen.value = true;
}

const expiringSoonLabel = computed(() => `خلال ${expiringDays.value} يومًا`);

watch(
    () => lossForm.value.product_id,
    (id) => {
        lossForm.value.batch_id = '';
        if (id) loadBatchesForProduct(id);
        else batchesForLoss.value = [];
    }
);

watch(expiringDays, () => {
    refresh();
});

onMounted(refresh);
</script>

<template>
    <AppSidebarLayout>
        <main class="p-6">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-50">المخزون والصلاحية</h1>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">دفعات قاربت على الانتهاء، مخزون منخفض، وجدول الدفعات وتسجيل الخسائر.</p>
                </div>
                <button
                    type="button"
                    class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-zinc-100 dark:text-zinc-900"
                    @click="openLoss"
                >
                    تسجيل خسارة
                </button>
            </div>

            <p v-if="err" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                {{ err }}
            </p>

            <div v-if="loading" class="rounded-xl border border-zinc-200 bg-white p-8 text-center text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">جاري التحميل…</div>

            <div v-else class="space-y-8">
                <section class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-lg font-semibold">قرب انتهاء الصلاحية</h2>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <span>النافذة (أيام)</span>
                            <input v-model.number="expiringDays" type="number" min="1" max="365" class="w-20 rounded border border-zinc-300 px-2 py-1 dark:border-zinc-600 dark:bg-zinc-800" />
                        </label>
                    </div>
                    <p class="mb-2 text-xs text-zinc-500">{{ expiringSoonLabel }} — دفعات بكمية متبقية &gt; 0</p>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] text-right text-sm">
                            <thead class="border-b border-zinc-200 dark:border-zinc-700">
                                <tr>
                                    <th class="px-2 py-1">المنتج</th>
                                    <th class="px-2 py-1">رمز الدفعة</th>
                                    <th class="px-2 py-1">الانتهاء</th>
                                    <th class="px-2 py-1">متبقي</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in expiring" :key="row.id" class="border-b border-zinc-100 dark:border-zinc-800">
                                    <td class="px-2 py-1">{{ row.product?.name ?? '—' }}</td>
                                    <td class="px-2 py-1 font-mono text-xs">{{ row.batch_code || '—' }}</td>
                                    <td class="px-2 py-1">{{ fmtDate(row.expiration_date) }}</td>
                                    <td class="px-2 py-1">{{ row.remaining_quantity }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-if="!expiring.length" class="py-4 text-center text-sm text-zinc-500">لا توجد دفعات في هذه النافذة.</p>
                </section>

                <section class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="mb-3 text-lg font-semibold">مخزون منخفض (≤ حد التنبيه)</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[480px] text-right text-sm">
                            <thead class="border-b border-zinc-200 dark:border-zinc-700">
                                <tr>
                                    <th class="px-2 py-1">المنتج</th>
                                    <th class="px-2 py-1">الإجمالي المتبقي</th>
                                    <th class="px-2 py-1">حد التنبيه</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in lowStock" :key="row.id" class="border-b border-zinc-100 dark:border-zinc-800">
                                    <td class="px-2 py-1">{{ row.name }}</td>
                                    <td class="px-2 py-1 font-medium text-amber-800 dark:text-amber-200">{{ row.total_stock ?? 0 }}</td>
                                    <td class="px-2 py-1">{{ row.min_stock_alert }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-if="!lowStock.length" class="py-4 text-center text-sm text-zinc-500">لا توجد منتجات تحت حد التنبيه حاليًا.</p>
                </section>

                <section class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="mb-3 text-lg font-semibold">كل الدفعات (حتى 500)</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px] text-right text-xs sm:text-sm">
                            <thead class="border-b border-zinc-200 dark:border-zinc-700">
                                <tr>
                                    <th class="px-2 py-1">المنتج</th>
                                    <th class="px-2 py-1">الباركود</th>
                                    <th class="px-2 py-1">الدفعة</th>
                                    <th class="px-2 py-1">انتهاء</th>
                                    <th class="px-2 py-1">متبقي / أصلي</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in allBatches" :key="row.id" class="border-b border-zinc-100 dark:border-zinc-800">
                                    <td class="px-2 py-1">{{ row.product?.name ?? '—' }}</td>
                                    <td class="px-2 py-1 font-mono">{{ row.product?.barcode ?? '—' }}</td>
                                    <td class="px-2 py-1 font-mono">{{ row.batch_code || '—' }}</td>
                                    <td class="px-2 py-1">{{ fmtDate(row.expiration_date) }}</td>
                                    <td class="px-2 py-1">{{ row.remaining_quantity }} / {{ row.quantity }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div
                v-if="lossOpen"
                class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center"
                @click.self="lossOpen = false"
            >
                <div class="w-full max-w-md rounded-xl bg-white p-4 shadow-xl dark:bg-zinc-900" @click.stop>
                    <h2 class="mb-3 text-lg font-bold">تسجيل خسارة</h2>
                    <p class="mb-3 text-xs text-zinc-500">عند اختيار دفعة يُخصم المتبقي منها تلقائيًا.</p>
                    <div class="space-y-3 text-sm">
                        <div>
                            <label class="mb-1 block text-zinc-600">المنتج</label>
                            <select v-model="lossForm.product_id" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800">
                                <option value="">— اختر —</option>
                                <option v-for="p in productsForSelect" :key="p.id" :value="String(p.id)">{{ p.name }}</option>
                            </select>
                            <p v-if="lossErrors.product_id" class="mt-1 text-xs text-red-600">{{ lossErrors.product_id[0] }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-zinc-600">الدفعة (اختياري)</label>
                            <select v-model="lossForm.batch_id" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" :disabled="!lossForm.product_id">
                                <option value="">— بدون دفعة —</option>
                                <option v-for="b in batchesForLoss" :key="b.id" :value="String(b.id)">
                                    {{ b.batch_code || b.id }} — متبقي {{ b.remaining_quantity }} — {{ fmtDate(b.expiration_date) }}
                                </option>
                            </select>
                            <p v-if="lossErrors.batch_id" class="mt-1 text-xs text-red-600">{{ lossErrors.batch_id[0] }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="mb-1 block text-zinc-600">الكمية</label>
                                <input v-model.number="lossForm.quantity" type="number" min="1" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" />
                                <p v-if="lossErrors.quantity" class="mt-1 text-xs text-red-600">{{ lossErrors.quantity[0] }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-zinc-600">قيمة الخسارة</label>
                                <input v-model="lossForm.loss_value" type="number" step="0.01" min="0" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" />
                                <p v-if="lossErrors.loss_value" class="mt-1 text-xs text-red-600">{{ lossErrors.loss_value[0] }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-zinc-600">السبب</label>
                            <input v-model="lossForm.reason" maxlength="100" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" />
                            <p v-if="lossErrors.reason" class="mt-1 text-xs text-red-600">{{ lossErrors.reason[0] }}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" class="rounded border border-zinc-300 px-3 py-1.5 dark:border-zinc-600" @click="lossOpen = false">إلغاء</button>
                        <button
                            type="button"
                            class="rounded bg-zinc-900 px-3 py-1.5 text-white disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900"
                            :disabled="lossSaving || !lossForm.product_id"
                            @click="submitLoss"
                        >
                            {{ lossSaving ? '…' : 'تسجيل' }}
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </AppSidebarLayout>
</template>
