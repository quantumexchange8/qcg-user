<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import { IconChevronRight } from '@tabler/icons-vue';
import Button from '@/Components/Button.vue';
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
import StatusBadge from '@/Components/StatusBadge.vue';
import {
    DepositIcon,
    WithdrawalIcon,
    RebateIcon,
    AgentGroupIcon,
} from '@/Components/Icons/outline.jsx';
import { computed, ref, watchEffect } from 'vue';
import {generalFormat, transactionFormat} from "@/Composables/index.js";
import Empty from "@/Components/Empty.vue";
import { usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import Vue3Autocounter from 'vue3-autocounter';

const props = defineProps({
    user: Object,
})

const { formatAmount } = transactionFormat();
const { formatRgbaColor } = generalFormat()

const tradingAccounts = ref();
const tradingAccountsLength = ref(0);
const userDetail = ref();
const counterDuration = ref(8);
const depositAmount = ref(9999999);
const withdrawalAmount = ref(9999999);
const rebateAmount = ref(0);
const memberAmount = ref(999);
const agentAmount = ref(999);

const getUserData = async () => {
    try {
        const response = await axios.get(`/network/getUserData?id=${props.user.id}`);

        userDetail.value = response.data.userDetail;
        tradingAccounts.value = response.data.tradingAccounts;
        depositAmount.value = response.data.depositAmount;
        withdrawalAmount.value = response.data.withdrawalAmount;
        memberAmount.value = response.data.memberAmount;
        agentAmount.value = response.data.agentAmount;

        tradingAccountsLength.value = tradingAccounts.value.length;
        counterDuration.value = 1;
    } catch (error) {
        console.error('Error get user data:', error);
    }
};

getUserData();

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getUserData();
    }
});

// data overview
const dataOverviews = computed(() => [
    {
        icon: DepositIcon,
        total: depositAmount.value,
        decimal: 2,
        label: trans('public.total_deposit')+" ($)",
        type: 'member',
    },
    {
        icon: WithdrawalIcon,
        total: withdrawalAmount.value,
        decimal: 2,
        label: trans('public.total_withdrawal')+" ($)",
        type: 'member',
    },
    {
        icon: RebateIcon,
        total: rebateAmount.value,
        decimal: 2,
        label: trans('public.total_rebate_earned')+" ($)",
        type: 'agent',
    },
    {
        icon: AgentGroupIcon,
        total: props.user.role === 'agent' ? memberAmount.value + agentAmount.value : memberAmount.value,
        decimal: 0,
        label: trans('public.total_referree'),
        type: 'member',
    },
]);

const filteredDataOverviews = computed(() => {
    if (props.user.role === 'member') {
        return dataOverviews.value.filter((item) =>
            item.type === 'member'
        );
    }

    return dataOverviews.value;
});


</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.network')">
        <div class="flex flex-col items-center gap-5 self-stretch">
            <!-- Breadcrumb -->
            <div class="flex flex-wrap md:flex-nowrap items-center gap-2 self-stretch">
                <Button
                    external
                    type="button"
                    variant="primary-text"
                    size="sm"
                    href="/network?tab=listing"
                >
                    {{ $t('public.listing') }}
                </Button>
                <IconChevronRight
                    :size="16"
                    stroke-width="1.25"
                />
                <span class="flex px-4 py-2 text-gray-400 items-center justify-center text-sm font-medium">{{ user.first_name }} - {{ $t('public.view_details') }}</span>
            </div>

            <div class="grid grid-cols-1 w-full md:grid-cols-2 gap-5">
                <!-- Profile -->
                <div class="p-3 flex flex-col justify-center items-center gap-3 self-stretch rounded-lg bg-white shadow-card md:py-5 md:px-6 md:gap-6">
                    <div v-if="userDetail" class="flex flex-col gap-4 items-start self-stretch">
                        <div class="w-[120px] h-[120px] overflow-hidden">
                            <div v-if="userDetail.profile_photo">
                                <img :src="userDetail.profile_photo" alt="Profile Photo" />
                            </div>
                            <div v-else>
                                <DefaultProfilePhoto />
                            </div>
                        </div>

                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <div class="text-gray-950 text-xl font-bold">
                                {{ userDetail.name }}
                            </div>
                            <div class="text-gray-700  font-medium">
                                {{ userDetail.email }}
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col gap-4 items-start self-stretch">
                        <div class="animate-pulse w-[120px] h-[120px] overflow-hidden">
                            <DefaultProfilePhoto />
                        </div>

                        <div class="flex flex-col items-start flex-1 gap-1 self-stretch">
                            <div class="w-48">
                                <div class="h-4 bg-gray-200 rounded-full w-44 my-2 md:my-3 md:mx-auto"></div>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full w-20 mb-1"></div>
                        </div>
                    </div>

                    <div class="h-[1px] bg-gray-200 w-full self-stretch"></div>

                    <div class="flex flex-col items-center gap-3 self-stretch md:gap-5 md:flex-1">
                        <div
                            v-if="userDetail"
                            class="grid grid-flow-col grid-cols-2 grid-rows-2 gap-y-2 gap-x-5 items-center self-stretch"
                        >
                            <div class="text-gray-500 ">
                                {{ $t('public.id') }}
                            </div>
                            <div class="truncate flex-1 text-gray-700  font-medium">
                                {{ userDetail.id_number }}
                            </div>
                            <div class="text-gray-500 ">
                                {{ $t('public.phone_number') }}
                            </div>
                            <div class="truncate flex-1 text-gray-700  font-medium">
                                {{ userDetail.dial_code }} {{ userDetail.phone }}
                            </div>
                        </div>
                        <!-- loading right top -->
                        <div
                            v-else
                            class="grid grid-flow-col grid-cols-2 grid-rows-2 gap-y-2 gap-x-5 items-center self-stretch"
                        >
                            <div class="text-gray-500 ">
                                {{ $t('public.id') }}
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full md:w-48 my-1.5"></div>
                            <div class="text-gray-500 ">
                                {{ $t('public.phone_number') }}
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full md:w-48 my-1.5"></div>
                        </div>

                        <div
                            v-if="userDetail"
                            class="grid grid-flow-col grid-cols-2 grid-rows-2 gap-y-2 gap-x-5 items-center self-stretch"
                        >
                            <div class="text-gray-500 ">
                                {{ $t('public.role') }}
                            </div>
                            <div class="flex items-start">
                                <div 
                                    :class="{
                                        'w-2.5 h-2.5 rounded-full': true, 
                                        'bg-orange': userDetail.role === 'agent', 
                                        'bg-info-500': userDetail.role === 'member', 
                                    }"
                                    :title="$t(`public.${userDetail.role}`)">
                                </div>
                            </div>
                            <div class="text-gray-500 ">
                                {{ $t('public.user_group') }}
                            </div>
                            <div class="truncate flex-1 text-gray-700  font-medium">
                                {{ userDetail.upline_name ??'-' }}
                            </div>
                        </div>
                        <!-- loading right bottom -->
                        <div
                            v-else
                            class="grid grid-flow-col grid-cols-2 grid-rows-2 gap-y-2 gap-x-5 items-center self-stretch"
                        >
                            <div class="text-gray-500 ">
                                {{ $t('public.role') }}
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full md:w-48 my-2"></div>
                            <div class="text-gray-500 ">
                                {{ $t('public.user_group') }}
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full w-24 my-2"></div>
                        </div>
                    </div>
                </div>

                <!-- Data Overview -->
                <div
                    class="grid gap-3 grid-cols-2 md:gap-5 w-full"
                >
                    <div
                        v-for="(item, index) in filteredDataOverviews"
                        :key="index"
                        class="flex flex-col rounded-lg bg-white shadow-card justify-center items-center gap-4 px-3 py-7 w-full"
                        :class="[(index % 2 === 0 && index === filteredDataOverviews.length - 1) ? 'col-span-full' : 'col-span-1']"
                    >
                        <component :is="item.icon" class="grow-0 shrink-0" />
                        <div class="flex flex-col items-center gap-1 self-stretch">
                            <span class="text-gray-500 text-xs w-28 truncate sm:w-auto">{{ item.label }}</span>
                            <div class="text-gray-950 text-lg font-semibold">
                                <vue3-autocounter ref="counter" :startAmount="0" :endAmount="item.total" :duration="counterDuration" separator="," decimalSeparator="." :decimals="item.decimal" :autoinit="true" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trading Accounts -->
            <div class="pt-3 flex flex-col items-start gap-5 self-stretch">
                <div class="self-stretch text-gray-950 font-bold">
                    {{ $t('public.all_trading_accounts') }}
                </div>

                <template v-if="tradingAccountsLength > 0 && tradingAccounts">
                    <div class="w-full grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div
                            v-for="(tradingAccount, index) in tradingAccounts"
                            :key="index"
                            class="min-w-[300px] flex flex-col justify-center items-start py-3 pl-4 pr-3 gap-3 flex-grow rounded-lg border-l-8 border-info-400 bg-white shadow-toast"
                            :style="{'borderColor': `#${tradingAccount.account_type_color}`}"
                        >
                            <div class="flex flex-wrap items-center content-center gap-4 md:h-[28px] self-stretch">
                                <span class="text-gray-950 font-semibold md:text-lg self-stretch">
                                    #{{ tradingAccount.meta_login }}
                                </span>
                                <div
                                    class="flex px-2 py-1 justify-center items-center text-xs font-semibold hover:-translate-y-1 transition-all duration-300 ease-in-out rounded"
                                    :style="{
                                        backgroundColor: formatRgbaColor(tradingAccount.account_type_color, 0.15),
                                        color: `#${tradingAccount.account_type_color}`,
                                    }"
                                >
                                    {{ $t(`public.${tradingAccount.account_type}`) }}
                                </div>
                            </div>

                            <div class="flex items-center content-center gap-2 self-stretch flex-wrap">
                                <div class="flex flex-col gap-1 items-start flex-1">
                                    <div class="text-gray-500 text-xs">
                                        {{ $t('public.balance') }}&nbsp;($)
                                    </div>
                                    <div class="text-gray-950 text-sm font-medium">
                                        $ {{ formatAmount(tradingAccount.balance) }}
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1 items-start flex-1">
                                    <div class="text-gray-500 text-xs">
                                        {{ $t('public.equity') }}&nbsp;($)
                                    </div>
                                    <div class="text-gray-950 text-sm font-medium">
                                        $ {{ formatAmount(tradingAccount.equity) }}
                                    </div>
                                </div>
                                <div
                                    v-if="tradingAccount.account_type !== 'Premium Account'"
                                    class="flex flex-col gap-1 items-start flex-1"
                                >
                                    <div class="text-gray-500 text-xs">
                                        {{ $t('public.credit') }}&nbsp;($)
                                    </div>
                                    <div class="text-gray-950 text-sm font-medium">
                                        $ {{ formatAmount(tradingAccount.credit) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <Empty :message="$t('public.trading_account_empty_caption')"/>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
