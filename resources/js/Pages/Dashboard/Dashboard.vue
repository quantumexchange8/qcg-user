<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import Button from '@/Components/Button.vue';
import {usePage, router} from "@inertiajs/vue3";
import {transactionFormat} from "@/Composables/index.js";
import {computed, ref, watch, watchEffect, onMounted} from "vue";
import {
    DepositIcon,
    WithdrawalIcon,
    NetBalanceIcon,
    NetAssetIcon,
    TradeLotIcon,
    TradeVolumeIcon,
} from '@/Components/Icons/outline.jsx';
import {IconReport, IconDots} from '@tabler/icons-vue';
import {trans} from "laravel-vue-i18n";
import RebateWalletAction from "@/Pages/Dashboard/Partials/RebateWalletAction.vue";
import RebateEarn from "@/Pages/Dashboard/Partials/RebateEarn.vue";
import RebateHistory from "@/Pages/Dashboard/Partials/RebateHistory.vue";
import Vue3Autocounter from "vue3-autocounter";
import Account from "@/Pages/Accounts/Account.vue";
import Carousel from 'primevue/carousel';
import Dialog from 'primevue/dialog';

const props = defineProps({
    announcements: Object,
})

const user = usePage().props.auth.user;
const { formatAmount } = transactionFormat();
const groupTotalDeposit = ref(0);
const groupTotalWithdrawal = ref(0);
const groupTotalNetBalance = ref(0);
const groupTotalAsset = ref(0);
const groupTotalTradeLot = ref(0);
const groupTotalTradePoints = ref(0);
const rebateWallet = ref();
const pinnedAnnouncements = ref([]);

// data overview
const dataOverviews = computed(() => [
    {
        icon: DepositIcon,
        total: groupTotalDeposit.value,
        label: user.role === 'member' ? trans('public.total_deposit') : trans('public.group_total_deposit'),
        borderColor: 'border-green',
        type: 'deposit',
        route: user.role === 'member' ? 'transaction' : 'report',
        query: 'group_transaction',
    },
    {
        icon: WithdrawalIcon,
        total: groupTotalWithdrawal.value,
        label: user.role === 'member' ? trans('public.total_withdrawal') : trans('public.group_total_withdrawal'),
        borderColor: 'border-pink',
        type: 'withdrawal',
        route: user.role === 'member' ? 'transaction' : 'report',
        query : 'group_transaction',
    },
    {
        icon: TradeLotIcon,
        total: groupTotalTradeLot.value,
        label: user.role === 'member' ? trans('public.total_trade_lots') : trans('public.group_total_trade_lots'),
        borderColor: 'border-green',
        type: 'trade_lots',
        route: user.role === 'member' ? '' : 'report',
    },
    {
        icon: TradeVolumeIcon,
        total: groupTotalTradePoints.value,
        // label: user.role === 'member' ? trans('public.total_trade_volume') : trans('public.group_total_trade_volume'),
        label: user.role === 'member' ? trans('public.personal_trade_points') : trans('public.personal_trade_points'),
        borderColor: 'border-green',
        type: 'trade_volume',
        route: '',
    },
    {
        icon: NetBalanceIcon,
        total: groupTotalNetBalance.value,
        label: user.role === 'member' ? trans('public.total_net_balance') : trans('public.group_total_net_balance'),
        borderColor: 'border-[#FEDC32]',
        type: 'net_balance',
        route: '',
    },
    {
        icon: NetAssetIcon,
        total: groupTotalAsset.value,
        label: user.role === 'member' ? trans('public.total_asset') : trans('public.group_total_asset'),
        borderColor: 'border-indigo',
        type: 'asset',
        route: '',
    },
]);

// Function to navigate with query parameters
const navigateWithQueryParams = (route, query, subquery) => {
    if (user.role === 'member') {
        router.visit(route, {
            method: 'get',
            data: { type: subquery },
            preserveState: true, 
            replace: true, // Prevents duplicate history entries
        });
    }
    else {
        router.visit(route, {
            method: 'get',
            data: { tab: query , type: subquery},
            preserveState: true, 
            replace: true, // Prevents duplicate history entries
        });
    }
};

const isClickable = (route) => {
    return !!route;
};

const getDashboardData = async () => {
    try {
        const response = await axios.get('/dashboard/getDashboardData');
        rebateWallet.value = response.data.rebateWallet
        pinnedAnnouncements.value = response.data.pinnedAnnouncements
        groupTotalDeposit.value = response.data.groupTotalDeposit
        groupTotalWithdrawal.value = response.data.groupTotalWithdrawal
        groupTotalNetBalance.value = response.data.groupTotalNetBalance
        groupTotalAsset.value = response.data.groupTotalAsset
        groupTotalTradeLot.value = response.data.groupTotalTradeLot
        groupTotalTradePoints.value = response.data.groupTotalTradePoints
    } catch (error) {
        console.error('Error pending counts:', error);
    }
};

const isMounted = ref(false);
// const autoplayInterval = ref(3000);
const announcementsQueue = ref([...props.announcements]) // clone to avoid mutation
const currentAnnouncement = ref(null)
const showPopup = ref(false)

onMounted(() => {
    if (announcementsQueue.value.length) {
        showNextAnnouncement();
    }

    // setTimeout(() => {
    //     autoplayInterval.value = 3000;
    // }, 200); 
});

getDashboardData();

watchEffect(() => {
    if (usePage().props.toast !== null || usePage().props.notification !== null) {
        getDashboardData();
    }
});

function showNextAnnouncement() {
  if (announcementsQueue.value.length) {
    currentAnnouncement.value = announcementsQueue.value.shift()
    showPopup.value = true
  }
}

async function handlePopupClose() {
  showPopup.value = false;

  if (currentAnnouncement.value?.popup_login === 'first') {
    // console.log(currentAnnouncement.value.id)
    await axios.post('/dashboard/markAsViewed', {
        announcement_id: currentAnnouncement.value.id,
    })
  }

  // Slight delay before showing the next popup
  setTimeout(() => {
    showNextAnnouncement()
  }, 300)
}

const responsiveOptions = ref([
    {
        breakpoint: '9999px',
        numVisible: 2.22,
        numScroll: 1
    },
    {
        breakpoint: '1280px',
        numVisible: 1.43,
        numScroll: 1
    },
    {
        breakpoint: '768px',
        numVisible: 1,
        numScroll: 1
    },
]);

</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.dashboard')">
        <div class="flex flex-col gap-3 md:gap-5 items-center self-stretch">
            <div class="flex flex-col items-center gap-3 md:gap-5 self-stretch w-full">
                <div class="flex flex-col gap-3 md:gap-5 items-center self-stretch w-full">
                    <!-- greeting card -->
                    <div class="bg-white rounded-lg h-[60px] md:h-20 shadow-card relative overflow-hidden px-3 py-[10px] md:px-6 md:py-4 items-center w-full">
                        <div class="flex flex-col gap-1 items-start justify-center w-[calc(100%-40px)]">
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
                        class="grid gap-3 md:gap-5 w-full grid-cols-2 xl:grid-cols-2"
                    >
                        <div
                            class="flex flex-col justify-center p-2 md:px-6 md:py-4 rounded-lg w-full shadow-card bg-white min-w-[140px] md:min-w-[240px] xl:min-w-[200px]"
                            :class="item.borderColor, { 'cursor-pointer !pb-0': isClickable(item.route) }"
                            v-for="(item, index) in dataOverviews"
                            :key="index"
                            @click="isClickable(item.route) ? navigateWithQueryParams(item.route, item.query, item.type) : null"
                        >
                            <div class="flex flex-row justify-between items-center gap-2">
                                <component :is="item.icon" class="w-6 h-6 md:w-9 md:h-9 grow-0 shrink-0" />
                                <div class="flex flex-col items-end truncate">
                                    <span class="text-gray-500 text-xxs md:text-sm text-right w-full truncate">{{ item.label }}</span>
                                    <div v-if="item.type === 'trade_volume'" class="text-gray-950 md:text-lg font-semibold text-right w-full truncate">
                                        {{ formatAmount(item.total, 2) }}
                                    </div>
                                    <div v-else-if="item.type === 'trade_lots'" class="text-gray-950 md:text-lg font-semibold text-right w-full truncate">
                                        {{ formatAmount(item.total) }} Ł
                                    </div>
                                    <div v-else class="text-gray-950 md:text-lg font-semibold w-full text-right truncate">
                                        $ {{ formatAmount(item.total) }}
                                    </div>
                                </div>
                            </div>
                            <div v-if="item.route" class="w-full flex justify-center items-center px-2 gap-2 self-stretch">
                                <IconDots class="w-4 h-4 text-gray-400" />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 bg-white rounded-lg shadow-card relative overflow-hidden p-3 md:p-6 items-center w-full">
                        <div class="flex flex-row items-center justify-between self-stretch">
                            <span class="text-sm md:text-base font-bold text-gray-950">{{ $t('public.highlights') }}</span>
                            <Button
                            variant="primary-text"
                            :href="route('highlights')"
                            >
                                {{ $t('public.see_more') }}
                            </Button>
                        </div>
                        <Carousel v-if="pinnedAnnouncements.length > 0" :value="pinnedAnnouncements" 
                            :numVisible="1.43" :numScroll="1" circular :autoplayInterval="3000"
                            :responsiveOptions="responsiveOptions"
                            :showNavigators="false" :showIndicators="false"
                            class="w-full relative">
                            <template #item="slotProps">
                                <div class="w-full flex justify-start">
                                    <div
                                        class="relative w-full h-[170px] md:h-[225px] overflow-hidden"
                                        :class="{ 'bg-black': !slotProps.data.thumbnail }"
                                    >
                                        <!-- Image -->
                                        <img
                                        :src="slotProps.data.thumbnail"
                                        alt="cover"
                                        class="w-full h-full object-fill"
                                        />

                                        <!-- <div
                                            class="absolute inset-0 p-2 flex flex-col items-start overflow-hidden"
                                        >
                                            <div class="mt-auto flex flex-col gap-1 max-h-[112px] w-full overflow-hidden">
                                                <span class="font-semibold text-gray-100 w-full line-clamp-1">
                                                {{ slotProps.data.title }}
                                                </span>
                                                <span class="text-sm text-gray-100 w-full line-clamp-2" v-html="slotProps.data.content">
                                                </span>
                                            </div>
                                        </div> -->
                                    </div>
                                </div>
                            </template>
                        </Carousel>
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
                            <div class="flex flex-col gap-1 items-center justify-center self-stretch w-full max-w-">
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

    <Dialog v-model:visible="showPopup" modal :header="$t('public.announcement')" class="dialog-xs md:dialog-md no-header-border" :closable="false">
        <div class="flex flex-col justify-center items-start gap-8 pb-6 self-stretch">
            <img :src="currentAnnouncement.thumbnail" alt="announcement_image" class="w-full h-[144px] md:h-[310.5px]" />

            <span class="text-lg font-bold text-gray-950">{{ currentAnnouncement.title }}</span>

            <!-- need to ask nic about this content if got html tag -->
            <span class="text-md font-regular text-gray-950 whitespace-pre-line" v-html="currentAnnouncement.content"></span>

        </div>
        <template #footer>
            <Button 
                type="button"
                variant="primary-flat"
                size="base"
                class="w-full"
                @click="handlePopupClose" 
            >
                {{ $t('public.close') }}
            </Button>
        </template>
    </Dialog>
</template>

<style scoped>
:deep(.p-carousel-items-content) {
    scroll-padding-left: 11%;
}
</style>

