<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AppSidebarLayout from '../../Layouts/AppSidebarLayout.vue';

const loading = ref(true);
const errorMsg = ref('');

const categories = ref([]);
const suppliers = ref([]);
const pagination = ref({ data: [], current_page: 1, last_page: 1, per_page: 20, total: 0 });
const filters = reactive({
    search: '',
    category_id: '',
    supplier_id: '',
    has_batches: 'all',
    stock_state: 'all',
});

const money = (n) => (Number(n) ?? 0).toFixed(2);

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
        const { data } = await axios.get('/api/products', {
            params: {
                page,
                per_page: 15,
                search: filters.search || undefined,
                category_id: filters.category_id || undefined,
                supplier_id: filters.supplier_id || undefined,
                has_batches: filters.has_batches,
                stock_state: filters.stock_state,
            },
        });
        pagination.value = data;
    } catch (e) {
        errorMsg.value = e.response?.data?.message ?? 'تعذر تحميل المنتجات.';
    } finally {
        loading.value = false;
    }
}

const categoryName = computed(() => {
    const map = Object.fromEntries(categories.value.map((c) => [c.id, c.name]));
    return (id) => map[id] ?? '—';
});

let searchDebounce;
watch(
    () => filters.search,
    () => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => fetchProducts(1), 350);
    }
);

watch(
    () => [filters.category_id, filters.supplier_id, filters.has_batches, filters.stock_state],
    () => fetchProducts(1)
);

onMounted(async () => {
    try {
        await fetchMeta();
        await fetchProducts(1);
    } catch (e) {
        errorMsg.value = 'تعذر تحميل البيانات.';
    }
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
                <a href="/products/create" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white">منتج جديد</a>
            </div>

            <p v-if="errorMsg" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                {{ errorMsg }}
            </p>

            <section class="mb-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="mb-3 text-sm font-semibold text-zinc-800 dark:text-zinc-200">البحث والفلترة</h2>
                <div class="grid gap-3 sm:grid-cols-5">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs text-zinc-500">بحث (الاسم / الباركود)</label>
                        <input v-model="filters.search" class="w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800" placeholder="ابحث عن منتج..." />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-zinc-500">الفئة</label>
                        <select v-model="filters.category_id" class="w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                            <option value="">الكل</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-zinc-500">المورد</label>
                        <select v-model="filters.supplier_id" class="w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                            <option value="">الكل</option>
                            <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-zinc-500">وجود دفعات</label>
                        <select v-model="filters.has_batches" class="w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                            <option value="all">الكل</option>
                            <option value="yes">لديه دفعات</option>
                            <option value="no">بدون دفعات</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 grid gap-3 sm:w-1/3">
                    <div>
                        <label class="mb-1 block text-xs text-zinc-500">حالة المخزون</label>
                        <select v-model="filters.stock_state" class="w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                            <option value="all">الكل</option>
                            <option value="available">متوفر</option>
                            <option value="low">منخفض</option>
                            <option value="out">نفد</option>
                        </select>
                    </div>
                </div>
            </section>

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
                                    <a :href="`/products/${row.id}`" title="عرض التفاصيل" class="rounded px-1 text-base text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-zinc-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    </a>
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

        </main>
    </AppSidebarLayout>
</template>
