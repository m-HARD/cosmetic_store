<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AppSidebarLayout from '../../Layouts/AppSidebarLayout.vue';

const loading = ref(true);
const detailsLoading = ref(false);
const errorMsg = ref('');

const filters = reactive({
    search: '',
    has_products: 'all',
});

const pagination = ref({ data: [], current_page: 1, last_page: 1, per_page: 15, total: 0 });

const detailOpen = ref(false);
const selected = ref(null);

const money = (n) => (Number(n) || 0).toFixed(2);

const hasProductsText = computed(() => {
    if (filters.has_products === 'yes') return 'بموردين لديهم منتجات فقط';
    if (filters.has_products === 'no') return 'بموردين بدون منتجات فقط';
    return 'كل الموردين';
});

async function fetchSuppliers(page = 1) {
    loading.value = true;
    errorMsg.value = '';
    try {
        const { data } = await axios.get('/api/suppliers', {
            params: {
                page,
                per_page: 15,
                search: filters.search || undefined,
                has_products: filters.has_products,
            },
        });
        pagination.value = data;
    } catch (e) {
        errorMsg.value = e.response?.data?.message ?? 'تعذر تحميل الموردين.';
    } finally {
        loading.value = false;
    }
}

async function openDetails(row) {
    detailOpen.value = true;
    detailsLoading.value = true;
    selected.value = null;
    try {
        const { data } = await axios.get(`/api/suppliers/${row.id}`);
        selected.value = data;
    } catch (e) {
        errorMsg.value = e.response?.data?.message ?? 'تعذر تحميل تفاصيل المورد.';
        detailOpen.value = false;
    } finally {
        detailsLoading.value = false;
    }
}

let searchDebounce;
watch(
    () => filters.search,
    () => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => {
            fetchSuppliers(1);
        }, 350);
    }
);

watch(
    () => filters.has_products,
    () => {
        fetchSuppliers(1);
    }
);

onMounted(() => {
    fetchSuppliers(1);
});
</script>

<template>
    <AppSidebarLayout>
        <main class="p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-50">الموردون</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">عرض الموردين، عدد الأصناف لكل مورد، وتفاصيل المنتجات المقدّمة منهم.</p>
            </div>

            <p v-if="errorMsg" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                {{ errorMsg }}
            </p>

            <section class="mb-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="mb-3 text-sm font-semibold text-zinc-800 dark:text-zinc-200">البحث والفلترة</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs text-zinc-500">بحث بالاسم / الهاتف / العنوان</label>
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="مثال: مورد الشرق"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-zinc-500">تصفية حسب المنتجات</label>
                        <select
                            v-model="filters.has_products"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800"
                        >
                            <option value="all">الكل</option>
                            <option value="yes">لديه منتجات</option>
                            <option value="no">بدون منتجات</option>
                        </select>
                    </div>
                </div>
                <p class="mt-2 text-xs text-zinc-500">الحالة الحالية: {{ hasProductsText }}</p>
            </section>

            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div v-if="loading" class="p-8 text-center text-zinc-500">جاري التحميل…</div>
                <table v-else class="w-full min-w-[780px] text-right text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/80">
                        <tr>
                            <th class="px-3 py-2 font-semibold">اسم المورد</th>
                            <th class="px-3 py-2 font-semibold">الهاتف</th>
                            <th class="px-3 py-2 font-semibold">العنوان</th>
                            <th class="px-3 py-2 font-semibold">عدد المنتجات المقدمة</th>
                            <th class="px-3 py-2 font-semibold">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in pagination.data" :key="row.id" class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="px-3 py-2 font-medium">{{ row.name }}</td>
                            <td class="px-3 py-2">{{ row.phone || '—' }}</td>
                            <td class="px-3 py-2">{{ row.address || '—' }}</td>
                            <td class="px-3 py-2">
                                <span class="rounded bg-zinc-100 px-2 py-1 text-xs font-semibold dark:bg-zinc-800">
                                    {{ row.products_count ?? 0 }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <button type="button" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400" @click="openDetails(row)">
                                    عرض التفاصيل
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!loading && !pagination.data?.length" class="p-8 text-center text-zinc-500">لا توجد بيانات مطابقة.</div>
                <div v-if="pagination.last_page > 1" class="flex items-center justify-between border-t border-zinc-200 px-3 py-2 dark:border-zinc-700">
                    <span class="text-xs text-zinc-500">صفحة {{ pagination.current_page }} من {{ pagination.last_page }}</span>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="rounded border border-zinc-300 px-2 py-1 text-xs disabled:opacity-40 dark:border-zinc-600"
                            :disabled="pagination.current_page <= 1"
                            @click="fetchSuppliers(pagination.current_page - 1)"
                        >
                            السابق
                        </button>
                        <button
                            type="button"
                            class="rounded border border-zinc-300 px-2 py-1 text-xs disabled:opacity-40 dark:border-zinc-600"
                            :disabled="pagination.current_page >= pagination.last_page"
                            @click="fetchSuppliers(pagination.current_page + 1)"
                        >
                            التالي
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="detailOpen"
                class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center"
                @click.self="detailOpen = false"
            >
                <div class="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-xl bg-white p-4 shadow-xl dark:bg-zinc-900" @click.stop>
                    <div class="mb-4 flex items-center justify-between gap-2">
                        <h2 class="text-lg font-bold">تفاصيل المورد</h2>
                        <button type="button" class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600" @click="detailOpen = false">إغلاق</button>
                    </div>

                    <div v-if="detailsLoading" class="py-10 text-center text-zinc-500">جاري تحميل التفاصيل…</div>

                    <template v-else-if="selected">
                        <div class="mb-4 grid gap-3 rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700 sm:grid-cols-2">
                            <div><span class="text-zinc-500">الاسم: </span>{{ selected.supplier.name }}</div>
                            <div><span class="text-zinc-500">الهاتف: </span>{{ selected.supplier.phone || '—' }}</div>
                            <div><span class="text-zinc-500">العنوان: </span>{{ selected.supplier.address || '—' }}</div>
                            <div><span class="text-zinc-500">عدد المنتجات: </span>{{ selected.products_count }}</div>
                            <div><span class="text-zinc-500">إجمالي الكميات المتاحة: </span>{{ selected.products_stock_total }}</div>
                            <div class="sm:col-span-2"><span class="text-zinc-500">ملاحظات: </span>{{ selected.supplier.notes || '—' }}</div>
                        </div>

                        <h3 class="mb-2 text-sm font-semibold">تفاصيل المنتجات</h3>
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
                                    <tr v-for="product in selected.products" :key="product.id" class="border-b border-zinc-100 dark:border-zinc-800">
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
                        <p v-if="!selected.products?.length" class="py-4 text-center text-sm text-zinc-500">لا توجد منتجات لهذا المورد.</p>
                    </template>
                </div>
            </div>
        </main>
    </AppSidebarLayout>
</template>
