<script setup>
import {
    ArrowRightStartOnRectangleIcon,
    Bars3Icon,
    ChartBarIcon,
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    Cog6ToothIcon,
    CubeIcon,
    HomeIcon,
    ReceiptPercentIcon,
    TruckIcon,
    UserCircleIcon,
    UsersIcon,
    WalletIcon,
} from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    name: { type: String, required: true },
});

const iconMap = {
    home: HomeIcon,
    sales: ReceiptPercentIcon,
    products: CubeIcon,
    suppliers: TruckIcon,
    reports: ChartBarIcon,
    expenses: WalletIcon,
    users: UsersIcon,
    settings: Cog6ToothIcon,
    profile: UserCircleIcon,
    logout: ArrowRightStartOnRectangleIcon,
    bars: Bars3Icon,
    chevronDown: ChevronDownIcon,
    chevronRight: ChevronRightIcon,
    chevronLeft: ChevronLeftIcon,
};

const iconComponent = computed(() => iconMap[props.name] ?? HomeIcon);
</script>

<template>
    <component :is="iconComponent" class="size-5" />
</template>
