<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import { usePage, useForm } from "@inertiajs/vue3";
import { transactionFormat } from "@/Composables/index.js";
import { IconCircleXFilled, IconSearch, IconPencilMinus } from "@tabler/icons-vue";
import { ref, watchEffect, computed } from "vue";
import Loader from "@/Components/Loader.vue";
import { wTrans, trans } from "laravel-vue-i18n";
import RebateSettingTable from "./Partials/RebateSettingTable.vue";

const props = defineProps({
    accountTypes: Array,
})

const forexRebate = ref(0.00);
const stocksRebate = ref(0.00);
const indicesRebate = ref(0.00);
const commoditiesRebate = ref(0.00);
const cryptocurrencyRebate = ref(0.00);

// data overview
const dataOverviews = computed(() => [
    {
        rebate: forexRebate.value,
        label: trans('public.forex'),
    },
    {
        rebate: stocksRebate.value,
        label: trans('public.stocks'),
    },
    {
        rebate: indicesRebate.value,
        label: trans('public.indices'),
    },
    {
        rebate: commoditiesRebate.value,
        label: trans('public.commodities'),
    },
    {
        rebate: cryptocurrencyRebate.value,
        label: trans('public.cryptocurrency'),
    },
]);

const accountType = ref(1)
const rebateDetails = ref();
const loading = ref(false);

const { formatAmount, formatDate } = transactionFormat();

const getResults = async () => {
    loading.value = true;

    try {
        const response = await axios.get(`/rebate_setting/getRebateData?account_type_id=${accountType.value}`);
        rebateDetails.value = response.data.rebateDetails;
    } catch (error) {
        console.error('Error fetch rebate data:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

const handleAccountTypeChange = (newType) => {
    accountType.value = newType
    getResults();
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.rebate_setting')">
        <div class="w-full flex flex-col items-center gap-5">
            <div class="w-full grid grid-cols-2 gap-3 md:gap-3 md:grid-cols-5 self-stretch overflow-x-auto">
                <!-- <div 
                    v-for="(item, index) in dataOverviews"
                    :key="index"
                    class="min-w-[140px] md:min-w-[150px] xl:min-w-[220px] md:h-[148px] flex flex-col justify-center items-center py-5 px-3 md:p-0 gap-3 rounded-lg bg-white shadow-card"
                >
                    <component :is="item.icon" class="w-8 h-8 grow-0 shrink-0 text-primary-600" />
                    <span class="text-gray-500 text-sm">{{ item.label }}</span>
                    <span class="self-stretch text-gray-950 text-center text-lg font-semibold">{{ item.total }}</span>
                </div> -->
                <div
                    v-if="rebateDetails"
                    v-for="rebateDetail in rebateDetails"
                    class="min-w-[140px] md:min-w-[150px] xl:min-w-[220px] md:h-[148px] flex flex-col justify-center items-center py-5 px-3 md:p-0 gap-3 rounded-lg bg-white shadow-card"
                >
                    <div class="flex flex-col gap-4">
                        <div class="text-sm font-semibold w-full">{{ $t(`public.${rebateDetail.symbol_group.display}`) }}</div>
                        <div class="flex flex-col gap-1">
                            <div class="text-xl font-semibold w-full">{{ formatAmount(rebateDetail.amount, 0) }}</div>
                            <div class="text-xs text-gray-500 w-full">{{ $t('public.rebate') }} / Ł ($)</div>
                        </div>
                    </div>
                </div>
            </div>

            <RebateSettingTable :accountTypes="accountTypes" @update:accountType="handleAccountTypeChange" />
        </div>
    </AuthenticatedLayout>
</template>
