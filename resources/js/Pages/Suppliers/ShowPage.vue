<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref } from 'vue';
import AppSidebarLayout from '../../Layouts/AppSidebarLayout.vue';

const props = defineProps({
    supplierId: {
        type: Number,
        required: true,
    },
});

const loading = ref(true);
const saving = ref(false);
const deleting = ref(false);
const errorMsg = ref('');
const successMsg = ref('');
const fieldErrors = ref({});

const details = ref(null);
const supplierOptions = ref([]);

const editForm = reactive({
    name: '',
    phone: '',
    address: '',
    notes: '',
});
const editModalOpen = ref(false);

const deleteModalOpen = ref(false);
const deleteForm = reactive({
    transfer_supplier_id: '',
    password: '',
});

const productsCount = computed(() => Number(details.value?.products_count ?? 0));
const hasProducts = computed(() => productsCount.value > 0);
const requirePasswordForDelete = computed(() => hasProducts.value && !deleteForm.transfer_supplier_id);

const money = (n) => (Number(n) || 0).toFixed(2);

async function loadDetails() {
    loading.value = true;
    errorMsg.value = '';
    try {
        const [{ data }, { data: options }] = await Promise.all([
            axios.get(`/api/suppliers/${props.supplierId}`),
            axios.get('/api/suppliers/options'),
        ]);
        details.value = data;
        supplierOptions.value = (options ?? []).filter((s) => s.id !== props.supplierId);
        Object.assign(editForm, {
            name: data.supplier.name ?? '',
            phone: data.supplier.phone ?? '',
            address: data.supplier.address ?? '',
            notes: data.supplier.notes ?? '',
        });
    } catch (e) {
        errorMsg.value = e.response?.data?.message ?? 'تعذر تحميل بيانات المورد.';
    } finally {
        loading.value = false;
    }
}

async function saveSupplier() {
    saving.value = true;
    fieldErrors.value = {};
    errorMsg.value = '';
    successMsg.value = '';
    try {
        await axios.put(`/api/suppliers/${props.supplierId}`, {
            name: editForm.name,
            phone: editForm.phone || null,
            address: editForm.address || null,
            notes: editForm.notes || null,
        });
        successMsg.value = 'تم تحديث بيانات المورد.';
        editModalOpen.value = false;
        await loadDetails();
    } catch (e) {
        if (e.response?.status === 422) {
            fieldErrors.value = e.response?.data?.errors ?? {};
        } else {
            errorMsg.value = e.response?.data?.message ?? 'تعذر حفظ التعديلات.';
        }
    } finally {
        saving.value = false;
    }
}

function openEditModal() {
    fieldErrors.value = {};
    editModalOpen.value = true;
}

function openDeleteModal() {
    deleteForm.transfer_supplier_id = '';
    deleteForm.password = '';
    fieldErrors.value = {};
    deleteModalOpen.value = true;
}

async function deleteSupplier() {
    deleting.value = true;
    fieldErrors.value = {};
    errorMsg.value = '';
    try {
        await axios.delete(`/api/suppliers/${props.supplierId}`, {
            data: {
                transfer_supplier_id: deleteForm.transfer_supplier_id || null,
                password: requirePasswordForDelete.value ? deleteForm.password : null,
            },
        });
        window.location.href = '/suppliers';
    } catch (e) {
        if (e.response?.status === 422) {
            fieldErrors.value = e.response?.data?.errors ?? {};
        } else {
            errorMsg.value = e.response?.data?.message ?? 'تعذر حذف المورد.';
        }
    } finally {
        deleting.value = false;
    }
}

onMounted(loadDetails);
</script>

<template>
    <AppSidebarLayout>
        <main class="p-6">
            <div class="mb-4 flex items-center justify-between gap-2">
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-50">تفاصيل المورد</h1>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">عرض بطاقة المورد، تحديث البيانات، والتحكم في الحذف.</p>
                </div>
                <a href="/suppliers" class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600">رجوع</a>
            </div>

            <p v-if="errorMsg" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                {{ errorMsg }}
            </p>
            <p v-if="successMsg" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
                {{ successMsg }}
            </p>

            <div v-if="loading" class="rounded-xl border border-zinc-200 bg-white p-8 text-center text-zinc-500 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">جاري التحميل…</div>

            <template v-else-if="details">
                <section class="mb-5 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h2 class="text-sm font-semibold">بطاقة المورد</h2>
                        <div class="flex gap-2">
                            <button type="button" class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600" @click="openEditModal">تعديل</button>
                            <button type="button" class="rounded border border-red-300 px-3 py-1.5 text-sm text-red-700 dark:border-red-700 dark:text-red-300" @click="openDeleteModal">حذف المورد</button>
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                            <p class="text-zinc-500">عدد المنتجات</p>
                            <p class="mt-1 text-lg font-semibold">{{ details.products_count }}</p>
                        </div>
                        <div class="rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                            <p class="text-zinc-500">إجمالي الكميات المتاحة</p>
                            <p class="mt-1 text-lg font-semibold">{{ details.products_stock_total }}</p>
                        </div>
                        <div class="rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                            <p class="text-zinc-500">رقم المورد</p>
                            <p class="mt-1 text-lg font-semibold">#{{ details.supplier.id }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="mb-3 text-sm font-semibold">منتجات المورد</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-right text-sm">
                            <thead class="border-b border-zinc-200 dark:border-zinc-700">
                                <tr>
                                    <th class="px-2 py-1">المنتج</th>
                                    <th class="px-2 py-1">الباركود</th>
                                    <th class="px-2 py-1">الفئة</th>
                                    <th class="px-2 py-1">سعر البيع</th>
                                    <th class="px-2 py-1">المخزون</th>
                                    <th class="px-2 py-1">نشط</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="product in details.products" :key="product.id" class="border-b border-zinc-100 dark:border-zinc-800">
                                    <td class="px-2 py-1 font-medium">{{ product.name }}</td>
                                    <td class="px-2 py-1 font-mono text-xs">{{ product.barcode }}</td>
                                    <td class="px-2 py-1">{{ product.category?.name || '—' }}</td>
                                    <td class="px-2 py-1">{{ money(product.sale_price) }}</td>
                                    <td class="px-2 py-1">{{ product.total_stock ?? 0 }}</td>
                                    <td class="px-2 py-1">{{ product.is_active ? 'نعم' : 'لا' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-if="!details.products?.length" class="py-4 text-center text-sm text-zinc-500">لا توجد منتجات لهذا المورد.</p>
                </section>
            </template>

            <div
                v-if="editModalOpen"
                class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center"
                @click.self="editModalOpen = false"
            >
                <div class="w-full max-w-lg rounded-xl bg-white p-4 shadow-xl dark:bg-zinc-900" @click.stop>
                    <h3 class="mb-3 text-lg font-bold">تعديل بيانات المورد</h3>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs text-zinc-500">الاسم</label>
                            <input v-model="editForm.name" class="w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                            <p v-if="fieldErrors.name" class="mt-1 text-xs text-red-600">{{ fieldErrors.name[0] }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-zinc-500">الهاتف</label>
                            <input v-model="editForm.phone" class="w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-zinc-500">العنوان</label>
                            <input v-model="editForm.address" class="w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-zinc-500">ملاحظات</label>
                            <input v-model="editForm.notes" class="w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600" @click="editModalOpen = false">إلغاء</button>
                        <button
                            type="button"
                            class="rounded bg-zinc-900 px-3 py-1.5 text-sm text-white disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900"
                            :disabled="saving"
                            @click="saveSupplier"
                        >
                            {{ saving ? '…' : 'حفظ التعديلات' }}
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="deleteModalOpen"
                class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center"
                @click.self="deleteModalOpen = false"
            >
                <div class="w-full max-w-lg rounded-xl bg-white p-4 shadow-xl dark:bg-zinc-900" @click.stop>
                    <h3 class="mb-2 text-lg font-bold text-red-700 dark:text-red-400">تأكيد حذف المورد</h3>
                    <template v-if="hasProducts">
                        <p class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                            هذا المورد لديه منتجات. إذا أكدت الحذف بدون نقل المنتجات، سيتم حذف المورد وجميع منتجاته.
                            إذا أردت الاحتفاظ بالمنتجات، انقلها لمورد آخر أو عدّل بيانات المورد بدل الحذف.
                        </p>
                        <div class="mb-3">
                            <label class="mb-1 block text-xs text-zinc-500">نقل المنتجات إلى مورد آخر (اختياري)</label>
                            <select v-model="deleteForm.transfer_supplier_id" class="w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                                <option value="">— حذف المنتجات مع المورد —</option>
                                <option v-for="row in supplierOptions" :key="row.id" :value="row.id">{{ row.name }}</option>
                            </select>
                            <p v-if="fieldErrors.transfer_supplier_id" class="mt-1 text-xs text-red-600">{{ fieldErrors.transfer_supplier_id[0] }}</p>
                        </div>
                        <div v-if="requirePasswordForDelete" class="mb-3">
                            <label class="mb-1 block text-xs text-zinc-500">كلمة المرور للتأكيد</label>
                            <input v-model="deleteForm.password" type="password" class="w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                            <p v-if="fieldErrors.password" class="mt-1 text-xs text-red-600">{{ fieldErrors.password[0] }}</p>
                        </div>
                    </template>
                    <template v-else>
                        <p class="mb-3 text-sm text-zinc-600 dark:text-zinc-300">هذا المورد لا يحتوي على منتجات. هل تريد حذفه نهائيًا؟</p>
                    </template>

                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600" @click="deleteModalOpen = false">إلغاء</button>
                        <button
                            type="button"
                            class="rounded bg-red-600 px-3 py-1.5 text-sm text-white disabled:opacity-50"
                            :disabled="deleting || (requirePasswordForDelete && !deleteForm.password)"
                            @click="deleteSupplier"
                        >
                            {{ deleting ? '…' : 'تأكيد الحذف' }}
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </AppSidebarLayout>
</template>
