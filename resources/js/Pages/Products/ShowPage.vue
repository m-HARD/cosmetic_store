<script setup>
import axios from 'axios';
import { onMounted, reactive, ref } from 'vue';
import AppSidebarLayout from '../../Layouts/AppSidebarLayout.vue';

const props = defineProps({
    productId: { type: Number, required: true },
});

const loading = ref(true);
const saving = ref(false);
const batchSaving = ref(false);
const deleting = ref(false);
const errorMsg = ref('');
const fieldErrors = ref({});

const product = ref(null);
const batches = ref([]);
const categories = ref([]);
const suppliers = ref([]);

const editModalOpen = ref(false);
const addBatchModalOpen = ref(false);
const editBatchModalOpen = ref(false);
const editForm = reactive({
    name: '',
    description: '',
    barcode: '',
    category_id: '',
    supplier_id: '',
    sale_price: '',
    min_stock_alert: 0,
    is_active: true,
    remove_image: false,
});
const editImageFile = ref(null);

const newBatch = reactive({
    batch_code: '',
    expiration_date: '',
    quantity: 1,
    remaining_quantity: '',
    cost_price: '',
});
const batchFieldErrors = ref({});
const batchEditForm = reactive({
    id: null,
    batch_code: '',
    expiration_date: '',
    quantity: 1,
    remaining_quantity: 0,
    cost_price: '',
});

const money = (n) => (Number(n) || 0).toFixed(2);
const fmtDate = (d) => (d ? String(d).slice(0, 10) : '—');
const productImageUrl = () => {
    const img = product.value?.image;
    if (!img) return null;
    if (String(img).startsWith('http://') || String(img).startsWith('https://')) return img;
    return `/storage/${img}`;
};

async function loadData() {
    loading.value = true;
    errorMsg.value = '';
    try {
        const [productRes, batchesRes, categoriesRes, suppliersRes] = await Promise.all([
            axios.get(`/api/products/${props.productId}`),
            axios.get(`/api/products/${props.productId}/batches`),
            axios.get('/api/categories'),
            axios.get('/api/suppliers/options'),
        ]);
        product.value = productRes.data;
        batches.value = batchesRes.data;
        categories.value = categoriesRes.data;
        suppliers.value = suppliersRes.data;
    } catch (e) {
        errorMsg.value = e.response?.data?.message ?? 'تعذر تحميل تفاصيل المنتج.';
    } finally {
        loading.value = false;
    }
}

function openEditModal() {
    if (!product.value) return;
    Object.assign(editForm, {
        name: product.value.name,
        description: product.value.description ?? '',
        barcode: product.value.barcode,
        category_id: product.value.category_id,
        supplier_id: product.value.supplier_id ?? '',
        sale_price: product.value.sale_price,
        min_stock_alert: product.value.min_stock_alert ?? 0,
        is_active: !!product.value.is_active,
        remove_image: false,
    });
    editImageFile.value = null;
    fieldErrors.value = {};
    editModalOpen.value = true;
}

async function saveProduct() {
    saving.value = true;
    fieldErrors.value = {};
    errorMsg.value = '';
    try {
        const body = new FormData();
        body.append('_method', 'PUT');
        body.append('name', editForm.name);
        body.append('description', editForm.description || '');
        body.append('barcode', editForm.barcode);
        body.append('category_id', String(Number(editForm.category_id)));
        if (editForm.supplier_id) body.append('supplier_id', String(Number(editForm.supplier_id)));
        body.append('sale_price', String(Number(editForm.sale_price)));
        body.append('min_stock_alert', String(Number(editForm.min_stock_alert) || 0));
        body.append('is_active', editForm.is_active ? '1' : '0');
        body.append('remove_image', editForm.remove_image ? '1' : '0');
        if (editImageFile.value) body.append('image', editImageFile.value);

        await axios.post(`/api/products/${props.productId}`, body, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        editModalOpen.value = false;
        await loadData();
    } catch (e) {
        if (e.response?.status === 422) fieldErrors.value = e.response?.data?.errors ?? {};
        else errorMsg.value = e.response?.data?.message ?? 'فشل حفظ التعديل.';
    } finally {
        saving.value = false;
    }
}

function onEditImageChange(event) {
    editImageFile.value = event.target.files?.[0] ?? null;
}

async function removeProduct() {
    if (!product.value) return;
    if (!confirm(`تأكيد حذف المنتج «${product.value.name}»؟`)) return;
    deleting.value = true;
    try {
        await axios.delete(`/api/products/${props.productId}`);
        window.location.href = '/products';
    } catch (e) {
        errorMsg.value = e.response?.data?.message ?? 'تعذر حذف المنتج.';
    } finally {
        deleting.value = false;
    }
}

async function addBatch() {
    batchSaving.value = true;
    batchFieldErrors.value = {};
    try {
        const payload = {
            batch_code: newBatch.batch_code || null,
            expiration_date: newBatch.expiration_date,
            quantity: Number(newBatch.quantity),
            cost_price: newBatch.cost_price === '' ? null : Number(newBatch.cost_price),
        };
        if (newBatch.remaining_quantity !== '' && newBatch.remaining_quantity != null) {
            payload.remaining_quantity = Number(newBatch.remaining_quantity);
        }
        await axios.post(`/api/products/${props.productId}/batches`, payload);
        Object.assign(newBatch, { batch_code: '', expiration_date: '', quantity: 1, remaining_quantity: '', cost_price: '' });
        addBatchModalOpen.value = false;
        await loadData();
    } catch (e) {
        if (e.response?.status === 422) batchFieldErrors.value = e.response?.data?.errors ?? {};
        else errorMsg.value = e.response?.data?.message ?? 'تعذر إضافة الدفعة.';
    } finally {
        batchSaving.value = false;
    }
}

function openEditBatchModal(batch) {
    Object.assign(batchEditForm, {
        id: batch.id,
        batch_code: batch.batch_code ?? '',
        expiration_date: fmtDate(batch.expiration_date),
        quantity: Number(batch.quantity ?? 1),
        remaining_quantity: Number(batch.remaining_quantity ?? 0),
        cost_price: batch.cost_price ?? '',
    });
    batchFieldErrors.value = {};
    editBatchModalOpen.value = true;
}

async function updateBatch() {
    if (!batchEditForm.id) return;
    batchSaving.value = true;
    batchFieldErrors.value = {};
    try {
        await axios.patch(`/api/batches/${batchEditForm.id}`, {
            batch_code: batchEditForm.batch_code || null,
            expiration_date: batchEditForm.expiration_date,
            quantity: Number(batchEditForm.quantity),
            remaining_quantity: Number(batchEditForm.remaining_quantity),
            cost_price: batchEditForm.cost_price === '' ? null : Number(batchEditForm.cost_price),
        });
        editBatchModalOpen.value = false;
        await loadData();
    } catch (e) {
        if (e.response?.status === 422) batchFieldErrors.value = e.response?.data?.errors ?? {};
        else errorMsg.value = e.response?.data?.message ?? 'تعذر تحديث الدفعة.';
    } finally {
        batchSaving.value = false;
    }
}

onMounted(loadData);
</script>

<template>
    <AppSidebarLayout>
        <main class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-50">تفاصيل المنتج</h1>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">معلومات المنتج والدفعات مع التعديل والحذف.</p>
                </div>
                <a href="/products" class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600">رجوع</a>
            </div>

            <p v-if="errorMsg" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">{{ errorMsg }}</p>

            <div v-if="loading" class="rounded-xl border border-zinc-200 bg-white p-8 text-center text-zinc-500 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">جاري التحميل…</div>
            <template v-else-if="product">
                <section class="mb-5 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold">بطاقة المنتج</h2>
                        <div class="flex gap-2">
                            <button class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600" @click="openEditModal">تعديل</button>
                            <button class="rounded border border-indigo-300 px-3 py-1.5 text-sm text-indigo-700 dark:border-indigo-700 dark:text-indigo-300" @click="addBatchModalOpen = true">إضافة دفعة</button>
                            <button class="rounded border border-red-300 px-3 py-1.5 text-sm text-red-700 dark:border-red-700 dark:text-red-300 disabled:opacity-60" :disabled="deleting" @click="removeProduct">حذف</button>
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700 sm:row-span-2">
                            <p class="mb-2 text-zinc-500">صورة المنتج</p>
                            <img v-if="productImageUrl()" :src="productImageUrl()" alt="صورة المنتج" class="h-40 w-full rounded object-cover" />
                            <div v-else class="flex h-40 items-center justify-center rounded bg-zinc-100 text-xs text-zinc-500 dark:bg-zinc-800">لا توجد صورة</div>
                        </div>
                        <div class="rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700"><p class="text-zinc-500">الاسم</p><p class="mt-1 font-semibold">{{ product.name }}</p></div>
                        <div class="rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700"><p class="text-zinc-500">الباركود</p><p class="mt-1 font-semibold font-mono">{{ product.barcode }}</p></div>
                        <div class="rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700"><p class="text-zinc-500">المخزون</p><p class="mt-1 font-semibold">{{ product.total_stock ?? 0 }}</p></div>
                        <div class="rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                            <p class="text-zinc-500">الفئة</p>
                            <p class="mt-1 font-semibold">
                                <a
                                    v-if="product.category"
                                    :href="`/categories/${product.category_id}`"
                                    class="text-indigo-600 hover:underline dark:text-indigo-400"
                                >
                                    {{ product.category.name }}
                                </a>
                                <span v-else>—</span>
                            </p>
                        </div>
                        <div class="rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                            <p class="text-zinc-500">المورد</p>
                            <p class="mt-1 font-semibold">
                                <a
                                    v-if="product.supplier"
                                    :href="`/suppliers/${product.supplier_id}`"
                                    class="text-indigo-600 hover:underline dark:text-indigo-400"
                                >
                                    {{ product.supplier.name }}
                                </a>
                                <span v-else>—</span>
                            </p>
                        </div>
                        <div class="rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700"><p class="text-zinc-500">سعر البيع</p><p class="mt-1 font-semibold">{{ money(product.sale_price) }}</p></div>
                    </div>
                </section>

                <section class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="mb-2 text-sm font-semibold">قائمة الدفعات</h2>
                    <table class="w-full text-right text-xs sm:text-sm">
                        <thead class="border-b border-zinc-200 dark:border-zinc-700"><tr><th class="px-2 py-1">رمز</th><th class="px-2 py-1">انتهاء</th><th class="px-2 py-1">أصلي</th><th class="px-2 py-1">متبقي</th><th class="px-2 py-1">تكلفة</th><th class="px-2 py-1"></th></tr></thead>
                        <tbody>
                            <tr v-for="b in batches" :key="b.id" class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="px-2 py-1 font-mono">{{ b.batch_code || '—' }}</td>
                                <td class="px-2 py-1">{{ fmtDate(b.expiration_date) }}</td>
                                <td class="px-2 py-1">{{ b.quantity }}</td>
                                <td class="px-2 py-1">{{ b.remaining_quantity }}</td>
                                <td class="px-2 py-1">{{ b.cost_price != null ? money(b.cost_price) : '—' }}</td>
                                <td class="px-2 py-1"><button class="text-indigo-600 hover:underline dark:text-indigo-400" :disabled="batchSaving" @click="openEditBatchModal(b)">تعديل</button></td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </template>

            <div v-if="addBatchModalOpen" class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center" @click.self="addBatchModalOpen = false">
                <div class="w-full max-w-2xl rounded-xl bg-white p-4 shadow-xl dark:bg-zinc-900" @click.stop>
                    <h2 class="mb-3 text-lg font-bold">إضافة دفعة</h2>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                        <div>
                            <label class="mb-1 block text-xs text-zinc-500">رمز الدفعة</label>
                            <input v-model="newBatch.batch_code" placeholder="اختياري" class="w-full rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-zinc-500">تاريخ الانتهاء</label>
                            <input v-model="newBatch.expiration_date" type="date" class="w-full rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-zinc-500">الكمية الأصلية</label>
                            <input v-model.number="newBatch.quantity" type="number" min="1" class="w-full rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-zinc-500">الكمية المتبقية</label>
                            <input v-model="newBatch.remaining_quantity" type="number" min="0" placeholder="تلقائي إذا فارغ" class="w-full rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-zinc-500">سعر التكلفة</label>
                            <input v-model="newBatch.cost_price" type="number" step="0.01" min="0" class="w-full rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                        </div>
                    </div>
                    <p v-if="batchFieldErrors.expiration_date" class="mt-1 text-xs text-red-600">{{ batchFieldErrors.expiration_date[0] }}</p>
                    <div class="mt-4 flex justify-end gap-2">
                        <button class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600" @click="addBatchModalOpen = false">إلغاء</button>
                        <button class="rounded bg-zinc-900 px-3 py-1.5 text-sm text-white disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900" :disabled="batchSaving || !newBatch.expiration_date" @click="addBatch">{{ batchSaving ? '…' : 'إضافة' }}</button>
                    </div>
                </div>
            </div>

            <div v-if="editBatchModalOpen" class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center" @click.self="editBatchModalOpen = false">
                <div class="w-full max-w-2xl rounded-xl bg-white p-4 shadow-xl dark:bg-zinc-900" @click.stop>
                    <h2 class="mb-3 text-lg font-bold">تعديل الدفعة</h2>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                        <div>
                            <label class="mb-1 block text-xs text-zinc-500">رمز الدفعة</label>
                            <input v-model="batchEditForm.batch_code" placeholder="اختياري" class="w-full rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-zinc-500">تاريخ الانتهاء</label>
                            <input v-model="batchEditForm.expiration_date" type="date" class="w-full rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-zinc-500">الكمية الأصلية</label>
                            <input v-model.number="batchEditForm.quantity" type="number" min="1" class="w-full rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-zinc-500">الكمية المتبقية</label>
                            <input v-model.number="batchEditForm.remaining_quantity" type="number" min="0" class="w-full rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-zinc-500">سعر التكلفة</label>
                            <input v-model="batchEditForm.cost_price" type="number" step="0.01" min="0" class="w-full rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                        </div>
                    </div>
                    <p v-if="batchFieldErrors.quantity" class="mt-1 text-xs text-red-600">{{ batchFieldErrors.quantity[0] }}</p>
                    <div class="mt-4 flex justify-end gap-2">
                        <button class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600" @click="editBatchModalOpen = false">إلغاء</button>
                        <button class="rounded bg-zinc-900 px-3 py-1.5 text-sm text-white disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900" :disabled="batchSaving" @click="updateBatch">{{ batchSaving ? '…' : 'حفظ التعديل' }}</button>
                    </div>
                </div>
            </div>

            <div v-if="editModalOpen" class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center" @click.self="editModalOpen = false">
                <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white p-4 shadow-xl dark:bg-zinc-900" @click.stop>
                    <h2 class="mb-3 text-lg font-bold">تعديل المنتج</h2>
                    <div class="space-y-3 text-sm">
                        <div><label class="mb-1 block text-zinc-600 dark:text-zinc-400">الاسم</label><input v-model="editForm.name" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" /><p v-if="fieldErrors.name" class="mt-1 text-xs text-red-600">{{ fieldErrors.name[0] }}</p></div>
                        <div><label class="mb-1 block text-zinc-600 dark:text-zinc-400">الوصف</label><textarea v-model="editForm.description" rows="2" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" /></div>
                        <div><label class="mb-1 block text-zinc-600 dark:text-zinc-400">الباركود</label><input v-model="editForm.barcode" class="w-full rounded border border-zinc-300 px-2 py-1.5 font-mono dark:border-zinc-600 dark:bg-zinc-800" /><p v-if="fieldErrors.barcode" class="mt-1 text-xs text-red-600">{{ fieldErrors.barcode[0] }}</p></div>
                        <div>
                            <label class="mb-1 block text-zinc-600 dark:text-zinc-400">صورة المنتج</label>
                            <input type="file" accept="image/*" class="w-full rounded border border-zinc-300 px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800" @change="onEditImageChange" />
                            <label class="mt-2 flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <input v-model="editForm.remove_image" type="checkbox" />
                                <span>إزالة الصورة الحالية</span>
                            </label>
                            <p v-if="fieldErrors.image" class="mt-1 text-xs text-red-600">{{ fieldErrors.image[0] }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div><label class="mb-1 block text-zinc-600 dark:text-zinc-400">الفئة</label><select v-model="editForm.category_id" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800"><option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option></select></div>
                            <div><label class="mb-1 block text-zinc-600 dark:text-zinc-400">المورد</label><select v-model="editForm.supplier_id" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800"><option value="">—</option><option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option></select></div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div><label class="mb-1 block text-zinc-600 dark:text-zinc-400">سعر البيع</label><input v-model="editForm.sale_price" type="number" step="0.01" min="0" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" /><p v-if="fieldErrors.sale_price" class="mt-1 text-xs text-red-600">{{ fieldErrors.sale_price[0] }}</p></div>
                            <div><label class="mb-1 block text-zinc-600 dark:text-zinc-400">حد تنبيه المخزون</label><input v-model.number="editForm.min_stock_alert" type="number" min="0" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" /></div>
                        </div>
                        <label class="flex items-center gap-2"><input v-model="editForm.is_active" type="checkbox" /><span>منتج نشط</span></label>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600" @click="editModalOpen = false">إلغاء</button>
                        <button class="rounded bg-zinc-900 px-3 py-1.5 text-sm text-white disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900" :disabled="saving" @click="saveProduct">{{ saving ? '…' : 'حفظ' }}</button>
                    </div>
                </div>
            </div>
        </main>
    </AppSidebarLayout>
</template>
