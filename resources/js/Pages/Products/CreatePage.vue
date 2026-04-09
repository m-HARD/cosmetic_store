<script setup>
import axios from 'axios';
import { onMounted, reactive, ref } from 'vue';
import AppSidebarLayout from '../../Layouts/AppSidebarLayout.vue';

const saving = ref(false);
const quickSaving = ref(false);
const errorMsg = ref('');
const fieldErrors = ref({});

const categories = ref([]);
const suppliers = ref([]);

const form = reactive({
    name: '',
    description: '',
    barcode: '',
    category_id: '',
    supplier_id: '',
    sale_price: '',
    min_stock_alert: 0,
    is_active: true,
});
const imageFile = ref(null);

const quickCategoryOpen = ref(false);
const quickSupplierOpen = ref(false);
const quickCategoryForm = reactive({ name: '', description: '', is_active: true });
const quickSupplierForm = reactive({ name: '', phone: '', address: '', notes: '' });

async function fetchMeta() {
    const [catRes, supRes] = await Promise.all([
        axios.get('/api/categories'),
        axios.get('/api/suppliers/options'),
    ]);
    categories.value = catRes.data;
    suppliers.value = supRes.data;
    if (!form.category_id && categories.value.length) form.category_id = categories.value[0].id;
}

async function saveProduct() {
    saving.value = true;
    fieldErrors.value = {};
    errorMsg.value = '';
    try {
        const body = new FormData();
        body.append('name', form.name);
        body.append('description', form.description || '');
        body.append('barcode', form.barcode);
        body.append('category_id', String(Number(form.category_id)));
        if (form.supplier_id) body.append('supplier_id', String(Number(form.supplier_id)));
        body.append('sale_price', String(Number(form.sale_price)));
        body.append('min_stock_alert', String(Number(form.min_stock_alert) || 0));
        body.append('is_active', form.is_active ? '1' : '0');
        if (imageFile.value) body.append('image', imageFile.value);

        const { data } = await axios.post('/api/products', body, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        window.location.href = `/products/${data.id}`;
    } catch (e) {
        if (e.response?.status === 422) fieldErrors.value = e.response?.data?.errors ?? {};
        else errorMsg.value = e.response?.data?.message ?? 'تعذر إضافة المنتج.';
    } finally {
        saving.value = false;
    }
}

async function createQuickCategory() {
    quickSaving.value = true;
    try {
        await axios.post('/api/categories', {
            name: quickCategoryForm.name,
            description: quickCategoryForm.description || null,
            is_active: !!quickCategoryForm.is_active,
        });
        quickCategoryOpen.value = false;
        Object.assign(quickCategoryForm, { name: '', description: '', is_active: true });
        await fetchMeta();
    } catch (e) {
        errorMsg.value = e.response?.data?.message ?? 'تعذر إضافة الفئة.';
    } finally {
        quickSaving.value = false;
    }
}

async function createQuickSupplier() {
    quickSaving.value = true;
    try {
        await axios.post('/api/suppliers', {
            name: quickSupplierForm.name,
            phone: quickSupplierForm.phone || null,
            address: quickSupplierForm.address || null,
            notes: quickSupplierForm.notes || null,
        });
        quickSupplierOpen.value = false;
        Object.assign(quickSupplierForm, { name: '', phone: '', address: '', notes: '' });
        await fetchMeta();
    } catch (e) {
        errorMsg.value = e.response?.data?.message ?? 'تعذر إضافة المورد.';
    } finally {
        quickSaving.value = false;
    }
}

onMounted(fetchMeta);

function onImageChange(event) {
    imageFile.value = event.target.files?.[0] ?? null;
}
</script>

<template>
    <AppSidebarLayout>
        <main class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-50">إضافة منتج جديد</h1>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">أدخل بيانات المنتج ثم احفظ. يمكنك إضافة فئة أو مورد بشكل سريع.</p>
                </div>
                <a href="/products" class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600">رجوع</a>
            </div>

            <p v-if="errorMsg" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">{{ errorMsg }}</p>

            <section class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="space-y-3 text-sm">
                    <div>
                        <label class="mb-1 block text-zinc-600 dark:text-zinc-400">الاسم</label>
                        <input v-model="form.name" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" />
                        <p v-if="fieldErrors.name" class="mt-1 text-xs text-red-600">{{ fieldErrors.name[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-zinc-600 dark:text-zinc-400">الوصف</label>
                        <textarea v-model="form.description" rows="2" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" />
                    </div>
                    <div>
                        <label class="mb-1 block text-zinc-600 dark:text-zinc-400">الباركود</label>
                        <input v-model="form.barcode" class="w-full rounded border border-zinc-300 px-2 py-1.5 font-mono dark:border-zinc-600 dark:bg-zinc-800" />
                        <p v-if="fieldErrors.barcode" class="mt-1 text-xs text-red-600">{{ fieldErrors.barcode[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-zinc-600 dark:text-zinc-400">صورة المنتج</label>
                        <input type="file" accept="image/*" class="w-full rounded border border-zinc-300 px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800" @change="onImageChange" />
                        <p v-if="fieldErrors.image" class="mt-1 text-xs text-red-600">{{ fieldErrors.image[0] }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <div class="mb-1 flex items-center justify-between">
                                <label class="text-zinc-600 dark:text-zinc-400">الفئة</label>
                                <button type="button" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400" @click="quickCategoryOpen = true">+ فئة سريعة</button>
                            </div>
                            <select v-model="form.category_id" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800">
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <div class="mb-1 flex items-center justify-between">
                                <label class="text-zinc-600 dark:text-zinc-400">المورد</label>
                                <button type="button" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400" @click="quickSupplierOpen = true">+ مورد سريع</button>
                            </div>
                            <select v-model="form.supplier_id" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800">
                                <option value="">—</option>
                                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="mb-1 block text-zinc-600 dark:text-zinc-400">سعر البيع</label>
                            <input v-model="form.sale_price" type="number" step="0.01" min="0" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" />
                            <p v-if="fieldErrors.sale_price" class="mt-1 text-xs text-red-600">{{ fieldErrors.sale_price[0] }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-zinc-600 dark:text-zinc-400">حد تنبيه المخزون</label>
                            <input v-model.number="form.min_stock_alert" type="number" min="0" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" />
                        </div>
                    </div>
                    <label class="flex items-center gap-2"><input v-model="form.is_active" type="checkbox" /><span>منتج نشط</span></label>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <a href="/products" class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600">إلغاء</a>
                    <button class="rounded bg-zinc-900 px-3 py-1.5 text-sm text-white disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900" :disabled="saving" @click="saveProduct">{{ saving ? '…' : 'حفظ المنتج' }}</button>
                </div>
            </section>

            <div v-if="quickCategoryOpen" class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center" @click.self="quickCategoryOpen = false">
                <div class="w-full max-w-md rounded-xl bg-white p-4 shadow-xl dark:bg-zinc-900" @click.stop>
                    <h2 class="mb-3 text-lg font-bold">إضافة فئة سريعة</h2>
                    <div class="space-y-2">
                        <input v-model="quickCategoryForm.name" placeholder="اسم الفئة" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" />
                        <textarea v-model="quickCategoryForm.description" rows="2" placeholder="وصف" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" />
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600" @click="quickCategoryOpen = false">إلغاء</button>
                        <button class="rounded bg-zinc-900 px-3 py-1.5 text-sm text-white disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900" :disabled="quickSaving || !quickCategoryForm.name" @click="createQuickCategory">إضافة</button>
                    </div>
                </div>
            </div>

            <div v-if="quickSupplierOpen" class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center" @click.self="quickSupplierOpen = false">
                <div class="w-full max-w-md rounded-xl bg-white p-4 shadow-xl dark:bg-zinc-900" @click.stop>
                    <h2 class="mb-3 text-lg font-bold">إضافة مورد سريع</h2>
                    <div class="space-y-2">
                        <input v-model="quickSupplierForm.name" placeholder="اسم المورد" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" />
                        <input v-model="quickSupplierForm.phone" placeholder="الهاتف" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" />
                        <input v-model="quickSupplierForm.address" placeholder="العنوان" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" />
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600" @click="quickSupplierOpen = false">إلغاء</button>
                        <button class="rounded bg-zinc-900 px-3 py-1.5 text-sm text-white disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900" :disabled="quickSaving || !quickSupplierForm.name" @click="createQuickSupplier">إضافة</button>
                    </div>
                </div>
            </div>
        </main>
    </AppSidebarLayout>
</template>
