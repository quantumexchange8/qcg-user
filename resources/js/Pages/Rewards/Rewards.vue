<script setup>
import { usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import { Vue3Lottie } from 'vue3-lottie';
import OneStarBadge from '@/Components/Icons/OneStarBadge.json';
import { transactionFormat } from "@/Composables/index.js";
import Button from "@/Components/Button.vue";
import {ref, watchEffect, computed} from 'vue';
import RewardRedemption from "./Partials/RewardRedemption.vue";
import dayjs from "dayjs";
import { trans } from "laravel-vue-i18n";
import Dialog from "primevue/dialog";

const { formatDate, formatDateTime, formatAmount } = transactionFormat();

const pointHistories = ref([]);
const tradePoints = ref([]);
const totalTradePoints = ref(0);

const getResults = async () => {
    let url = `/rewards/getTradePoints`;

    try {
        const response = await axios.get(url);

        tradePoints.value = response.data.tradePoints;
        totalTradePoints.value = response.data.totalTradePoints;
    } catch (error) {
        console.error('Error fetching trade points:', error);
    }
};

getResults();

const getPointHistories = async () => {
    let url = `/rewards/getPointHistory`;

    try {
        const response = await axios.get(url);

        pointHistories.value = response.data.pointHistory;
        // console.log(pointHistories)
    } catch (error) {
        console.error('Error fetching trade point history:', error);
    }
};

getPointHistories();

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
        getPointHistories();
    }

    if (usePage().props.notification !== null) {
        getResults();
        getPointHistories();
    }
});

const getTransactionLabel = (type) => {
    return type === 'redemption' ? trans('public.used') : trans('public.earned');
};

const getSign = (type) => {
    return type === 'redemption' ? '-' : '+';
};

const showAll = ref(false);

const limitedHistories = computed(() => pointHistories.value.slice(0, 6));

const viewAll = () => {
    showAll.value = true;
};

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'approved': return 'bg-success-500 text-white';
        case 'processing': return 'bg-warning-500 text-white';
        case 'rejected': return 'bg-error-500 text-white';
        default: return 'bg-gray-500 text-white';
    }
};

const getStatusTooltip = (history) => {
    if (history.status === 'processing') return null;
    return `${history.status} on ${formatDate(history.approved_at)}`;
};
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.rewards')">
        <div class="w-full flex flex-col items-center gap-5">
            <!-- overview -->
            <div class="w-full grid grid-cols-1 md:grid-cols-2 items-center gap-5">
                <!-- total points -->
                <div class="bg-white shadow-card rounded-lg p-6 flex flex-col gap-5 self-stretch">
                    <div class="flex flex-row gap-5 items-center">
                        <div class="w-12 h-12 md:w-[60px] md:h-[60px]">
                            <Vue3Lottie :animationData="OneStarBadge" :loop="true" :autoplay="true" />
                        </div>
                        <div class="flex flex-col">
                            <span class="text-2xl font-semibold text-gray-950">{{ formatAmount(totalTradePoints ?? 0) }}</span>
                            <span class="text-sm text-gray-500">{{ $t('public.personal_trade_points') }} (tp)</span>
                        </div>
                    </div>
                    <div class="h-[1px] bg-gray-200" />
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-1">
                            <span class="font-semibold text-gray-950">{{ $t('public.trade_points_msg1') }}</span>
                            <span class="text-sm text-gray-500">{{ $t('public.trade_points_msg2') }}</span>
                        </div>
                        <div class="flex flex-col gap-2">
                            <div v-for="(item, index) in tradePoints" :key="index" class="flex items-center gap-5 self-stretch">
                                <span class="w-full truncate text-sm text-gray-950 font-medium">
                                    {{ $t('public.' + item.symbol_group) }}
                                </span>
                                <span class="w-full truncate text-sm text-warning-500 text-right font-medium">
                                    {{ formatAmount(item.trade_points) }} tp
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- points history -->
                <div class="bg-white shadow-card rounded-lg px-6 pt-6 pb-3 flex flex-col gap-2 self-stretch">
                    <div class="flex flex-row items-center justify-between">
                        <span class="font-bold text-gray-950">{{ $t('public.points_history') }}</span>
                        <Button
                            type="button"
                            variant="gray-outlined"
                            class="!py-2"
                            @click="viewAll"
                        >
                            {{ $t('public.view_all') }}
                        </Button>
                    </div>
                    <!-- <div 
                        class="flex flex-row py-[6px] justify-between items-center gap-5 self-stretch border-b border-gray-100"
                    >
                        <div class="flex flex-col">
                            <span class="text-sm text-gray-950 font-medium">{{ $t('public.earned') }}</span>
                            <span class="text-xs text-gray-500">2020/01/01</span>
                        </div>
                        <span class="text-sm text-gray-950 font-medium">+0.00 tp</span>
                    </div> -->
                    <div class="w-full">
                        <div v-for="(history, index) in limitedHistories"
                            :key="index"
                            class="flex flex-row py-[6px] justify-between items-center gap-5 self-stretch border-b border-gray-100"
                            :class="{ 'border-transparent': index === limitedHistories.length - 1 }"
                        >
                            <div class="flex flex-col">
                                <span class="text-sm text-gray-950 font-medium">{{ getTransactionLabel(history.type) }}</span>
                                <div class="flex flex-row items-center gap-2">
                                    <span class="text-xs text-gray-500">{{ formatDate(history.date) }}</span>
                                    <span 
                                        v-if="history.type === 'redemption'" 
                                        :class="['px-1 py-0.5 text-xxs rounded-sm', getStatusBadgeClass(history.status)]"
                                        v-tooltip.top="history.status !== 'processing' ? getStatusTooltip(history) : null"
                                    >
                                        {{ $t(`public.${history.status ?? 'unknown'}`) }}
                                    </span>
                                </div>
                            </div>
                            <span class="text-sm text-gray-950 font-medium">{{ getSign(history.type) }}{{ formatAmount(history.amount) }} tp</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- rewards -->
            <RewardRedemption 
               :trade_points="totalTradePoints" 
            />

        </div>
    </AuthenticatedLayout>

    <!-- Dialog for Full History -->
    <Dialog v-model:visible="showAll"
        modal
        :header="$t('public.point_history')"
        class="dialog-xs md:dialog-sm"
    >
        <div v-for="(history, index) in limitedHistories"
            :key="index"
            class="flex flex-row py-[6px] justify-between items-center gap-5 self-stretch border-b border-gray-100"
            :class="{ 'border-transparent': index === limitedHistories.length - 1 }"
        >
            <div class="flex flex-col">
                <span class="text-sm text-gray-950 font-medium">{{ getTransactionLabel(history.type) }}</span>
                <div class="flex flex-row items-center gap-2">
                    <span class="text-xs text-gray-500">{{ formatDate(history.date) }}</span>
                    <span 
                        v-if="history.type === 'redemption'" 
                        :class="['px-1 py-0.5 text-xxs rounded-sm', getStatusBadgeClass(history.status)]"
                        v-tooltip.top="history.status !== 'processing' ? getStatusTooltip(history) : null"
                    >
                        {{ $t(`public.${history.status}`) }}
                    </span>
                </div>
            </div>
            <span class="text-sm text-gray-950 font-medium">{{ getSign(history.type) }}{{ formatAmount(history.amount) }} tp</span>
        </div>
    </Dialog>
</template>
