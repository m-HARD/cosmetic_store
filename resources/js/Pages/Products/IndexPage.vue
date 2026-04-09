<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AppSidebarLayout from '../../Layouts/AppSidebarLayout.vue';

const loading = ref(true);
const saving = ref(false);
const batchSaving = ref(false);
const errorMsg = ref('');
const fieldErrors = ref({});

const categories = ref([]);
const suppliers = ref([]);
const pagination = ref({ data: [], current_page: 1, last_page: 1, per_page: 20, total: 0 });

const productModalOpen = ref(false);
const editingId = ref(null);
const productForm = reactive({
    name: '',
    description: '',
    barcode: '',
    category_id: '',
    supplier_id: '',
    sale_price: '',
    min_stock_alert: 0,
    is_active: true,
});

const batchesModalOpen = ref(false);
const batchProduct = ref(null);
const batches = ref([]);
const batchesLoading = ref(false);
const newBatch = reactive({
    batch_code: '',
    expiration_date: '',
    quantity: 1,
    remaining_quantity: '',
    cost_price: '',
});
const editingRemaining = reactive({});

const money = (n) => (Number(n) ?? 0).toFixed(2);
const fmtDate = (d) => (d ? String(d).slice(0, 10) : '—');

async function fetchMeta() {
    const [catRes, supRes] = await Promise.all([
        axios.get('/api/categories'),
        axios.get('/api/suppliers/options'),
    ]);
    categories.value = catRes.data;
    suppliers.value = supRes.data;
}

async function fetchProducts(page = 1) {
    loading.value = true;
    errorMsg.value = '';
    try {
        const { data } = await axios.get('/api/products', { params: { page, per_page: 15 } });
        pagination.value = data;
    } catch (e) {
        errorMsg.value = e.response?.data?.message ?? 'تعذر تحميل المنتجات.';
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    editingId.value = null;
    Object.assign(productForm, {
        name: '',
        description: '',
        barcode: '',
        category_id: categories.value[0]?.id ?? '',
        supplier_id: '',
        sale_price: '',
        min_stock_alert: 0,
        is_active: true,
    });
    fieldErrors.value = {};
    productModalOpen.value = true;
}

function openEdit(row) {
    editingId.value = row.id;
    Object.assign(productForm, {
        name: row.name,
        description: row.description ?? '',
        barcode: row.barcode,
        category_id: row.category_id,
        supplier_id: row.supplier_id ?? '',
        sale_price: row.sale_price,
        min_stock_alert: row.min_stock_alert ?? 0,
        is_active: !!row.is_active,
    });
    fieldErrors.value = {};
    productModalOpen.value = true;
}

async function saveProduct() {
    saving.value = true;
    fieldErrors.value = {};
    errorMsg.value = '';
    const payload = {
        name: productForm.name,
        description: productForm.description || null,
        barcode: productForm.barcode,
        category_id: Number(productForm.category_id),
        supplier_id: productForm.supplier_id ? Number(productForm.supplier_id) : null,
        sale_price: Number(productForm.sale_price),
        min_stock_alert: Number(productForm.min_stock_alert) || 0,
        is_active: productForm.is_active,
    };
    try {
        if (editingId.value) {
            await axios.put(`/api/products/${editingId.value}`, payload);
        } else {
            await axios.post('/api/products', payload);
        }
        productModalOpen.value = false;
        await fetchProducts(pagination.value.current_page);
    } catch (e) {
        if (e.response?.status === 422) {
            fieldErrors.value = e.response.data.errors ?? {};
        } else {
            errorMsg.value = e.response?.data?.message ?? 'فشل الحفظ.';
        }
    } finally {
        saving.value = false;
    }
}

async function removeProduct(row) {
    if (!confirm(`حذف المنتج «${row.name}»؟ لن يظهر في القوائم النشطة إن وُجدت مبيعات مرتبطة قد تمنع الحذف الكامل.`)) {
        return;
    }
    try {
        await axios.delete(`/api/products/${row.id}`);
        await fetchProducts(pagination.value.current_page);
    } catch (e) {
        alert(e.response?.data?.message ?? 'تعذر الحذف.');
    }
}

async function openBatches(row) {
    batchProduct.value = row;
    batchesModalOpen.value = true;
    await loadBatches();
}

async function loadBatches() {
    if (!batchProduct.value) return;
    batchesLoading.value = true;
    try {
        const { data } = await axios.get(`/api/products/${batchProduct.value.id}/batches`);
        batches.value = data;
        Object.keys(editingRemaining).forEach((k) => delete editingRemaining[k]);
    } finally {
        batchesLoading.value = false;
    }
}

async function addBatch() {
    if (!batchProduct.value) return;
    batchSaving.value = true;
    try {
        const body = {
            batch_code: newBatch.batch_code || null,
            expiration_date: newBatch.expiration_date,
            quantity: Number(newBatch.quantity),
            cost_price: newBatch.cost_price === '' ? null : Number(newBatch.cost_price),
        };
        if (newBatch.remaining_quantity !== '' && newBatch.remaining_quantity != null) {
            body.remaining_quantity = Number(newBatch.remaining_quantity);
        }
        await axios.post(`/api/products/${batchProduct.value.id}/batches`, body);
        Object.assign(newBatch, {
            batch_code: '',
            expiration_date: '',
            quantity: 1,
            remaining_quantity: '',
            cost_price: '',
        });
        await loadBatches();
        await fetchProducts(pagination.value.current_page);
    } catch (e) {
        alert(e.response?.data?.message ?? 'فشل إضافة الدفعة.');
    } finally {
        batchSaving.value = false;
    }
}

async function saveRemaining(batch) {
    const val = editingRemaining[batch.id] !== undefined ? editingRemaining[batch.id] : batch.remaining_quantity;
    if (val === undefined || val === null) return;
    batchSaving.value = true;
    try {
        await axios.patch(`/api/batches/${batch.id}`, { remaining_quantity: Number(val) });
        delete editingRemaining[batch.id];
        await loadBatches();
        await fetchProducts(pagination.value.current_page);
    } catch (e) {
        alert(e.response?.data?.message ?? 'فشل التحديث.');
    } finally {
        batchSaving.value = false;
    }
}

const categoryName = computed(() => {
    const map = Object.fromEntries(categories.value.map((c) => [c.id, c.name]));
    return (id) => map[id] ?? '—';
});

onMounted(async () => {
    try {
        await fetchMeta();
        await fetchProducts(1);
    } catch (e) {
        errorMsg.value = 'تعذر تحميل البيانات.';
    }
});

watch(productModalOpen, (open) => {
    if (!open) fieldErrors.value = {};
});
</script>

<template>
    <AppSidebarLayout>
        <main class="p-6">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-50">المنتجات والدفعات</h1>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">إدارة المنتجات، الباركود، الأسعار، ودفعات المخزون.</p>
                </div>
                <button
                    type="button"
                    class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white"
                    @click="openCreate"
                >
                    منتج جديد
                </button>
            </div>

            <p v-if="errorMsg" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                {{ errorMsg }}
            </p>

            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div v-if="loading" class="p-8 text-center text-zinc-500">جاري التحميل…</div>
                <table v-else class="w-full min-w-[720px] text-right text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/80">
                        <tr>
                            <th class="px-3 py-2 font-semibold">الاسم</th>
                            <th class="px-3 py-2 font-semibold">الباركود</th>
                            <th class="px-3 py-2 font-semibold">الفئة</th>
                            <th class="px-3 py-2 font-semibold">سعر البيع</th>
                            <th class="px-3 py-2 font-semibold">المخزون</th>
                            <th class="px-3 py-2 font-semibold">تنبيه</th>
                            <th class="px-3 py-2 font-semibold">نشط</th>
                            <th class="px-3 py-2 font-semibold">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in pagination.data"
                            :key="row.id"
                            class="border-b border-zinc-100 dark:border-zinc-800"
                        >
                            <td class="px-3 py-2 font-medium">{{ row.name }}</td>
                            <td class="px-3 py-2 font-mono text-xs">{{ row.barcode }}</td>
                            <td class="px-3 py-2">{{ row.category?.name ?? categoryName(row.category_id) }}</td>
                            <td class="px-3 py-2">{{ money(row.sale_price) }}</td>
                            <td class="px-3 py-2">{{ row.total_stock ?? 0 }}</td>
                            <td class="px-3 py-2">{{ row.min_stock_alert }}</td>
                            <td class="px-3 py-2">{{ row.is_active ? 'نعم' : 'لا' }}</td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap gap-1">
                                    <button type="button" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400" @click="openEdit(row)">تعديل</button>
                                    <button type="button" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400" @click="openBatches(row)">الدفعات</button>
                                    <button type="button" class="text-xs text-red-600 hover:underline dark:text-red-400" @click="removeProduct(row)">حذف</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!loading && !pagination.data?.length" class="p-8 text-center text-zinc-500">لا توجد منتجات.</div>
                <div v-if="pagination.last_page > 1" class="flex items-center justify-between border-t border-zinc-200 px-3 py-2 dark:border-zinc-700">
                    <span class="text-xs text-zinc-500">صفحة {{ pagination.current_page }} من {{ pagination.last_page }}</span>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="rounded border border-zinc-300 px-2 py-1 text-xs disabled:opacity-40 dark:border-zinc-600"
                            :disabled="pagination.current_page <= 1"
                            @click="fetchProducts(pagination.current_page - 1)"
                        >
                            السابق
                        </button>
                        <button
                            type="button"
                            class="rounded border border-zinc-300 px-2 py-1 text-xs disabled:opacity-40 dark:border-zinc-600"
                            :disabled="pagination.current_page >= pagination.last_page"
                            @click="fetchProducts(pagination.current_page + 1)"
                        >
                            التالي
                        </button>
                    </div>
                </div>
            </div>

            <!-- نموذج منتج -->
            <div
                v-if="productModalOpen"
                class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center"
                role="dialog"
                aria-modal="true"
                @click.self="productModalOpen = false"
            >
                <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white p-4 shadow-xl dark:bg-zinc-900" @click.stop>
                    <h2 class="mb-3 text-lg font-bold">{{ editingId ? 'تعديل منتج' : 'منتج جديد' }}</h2>
                    <div class="space-y-3 text-sm">
                        <div>
                            <label class="mb-1 block text-zinc-600 dark:text-zinc-400">الاسم</label>
                            <input v-model="productForm.name" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" />
                            <p v-if="fieldErrors.name" class="mt-1 text-xs text-red-600">{{ fieldErrors.name[0] }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-zinc-600 dark:text-zinc-400">الوصف</label>
                            <textarea v-model="productForm.description" rows="2" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" />
                        </div>
                        <div>
                            <label class="mb-1 block text-zinc-600 dark:text-zinc-400">الباركود</label>
                            <input v-model="productForm.barcode" class="w-full rounded border border-zinc-300 px-2 py-1.5 font-mono dark:border-zinc-600 dark:bg-zinc-800" />
                            <p v-if="fieldErrors.barcode" class="mt-1 text-xs text-red-600">{{ fieldErrors.barcode[0] }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="mb-1 block text-zinc-600 dark:text-zinc-400">الفئة</label>
                                <select v-model="productForm.category_id" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800">
                                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-zinc-600 dark:text-zinc-400">المورد</label>
                                <select v-model="productForm.supplier_id" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800">
                                    <option value="">—</option>
                                    <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="mb-1 block text-zinc-600 dark:text-zinc-400">سعر البيع</label>
                                <input v-model="productForm.sale_price" type="number" step="0.01" min="0" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" />
                                <p v-if="fieldErrors.sale_price" class="mt-1 text-xs text-red-600">{{ fieldErrors.sale_price[0] }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-zinc-600 dark:text-zinc-400">حد تنبيه المخزون</label>
                                <input v-model.number="productForm.min_stock_alert" type="number" min="0" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" />
                            </div>
                        </div>
                        <label class="flex items-center gap-2">
                            <input v-model="productForm.is_active" type="checkbox" />
                            <span>منتج نشط</span>
                        </label>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600" @click="productModalOpen = false">إلغاء</button>
                        <button
                            type="button"
                            class="rounded bg-zinc-900 px-3 py-1.5 text-sm text-white disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900"
                            :disabled="saving"
                            @click="saveProduct"
                        >
                            {{ saving ? '…' : 'حفظ' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- دفعات المنتج -->
            <div
                v-if="batchesModalOpen && batchProduct"
                class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center"
                @click.self="batchesModalOpen = false"
            >
                <div class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-xl bg-white p-4 shadow-xl dark:bg-zinc-900" @click.stop>
                    <h2 class="mb-1 text-lg font-bold">دفعات: {{ batchProduct.name }}</h2>
                    <p class="mb-3 text-xs text-zinc-500">تعديل «المتبقي» للجرد؛ إضافة دفعة ترفع المخزون.</p>

                    <div class="mb-4 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        <h3 class="mb-2 text-sm font-semibold">إضافة دفعة</h3>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                            <input v-model="newBatch.batch_code" placeholder="رمز الدفعة" class="rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                            <input v-model="newBatch.expiration_date" type="date" class="rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                            <input v-model.number="newBatch.quantity" type="number" min="1" placeholder="الكمية" class="rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                            <input v-model="newBatch.remaining_quantity" type="number" min="0" placeholder="متبقي (اختياري)" class="rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                            <input v-model="newBatch.cost_price" type="number" step="0.01" min="0" placeholder="تكلفة" class="rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                        </div>
                        <button
                            type="button"
                            class="mt-2 rounded bg-zinc-900 px-3 py-1.5 text-sm text-white disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900"
                            :disabled="batchSaving || !newBatch.expiration_date"
                            @click="addBatch"
                        >
                            إضافة
                        </button>
                    </div>

                    <div v-if="batchesLoading" class="py-6 text-center text-zinc-500">جاري التحميل…</div>
                    <table v-else class="w-full text-right text-xs sm:text-sm">
                        <thead class="border-b border-zinc-200 dark:border-zinc-700">
                            <tr>
                                <th class="px-2 py-1">رمز</th>
                                <th class="px-2 py-1">انتهاء</th>
                                <th class="px-2 py-1">أصلي</th>
                                <th class="px-2 py-1">متبقي</th>
                                <th class="px-2 py-1">تكلفة</th>
                                <th class="px-2 py-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="b in batches" :key="b.id" class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="px-2 py-1 font-mono">{{ b.batch_code || '—' }}</td>
                                <td class="px-2 py-1">{{ fmtDate(b.expiration_date) }}</td>
                                <td class="px-2 py-1">{{ b.quantity }}</td>
                                <td class="px-2 py-1">
                                    <input
                                        v-model.number="editingRemaining[b.id]"
                                        type="number"
                                        min="0"
                                        class="w-20 rounded border border-zinc-300 px-1 dark:border-zinc-600 dark:bg-zinc-800"
                                        :placeholder="String(b.remaining_quantity)"
                                        @focus="editingRemaining[b.id] = b.remaining_quantity"
                                    />
                                </td>
                                <td class="px-2 py-1">{{ b.cost_price != null ? money(b.cost_price) : '—' }}</td>
                                <td class="px-2 py-1">
                                    <button type="button" class="text-indigo-600 hover:underline dark:text-indigo-400" :disabled="batchSaving" @click="saveRemaining(b)">حفظ المتبقي</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="mt-4 flex justify-end">
                        <button type="button" class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600" @click="batchesModalOpen = false">إغلاق</button>
                    </div>
                </div>
            </div>
        </main>
    </AppSidebarLayout>
</template>
