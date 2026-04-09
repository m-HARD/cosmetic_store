<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AppSidebarLayout from '../../Layouts/AppSidebarLayout.vue';

const loading = ref(true);
const saving = ref(false);
const errorMsg = ref('');
const fieldErrors = ref({});

const filters = reactive({
    search: '',
    has_products: 'all',
    status: 'all',
});

const pagination = ref({ data: [], current_page: 1, last_page: 1, per_page: 15, total: 0 });
const createModalOpen = ref(false);
const createForm = reactive({
    name: '',
    description: '',
    is_active: true,
});

const hasProductsText = computed(() => {
    if (filters.has_products === 'yes') return 'الفئات التي تحتوي منتجات فقط';
    if (filters.has_products === 'no') return 'الفئات بدون منتجات فقط';
    return 'كل الفئات';
});

async function fetchCategories(page = 1) {
    loading.value = true;
    errorMsg.value = '';
    try {
        const { data } = await axios.get('/api/categories/manage', {
            params: {
                page,
                per_page: 15,
                search: filters.search || undefined,
                has_products: filters.has_products,
                status: filters.status,
            },
        });
        pagination.value = data;
    } catch (e) {
        errorMsg.value = e.response?.data?.message ?? 'تعذر تحميل الفئات.';
    } finally {
        loading.value = false;
    }
}

let searchDebounce;
watch(
    () => filters.search,
    () => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => {
            fetchCategories(1);
        }, 350);
    }
);

watch(
    () => [filters.has_products, filters.status],
    () => {
        fetchCategories(1);
    }
);

onMounted(() => {
    fetchCategories(1);
});

function openCreateModal() {
    Object.assign(createForm, { name: '', description: '', is_active: true });
    fieldErrors.value = {};
    createModalOpen.value = true;
}

async function createCategory() {
    saving.value = true;
    fieldErrors.value = {};
    errorMsg.value = '';
    try {
        await axios.post('/api/categories', {
            name: createForm.name,
            description: createForm.description || null,
            is_active: !!createForm.is_active,
        });
        createModalOpen.value = false;
        await fetchCategories(1);
    } catch (e) {
        if (e.response?.status === 422) {
            fieldErrors.value = e.response?.data?.errors ?? {};
        } else {
            errorMsg.value = e.response?.data?.message ?? 'تعذر إضافة الفئة.';
        }
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <AppSidebarLayout>
        <main class="p-6">
            <div class="mb-6 flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-50">فئات المنتجات</h1>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">إدارة الفئات، عرض عدد المنتجات، والدخول لتفاصيل وتعديل وحذف كل فئة.</p>
                </div>
                <button
                    type="button"
                    class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white"
                    @click="openCreateModal"
                >
                    فئة جديدة
                </button>
            </div>

            <p v-if="errorMsg" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                {{ errorMsg }}
            </p>

            <section class="mb-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="mb-3 text-sm font-semibold text-zinc-800 dark:text-zinc-200">البحث والفلترة</h2>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs text-zinc-500">بحث بالاسم أو الوصف</label>
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="مثال: العناية بالبشرة"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-zinc-500">حسب وجود المنتجات</label>
                        <select v-model="filters.has_products" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                            <option value="all">الكل</option>
                            <option value="yes">تحتوي منتجات</option>
                            <option value="no">بدون منتجات</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-zinc-500">الحالة</label>
                        <select v-model="filters.status" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                            <option value="all">الكل</option>
                            <option value="active">نشطة</option>
                            <option value="inactive">غير نشطة</option>
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
                            <th class="px-3 py-2 font-semibold">اسم الفئة</th>
                            <th class="px-3 py-2 font-semibold">الوصف</th>
                            <th class="px-3 py-2 font-semibold">الحالة</th>
                            <th class="px-3 py-2 font-semibold">عدد المنتجات</th>
                            <th class="px-3 py-2 font-semibold">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in pagination.data" :key="row.id" class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="px-3 py-2 font-medium">{{ row.name }}</td>
                            <td class="px-3 py-2">{{ row.description || '—' }}</td>
                            <td class="px-3 py-2">{{ row.is_active ? 'نشطة' : 'غير نشطة' }}</td>
                            <td class="px-3 py-2">
                                <span class="rounded bg-zinc-100 px-2 py-1 text-xs font-semibold dark:bg-zinc-800">
                                    {{ row.products_count ?? 0 }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <a :href="`/categories/${row.id}`" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">عرض التفاصيل</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!loading && !pagination.data?.length" class="p-8 text-center text-zinc-500">لا توجد بيانات مطابقة.</div>
                <div v-if="pagination.last_page > 1" class="flex items-center justify-between border-t border-zinc-200 px-3 py-2 dark:border-zinc-700">
                    <span class="text-xs text-zinc-500">صفحة {{ pagination.current_page }} من {{ pagination.last_page }}</span>
                    <div class="flex gap-2">
                        <button type="button" class="rounded border border-zinc-300 px-2 py-1 text-xs disabled:opacity-40 dark:border-zinc-600" :disabled="pagination.current_page <= 1" @click="fetchCategories(pagination.current_page - 1)">السابق</button>
                        <button type="button" class="rounded border border-zinc-300 px-2 py-1 text-xs disabled:opacity-40 dark:border-zinc-600" :disabled="pagination.current_page >= pagination.last_page" @click="fetchCategories(pagination.current_page + 1)">التالي</button>
                    </div>
                </div>
            </div>

            <div
                v-if="createModalOpen"
                class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center"
                @click.self="createModalOpen = false"
            >
                <div class="w-full max-w-lg rounded-xl bg-white p-4 shadow-xl dark:bg-zinc-900" @click.stop>
                    <h2 class="mb-3 text-lg font-bold">إضافة فئة جديدة</h2>
                    <div class="space-y-3 text-sm">
                        <div>
                            <label class="mb-1 block text-zinc-600 dark:text-zinc-400">الاسم</label>
                            <input v-model="createForm.name" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" />
                            <p v-if="fieldErrors.name" class="mt-1 text-xs text-red-600">{{ fieldErrors.name[0] }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-zinc-600 dark:text-zinc-400">الوصف</label>
                            <textarea v-model="createForm.description" rows="2" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" />
                        </div>
                        <div>
                            <label class="mb-1 block text-zinc-600 dark:text-zinc-400">الحالة</label>
                            <select v-model="createForm.is_active" class="w-full rounded border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800">
                                <option :value="true">نشطة</option>
                                <option :value="false">غير نشطة</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600" @click="createModalOpen = false">إلغاء</button>
                        <button
                            type="button"
                            class="rounded bg-zinc-900 px-3 py-1.5 text-sm text-white disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900"
                            :disabled="saving"
                            @click="createCategory"
                        >
                            {{ saving ? '…' : 'إضافة' }}
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </AppSidebarLayout>
</template>
