<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import Button from '@/Components/Button.vue';
import {usePage} from "@inertiajs/vue3";
import {transactionFormat} from "@/Composables/index.js";
import {computed, ref, watchEffect} from "vue";
import {
    DepositIcon,
    WithdrawalIcon,
    NetBalanceIcon,
    NetAssetIcon,
    TradeLotIcon,
    TradeVolumeIcon,
} from '@/Components/Icons/outline.jsx';
import {IconReport} from '@tabler/icons-vue';
import {trans} from "laravel-vue-i18n";
import RebateWalletAction from "@/Pages/Dashboard/Partials/RebateWalletAction.vue";
import RebateEarn from "@/Pages/Dashboard/Partials/RebateEarn.vue";
import RebateHistory from "@/Pages/Dashboard/Partials/RebateHistory.vue";
import Vue3Autocounter from "vue3-autocounter";
import Account from "@/Pages/Accounts/Account.vue";

// const props = defineProps({
//     terms: Object,
//     postCounts: Number,
//     authorName: String,
// })

const user = usePage().props.auth.user;
const { formatAmount } = transactionFormat();
const groupTotalDeposit = ref(0);
const groupTotalWithdrawal = ref(0);
const totalGroupNetBalance = ref(0);
const total_group_net_asset = ref(0);
const groupTotalTradeLot = ref(0);
const groupTotalTradeVolume = ref(0);
const rebateWallet = ref();

// data overview
const dataOverviews = computed(() => [
    {
        icon: DepositIcon,
        total: groupTotalDeposit.value,
        label: user.role === 'member' ? trans('public.total_deposit') : trans('public.group_total_deposit'),
        borderColor: 'border-green',
        type: 'total_deposit'
    },
    {
        icon: WithdrawalIcon,
        total: groupTotalWithdrawal.value,
        label: user.role === 'member' ? trans('public.total_withdrawal') : trans('public.group_total_withdrawal'),
        borderColor: 'border-pink',
        type: 'total_withdrawal'
    },
    {
        icon: NetBalanceIcon,
        total: totalGroupNetBalance.value,
        label: user.role === 'member' ? trans('public.total_net_balance') : trans('public.group_total_net_balance'),
        borderColor: 'border-[#FEDC32]',
        type: 'total_net_balance'
    },
    {
        icon: NetAssetIcon,
        total: total_group_net_asset.value,
        label: user.role === 'member' ? trans('public.total_asset') : trans('public.group_total_asset'),
        borderColor: 'border-indigo',
        type: 'total_asset'
    },
    {
        icon: TradeLotIcon,
        total: groupTotalTradeLot.value,
        label: user.role === 'member' ? trans('public.total_trade_lots') : trans('public.group_total_trade_lots'),
        borderColor: 'border-green',
        type: 'total_trade_lots'
    },
    {
        icon: TradeVolumeIcon,
        total: groupTotalTradeVolume.value,
        label: user.role === 'member' ? trans('public.total_trade_volume') : trans('public.group_total_trade_volume'),
        borderColor: 'border-green',
        type: 'total_trade_volume'
    },
]);

const getDashboardData = async () => {
    try {
        const response = await axios.get('/dashboard/getDashboardData');
        rebateWallet.value = response.data.rebateWallet
        groupTotalDeposit.value = response.data.groupTotalDeposit
        groupTotalWithdrawal.value = response.data.groupTotalWithdrawal
        totalGroupNetBalance.value = response.data.totalGroupNetBalance
        groupTotalTradeLot.value = response.data.groupTotalTradeLot
        groupTotalTradeVolume.value = response.data.groupTotalTradeVolume
    } catch (error) {
        console.error('Error pending counts:', error);
    }
};

getDashboardData();

watchEffect(() => {
    if (usePage().props.toast !== null || usePage().props.notification !== null) {
        getDashboardData();
    }
});
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.dashboard')">
        <div class="flex flex-col gap-3 md:gap-5 items-center self-stretch">
            <div class="flex flex-col items-center gap-3 md:gap-5 self-stretch w-full">
                <div class="flex flex-col gap-3 md:gap-5 items-center self-stretch w-full">
                    <!-- greeting card -->
                    <div class="bg-white rounded-lg h-[60px] md:h-20 shadow-card relative overflow-hidden px-3 py-[10px] md:px-6 md:py-4 items-center w-full">
                        <div class="flex flex-col gap-1 items-start justify-center w-[calc(100%-60px)]">
                            <span class="md:text-base text-sm text-gray-950 font-bold line-clamp-1">{{ $t('public.welcome_back', {'name': user.first_name}) }}</span>
                            <span v-if="user.role==='agent'" class="md:text-sm text-xs text-gray-700 line-clamp-1">{{ $t('public.welcome_back_caption') }}</span>
                            <span v-else class="md:text-sm text-xs text-gray-700 line-clamp-1">{{ $t('public.member_welcome_back_caption') }}</span>
                        </div>

                        <div class="absolute right-0 top-0">
                            <img src="/assets/greeting_banner.svg" alt="banner" class="hidden md:block">
                            <img src="/assets/greeting_banner_sm.svg" alt="banner_sm" class="block md:hidden">
                        </div>
                    </div>

                    <!-- overview data -->
                    <div
                        class="grid gap-3 md:gap-5 w-full grid-cols-2 xl:grid-cols-3"
                    >
                        <div
                            class="flex flex-row justify-between items-center gap-2 p-2 md:px-6 md:py-4 rounded-lg w-full shadow-card bg-white min-w-[140px] md:min-w-[240px] xl:min-w-[200px]"
                            :class="item.borderColor"
                            v-for="(item, index) in dataOverviews"
                            :key="index"
                        >
                            <component :is="item.icon" class="w-6 h-6 md:w-9 md:h-9 grow-0 shrink-0" />
                            <div class="flex flex-col items-end truncate">
                                <span class="text-gray-700 text-xxs md:text-sm font-medium text-right w-full truncate">{{ item.label }}</span>
                                <div v-if="item.type === 'total_trade_volume'" class="text-gray-950 md:text-lg font-semibold text-right w-full truncate">
                                    {{ formatAmount(item.total, 0) }}
                                </div>
                                <div v-else-if="item.type === 'total_trade_lots'" class="text-gray-950 md:text-lg font-semibold text-right w-full truncate">
                                    {{ formatAmount(item.total) }} Ł
                                </div>
                                <div v-else class="text-gray-950 md:text-lg font-semibold w-full text-right truncate">
                                    $ {{ formatAmount(item.total) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="user.role === 'agent'"
                    class="flex flex-col py-2 px-3 md:p-6 gap-3 items-center self-stretch w-full bg-white rounded-lg"
                >
                    <div class="flex h-9 items-center justify-between self-stretch">
                        <span class="text-gray-950 text-sm md:text-base font-bold">
                            {{ $t('public.agent_rebate') }}
                        </span>
                        <RebateHistory />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 md:gap-6 items-center self-stretch h-full w-full">
                        <!-- rebate earn -->
                        <RebateEarn />

                        <!-- rebate wallet -->
                        <div class="bg-gray-50 flex flex-col p-3 md:p-6 gap-3 justify-between items-center self-stretch w-full">
                            <div class="flex flex-col gap-1 items-center justify-center self-stretch w-full">
                                <span class="text-xxs md:text-sm text-gray-500">{{ $t('public.available_rebate_balance') }}</span>
                                <span class="text-lg md:text-xl text-gray-950 font-semibold"> $ <vue3-autocounter ref="counter" :startAmount="0" :endAmount="rebateWallet ? Number(rebateWallet.balance) : 0" :duration="1" separator="," decimalSeparator="." :decimals="2" :autoinit="true" /></span>
                            </div>
                            <RebateWalletAction
                                :rebateWallet="rebateWallet"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- posts -->
            <!-- <ForumPost
                :postCounts="postCounts"
                :authorName="authorName"
            /> -->
            <Account
            />
        </div>

    </AuthenticatedLayout>
</template>
