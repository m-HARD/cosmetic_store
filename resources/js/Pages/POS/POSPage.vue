<script setup>
import { computed, onMounted, ref } from 'vue';
import CategorySidebar from '../../Components/POS/CategorySidebar.vue';
import InvoicePanel from '../../Components/POS/InvoicePanel.vue';
import InvoiceTabs from '../../Components/POS/InvoiceTabs.vue';
import PaymentModal from '../../Components/POS/PaymentModal.vue';
import POSLayout from '../../Components/POS/POSLayout.vue';
import ProductGrid from '../../Components/POS/ProductGrid.vue';
import ProductSearch from '../../Components/POS/ProductSearch.vue';
import AppSidebarLayout from '../../Layouts/AppSidebarLayout.vue';

const categories = ref([
    { id: 1, name: 'العناية بالبشرة' },
    { id: 2, name: 'العطور' },
    { id: 3, name: 'الشعر' },
    { id: 4, name: 'المكياج' },
]);

const products = ref([
    { id: 1, name: 'شامبو', sale_price: 100, category_id: 3 },
    { id: 2, name: 'عطر وردي', sale_price: 250, category_id: 2 },
    { id: 3, name: 'كريم مرطب', sale_price: 120, category_id: 1 },
]);

const search = ref('');
const activeCategory = ref(1);
const tabs = ref([{ id: 1, name: 'فاتورة 1', items: [] }]);
const activeTabId = ref(1);
const paymentOpen = ref(false);
const paymentMethod = ref('cash');
const bankakLast5 = ref('');

const activeTab = computed(() => tabs.value.find((t) => t.id === activeTabId.value));
const filteredProducts = computed(() =>
    products.value.filter(
        (p) =>
            p.category_id === activeCategory.value &&
            (p.name.includes(search.value) || String(p.id).includes(search.value))
    )
);
const invoiceTotal = computed(() => (activeTab.value?.items ?? []).reduce((s, i) => s + i.line_total, 0));

function createInvoice() {
    const id = tabs.value.length + 1;
    tabs.value.push({ id, name: `فاتورة ${id}`, items: [] });
    activeTabId.value = id;
}

function addProduct(product) {
    if (!activeTab.value) return;
    const existing = activeTab.value.items.find((i) => i.id === product.id);
    if (existing) {
        existing.quantity += 1;
        existing.line_total = existing.quantity * existing.unit_price;
        return;
    }
    activeTab.value.items.push({
        id: product.id,
        name: product.name,
        quantity: 1,
        unit_price: product.sale_price,
        line_total: product.sale_price,
    });
}

function inc(id) {
    const item = activeTab.value?.items.find((i) => i.id === id);
    if (!item) return;
    item.quantity += 1;
    item.line_total = item.quantity * item.unit_price;
}

function dec(id) {
    const item = activeTab.value?.items.find((i) => i.id === id);
    if (!item) return;
    item.quantity = Math.max(1, item.quantity - 1);
    item.line_total = item.quantity * item.unit_price;
}

function removeItem(id) {
    if (!activeTab.value) return;
    activeTab.value.items = activeTab.value.items.filter((i) => i.id !== id);
}

onMounted(() => {
    // اختصارات لوحة المفاتيح مخصصة لتسريع عمل الكاشير.
    window.addEventListener('keydown', (e) => {
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
        <div class="relative flex min-h-0 min-h-[calc(100dvh-3.5rem)] flex-1 flex-col bg-slate-50 lg:min-h-screen">
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
                        :categories="categories"
                        :active-category="activeCategory"
                        @select="activeCategory = $event"
                    />
                </template>
                <template #center>
                    <div class="mb-3">
                        <ProductSearch v-model="search" />
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
                @update:payment-method="paymentMethod = $event"
                @update:bankak-last5="bankakLast5 = $event"
                @close="paymentOpen = false"
                @pay="paymentOpen = false"
            />
        </div>
    </AppSidebarLayout>
</template>
