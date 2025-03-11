<script setup>
import { usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import { Vue3Lottie } from 'vue3-lottie';
import OneStarBadge from '@/Components/Icons/OneStarBadge.json';
import { transactionFormat } from "@/Composables/index.js";
import Button from "@/Components/Button.vue";
import {ref, watchEffect} from 'vue';
import RewardRedemption from "./Partials/RewardRedemption.vue";

const { formatDate, formatDateTime, formatAmount } = transactionFormat();

const tradePoints = ref([]);
const totalTradePoints = ref(0);

const getResults = async () => {
    let url = `/rewards/getTradePoints`;

    try {
        const response = await axios.get(url);

        // Update the rebateSummary and totals
        tradePoints.value = response.data.tradePoints;
        totalTradePoints.value = response.data.totalTradePoints;
    } catch (error) {
        console.error('Error fetching trade points:', error);
    }
};

getResults();

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }

    if (usePage().props.notification !== null) {
        getResults();
    }
});
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
                                    {{ formatAmount(0) }} tp
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
                        >
                            {{ $t('public.view_all') }}
                        </Button>
                    </div>
                    <div 
                        class="flex flex-row py-[6px] justify-between items-center gap-5 self-stretch border-b border-gray-100"
                    >
                        <div class="flex flex-col">
                            <span class="text-sm text-gray-950 font-medium">{{ $t('public.earned') }}</span>
                            <span class="text-xs text-gray-500">2020/01/01</span>
                        </div>
                        <span class="text-sm text-gray-950 font-medium">+0.00 tp</span>
                    </div>
                    <!-- <div v-for="(history, index) in pointHistories"
                        :key="index"
                        class="flex flex-row py-[6px] items-center gap-5 self-stretch border-b border-gray-100"
                        :class="{ 'border-transparent': index === history.length - 1 }"
                    >
                        <div class="flex flex-col">
                            <span class="text-sm text-gray-950 font-medium">Earned</span>
                            <span class="text-xs text-gray-500">2020/01/01</span>
                        </div>
                        <span class="text-sm text-gray-950 font-medium">+0.00 tp</span>
                    </div> -->
                </div>
            </div>
            <!-- rewards -->
            <RewardRedemption 
               :trade_points="totalTradePoints" 
            />

        </div>
    </AuthenticatedLayout>
</template>
