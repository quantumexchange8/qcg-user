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
} from '@/Components/Icons/outline.jsx';
import {IconReport} from '@tabler/icons-vue';
import {trans} from "laravel-vue-i18n";
import RebateWalletAction from "@/Pages/Dashboard/Partials/RebateWalletAction.vue";
import RebateEarn from "@/Pages/Dashboard/Partials/RebateEarn.vue";
import RebateHistory from "@/Pages/Dashboard/Partials/RebateHistory.vue";
import Vue3Autocounter from "vue3-autocounter";
import ForumPost from "@/Pages/Dashboard/Partials/ForumPost.vue";

const props = defineProps({
    terms: Object,
    postCounts: Number,
    authorName: String,
})

const user = usePage().props.auth.user;
const { formatAmount } = transactionFormat();
const groupTotalDeposit = ref(0);
const groupTotalWithdrawal = ref(0);
const totalGroupNetBalance = ref(0);
const total_group_net_asset = ref(0);
const rebateWallet = ref();

// data overview
const dataOverviews = computed(() => [
    {
        icon: DepositIcon,
        total: groupTotalDeposit.value,
        label: user.role === 'member' ? trans('public.total_deposit') : trans('public.group_total_deposit'),
        borderColor: 'border-green',
    },
    {
        icon: WithdrawalIcon,
        total: groupTotalWithdrawal.value,
        label: user.role === 'member' ? trans('public.total_withdrawal') : trans('public.group_total_withdrawal'),
        borderColor: 'border-pink',
    },
    {
        icon: NetBalanceIcon,
        total: totalGroupNetBalance.value,
        label: user.role === 'member' ? trans('public.total_net_balance') : trans('public.group_total_net_balance'),
        borderColor: 'border-[#FEDC32]',
    },
    {
        icon: NetAssetIcon,
        total: total_group_net_asset.value,
        label: user.role === 'member' ? trans('public.total_asset') : trans('public.group_total_asset'),
        borderColor: 'border-indigo',
    },
]);

const getDashboardData = async () => {
    try {
        const response = await axios.get('/dashboard/getDashboardData');
        rebateWallet.value = response.data.rebateWallet
        groupTotalDeposit.value = response.data.groupTotalDeposit
        groupTotalWithdrawal.value = response.data.groupTotalWithdrawal
        totalGroupNetBalance.value = response.data.totalGroupNetBalance
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
        <div class="flex flex-col gap-5 items-center self-stretch">
            <div class="flex flex-col xl:flex-row items-center gap-5 self-stretch w-full">
                <div class="flex flex-col gap-5 items-center self-stretch w-full">
                    <!-- greeting card -->
                    <div class="bg-white rounded-lg h-[120px] md:h-40 shadow-card relative overflow-hidden p-3 md:px-6 md:py-8 items-center w-full">
                        <div class="flex flex-col gap-2 items-start justify-center w-full h-24 md:max-w-[500px] max-w-[170px]">
                            <span class="md:text-xl text-sm text-gray-950 font-bold">{{ $t('public.welcome_back', {'name': user.first_name}) }}</span>
                            <span class="md:text-sm text-xs text-gray-700">{{ $t('public.welcome_back_caption') }}</span>
                        </div>

                        <div class="absolute right-0 top-0">
                            <img src="/assets/greeting_banner.svg" alt="banner" class="hidden md:block">
                            <img src="/assets/greeting_banner_sm.svg" alt="banner_sm" class="block md:hidden">
                        </div>
                    </div>

                    <!-- overview data -->
                    <div
                        class="grid gap-5 w-full"
                        :class="{
                        'grid-cols-2': user.role === 'agent',
                        'grid-cols-2 xl:grid-cols-4': user.role === 'member'
                    }"
                    >
                        <div
                            class="flex flex-col justify-center items-start gap-4 px-3 md:px-6 py-5 md:py-7 rounded-lg w-full shadow-card bg-white min-w-[140px] md:min-w-[240px] xl:min-w-[200px]"
                            :class="item.borderColor"
                            v-for="(item, index) in dataOverviews"
                            :key="index"
                        >
                            <component :is="item.icon" class="w-9 h-9 grow-0 shrink-0" />
                            <div class="flex flex-col items-start gap-4 w-full">
                                <span class="text-gray-700 text-xs md:text-sm font-medium">{{ item.label }}</span>
                                <div class="text-gray-950 text-md md:text-xl font-semibold">
                                    $ {{ formatAmount(item.total) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="user.role === 'agent'"
                    class="flex flex-col p-6 gap-6 items-center self-stretch w-full bg-white"
                >
                    <div class="flex h-9 items-center justify-between self-stretch">
                        <span class="text-gray-950 text-md font-bold">
                            {{ $t('public.agent_rebate') }}
                        </span>
                        <RebateHistory />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-1 gap-6 items-center self-stretch h-full w-full">
                        <!-- rebate earn -->
                        <RebateEarn />

                        <!-- rebate wallet -->
                        <div class="bg-gray-50 flex flex-col p-6 gap-4 xl:gap-0 justify-between items-center self-stretch w-full">
                            <div class="flex flex-col gap-3 items-center justify-center self-stretch w-full">
                                <span class="text-sm text-gray-500">{{ $t('public.available_rebate_balance') }}</span>
                                <span class="text-xxl text-gray-950 font-semibold"> $ <vue3-autocounter ref="counter" :startAmount="0" :endAmount="rebateWallet ? Number(rebateWallet.balance) : 0" :duration="1" separator="," decimalSeparator="." :decimals="2" :autoinit="true" /></span>
                            </div>
                            <RebateWalletAction
                                :rebateWallet="rebateWallet"
                                :terms="terms"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- posts -->
            <ForumPost
                :postCounts="postCounts"
                :authorName="authorName"
            />
        </div>

    </AuthenticatedLayout>
</template>
