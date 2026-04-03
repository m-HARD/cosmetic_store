<script setup>
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import CategorySidebar from '../../Components/POS/CategorySidebar.vue';
import InvoicePanel from '../../Components/POS/InvoicePanel.vue';
import InvoiceTabs from '../../Components/POS/InvoiceTabs.vue';
import PaymentModal from '../../Components/POS/PaymentModal.vue';
import POSLayout from '../../Components/POS/POSLayout.vue';
import ProductGrid from '../../Components/POS/ProductGrid.vue';
import ProductSearch from '../../Components/POS/ProductSearch.vue';
import AppSidebarLayout from '../../Layouts/AppSidebarLayout.vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
});

/** فئة افتراضية لعرض كل المنتجات */
const categoriesWithAll = computed(() => [{ id: 0, name: 'الكل' }, ...props.categories]);

const search = ref('');
const activeCategory = ref(0);
const searchInputRef = ref(null);
const tabs = ref([{ id: 1, name: 'فاتورة 1', items: [] }]);
const activeTabId = ref(1);
const paymentOpen = ref(false);
const paymentMethod = ref('cash');
const bankakLast5 = ref('');
const saleSubmitting = ref(false);

const activeTab = computed(() => tabs.value.find((t) => t.id === activeTabId.value));

const productsList = computed(() => props.products);

const filteredProducts = computed(() => {
    let list = productsList.value;
    if (activeCategory.value !== 0) {
        list = list.filter((p) => p.category_id === activeCategory.value);
    }
    const q = search.value.trim();
    if (!q) {
        return list;
    }
    return list.filter(
        (p) => p.name.includes(q) || (p.barcode && String(p.barcode).includes(q))
    );
});

const invoiceTotal = computed(() => (activeTab.value?.items ?? []).reduce((s, i) => s + i.line_total, 0));

function createInvoice() {
    const id = tabs.value.length + 1;
    tabs.value.push({ id, name: `فاتورة ${id}`, items: [] });
    activeTabId.value = id;
}

function addProduct(product) {
    if (!activeTab.value) {
        return;
    }
    const stock = product.stock ?? 0;
    if (stock <= 0) {
        return;
    }
    const existing = activeTab.value.items.find((i) => i.id === product.id);
    if (existing) {
        if (existing.quantity >= existing.max_stock) {
            return;
        }
        existing.quantity += 1;
        existing.line_total = existing.quantity * existing.unit_price;
        return;
    }
    activeTab.value.items.push({
        id: product.id,
        product_id: product.id,
        name: product.name,
        quantity: 1,
        unit_price: product.sale_price,
        line_total: product.sale_price,
        max_stock: stock,
    });
}

function inc(id) {
    const item = activeTab.value?.items.find((i) => i.id === id);
    if (!item) {
        return;
    }
    if (item.quantity >= item.max_stock) {
        return;
    }
    item.quantity += 1;
    item.line_total = item.quantity * item.unit_price;
}

function dec(id) {
    const item = activeTab.value?.items.find((i) => i.id === id);
    if (!item) {
        return;
    }
    item.quantity = Math.max(1, item.quantity - 1);
    item.line_total = item.quantity * item.unit_price;
}

function removeItem(id) {
    if (!activeTab.value) {
        return;
    }
    activeTab.value.items = activeTab.value.items.filter((i) => i.id !== id);
}

/** إدخال باركود بالمسح ثم Enter */
function onSearchEnter() {
    const q = search.value.trim();
    if (!q) {
        return;
    }
    const match = productsList.value.find((p) => String(p.barcode) === q);
    if (match) {
        addProduct(match);
        search.value = '';
    }
}

async function confirmSale() {
    if (!activeTab.value?.items.length) {
        window.alert('الفاتورة فارغة.');
        return;
    }
    if (paymentMethod.value === 'bankak' && bankakLast5.value.length !== 5) {
        window.alert('أدخل آخر 5 أرقام لعملية بنكك.');
        return;
    }
    const items = activeTab.value.items.map((i) => ({
        product_id: i.product_id,
        quantity: i.quantity,
        unit_price: Number(i.unit_price),
    }));
    const total = invoiceTotal.value;
    saleSubmitting.value = true;
    try {
        await axios.post('/api/pos/sales', {
            payment_method: paymentMethod.value,
            bankak_reference_last5: paymentMethod.value === 'bankak' ? bankakLast5.value : null,
            subtotal: total,
            discount: 0,
            tax: 0,
            total,
            paid_amount: total,
            change_amount: 0,
            items,
        });
        activeTab.value.items = [];
        paymentOpen.value = false;
        bankakLast5.value = '';
        router.reload({ only: ['products'] });
    } catch (e) {
        const data = e.response?.data;
        const msg =
            (typeof data?.message === 'string' && data.message) ||
            (data?.errors && Object.values(data.errors).flat().join(' ')) ||
            'تعذر تسجيل البيع.';
        window.alert(msg);
    } finally {
        saleSubmitting.value = false;
    }
}

onMounted(() => {
    window.addEventListener('keydown', (e) => {
        if (e.key === 'F1') {
            e.preventDefault();
            searchInputRef.value?.focus();
        }
        if (e.key === 'F2') {
            e.preventDefault();
            paymentOpen.value = true;
        }
        if (e.key === 'F4') {
            e.preventDefault();
            createInvoice();
        }
        if (e.key === 'Escape') {
            paymentOpen.value = false;
        }
    });
});
</script>

<template>
    <AppSidebarLayout>
        <div class="relative flex min-h-[calc(100dvh-3.5rem)] flex-1 flex-col bg-slate-50 lg:min-h-screen">
            <POSLayout>
                <template #top>
                    <InvoiceTabs
                        :tabs="tabs"
                        :active-id="activeTabId"
                        @select="activeTabId = $event"
                        @create="createInvoice"
                    />
                </template>
                <template #left>
                    <CategorySidebar
                        :categories="categoriesWithAll"
                        :active-category="activeCategory"
                        @select="activeCategory = $event"
                    />
                </template>
                <template #center>
                    <div class="mb-3">
                        <ProductSearch
                            ref="searchInputRef"
                            v-model="search"
                            @enter="onSearchEnter"
                        />
                    </div>
                    <ProductGrid :products="filteredProducts" @add="addProduct" />
                </template>
                <template #right>
                    <InvoicePanel
                        :items="activeTab?.items ?? []"
                        :total="invoiceTotal"
                        @inc="inc"
                        @dec="dec"
                        @remove="removeItem"
                        @pay="paymentOpen = true"
                    />
                </template>
            </POSLayout>

            <PaymentModal
                :open="paymentOpen"
                :total="invoiceTotal"
                :payment-method="paymentMethod"
                :bankak-last5="bankakLast5"
                :busy="saleSubmitting"
                @update:payment-method="paymentMethod = $event"
                @update:bankak-last5="bankakLast5 = $event"
                @close="paymentOpen = false"
                @pay="confirmSale"
            />
        </div>
    </AppSidebarLayout>
</template>
