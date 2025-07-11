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
    DashboardPointIcon,
} from '@/Components/Icons/outline.jsx';
import {IconReport, IconDots, IconQrcode, IconCopy} from '@tabler/icons-vue';
import {trans} from "laravel-vue-i18n";
import RebateWalletAction from "@/Pages/Dashboard/Partials/RebateWalletAction.vue";
import RebateEarn from "@/Pages/Dashboard/Partials/RebateEarn.vue";
import RebateHistory from "@/Pages/Dashboard/Partials/RebateHistory.vue";
import Vue3Autocounter from "vue3-autocounter";
import Account from "@/Pages/Accounts/Account.vue";
import Carousel from 'primevue/carousel';
import Dialog from 'primevue/dialog';
import Checkbox from 'primevue/checkbox';
import QrcodeVue from 'qrcode.vue'
import Tag from "primevue/tag";
import InputText from "primevue/inputtext";

const props = defineProps({
    announcements: Object,
})

const user = usePage().props.auth.user;
const { formatAmount } = transactionFormat();
const personalTotalDeposit = ref(0);
const personalTotalWithdrawal = ref(0);
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
    {
        icon: TradeLotIcon,
        total: groupTotalTradeLot.value,
        label: user.role === 'member' ? trans('public.total_trade_lots') : trans('public.group_total_trade_lots'),
        borderColor: 'border-green',
        type: 'trade_lots',
        route: user.role === 'member' ? '' : 'report',
    },
    {
        icon: DashboardPointIcon,
        total: groupTotalTradePoints.value,
        // label: user.role === 'member' ? trans('public.total_trade_volume') : trans('public.group_total_trade_volume'),
        label: user.role === 'member' ? trans('public.personal_trade_points') + ' (tp)': trans('public.personal_trade_points') + ' (tp)',
        borderColor: 'border-green',
        type: 'trade_points',
        route: 'rewards',
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
        personalTotalDeposit.value = response.data.personalTotalDeposit
        personalTotalWithdrawal.value = response.data.personalTotalWithdrawal
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
const checkboxDaily = ref(false);
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

  if (currentAnnouncement.value?.popup_login === 'first' || (currentAnnouncement.value?.popup_login === 'every' && checkboxDaily)) {
    // console.log(currentAnnouncement.value.id)
    await axios.post('/dashboard/markAsViewed', {
        announcement_id: currentAnnouncement.value.id,
    })
  }

  // Slight delay before showing the next popup
  setTimeout(() => {
    checkboxDaily.value = false;
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

const referralDialog = ref(false)
const tooltipText = ref('copy')
const qrcodeContainer = ref();
const registerLink = ref(`${window.location.origin}/sign_up/${usePage().props.auth.user.referral_code}`);

const copyToClipboard = (text) => {
    const textToCopy = text;

    const textArea = document.createElement('textarea');
    document.body.appendChild(textArea);

    textArea.value = textToCopy;
    textArea.select();

    try {
        const successful = document.execCommand('copy');

        tooltipText.value = 'copied';
        setTimeout(() => {
            tooltipText.value = 'copy';
        }, 1500);
    } catch (err) {
        console.error('Copy to clipboard failed:', err);
    }

    document.body.removeChild(textArea);
}

const downloadQrCode = () => {
    const canvas = qrcodeContainer.value.querySelector("canvas");
    const link = document.createElement("a");
    link.download = "qr-code.png";
    link.href = canvas.toDataURL("image/png");
    link.click();
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.dashboard')">
        <div class="flex flex-col gap-3 md:gap-5 items-center self-stretch">
            <div class="flex flex-col items-center gap-3 md:gap-5 self-stretch w-full">
                <div class="flex flex-col gap-3 md:gap-5 items-center self-stretch w-full">
                    <!-- greeting card -->
                    <div v-if="user.role==='agent'" class="flex flex-col justify-between bg-white rounded-lg h-[150px] md:h-[170px] shadow-box relative overflow-hidden px-3 py-[17px] md:px-6 md:py-[22px] items-start w-full">
                        <div class="flex flex-col gap-1 items-start justify-center w-[calc(100%-50px)]">
                            <span class="md:text-base text-sm text-gray-950 font-bold line-clamp-1">{{ $t('public.welcome_back', {'name': user.first_name}) }}</span>
                            <span class="md:text-sm text-xs text-gray-700 line-clamp-2">{{ $t('public.welcome_back_caption') }}</span>
                        </div>
                        <div class="flex flex-row gap-5 smd:gap-10 md:gap-16 w-[calc(100%-80px)]">
                            <div class="flex flex-col w-[107px] h-10 md:w-[165px] md:h-12 justify-center items-start pl-[6px] rounded border-l-4 bg-white border-primary-500">
                                <span class="text-xxs md:text-sm text-gray-500 line-clamp-1">{{ $t('public.deposit') }}:</span>
                                <span class="text-sm smd:text-base md:text-lg font-semibold text-primary-500">$ {{ formatAmount(personalTotalDeposit) }}</span>
                            </div>
                            <div class="flex flex-col w-[115px] h-10 md:w-[165px] md:h-12 justify-center items-start pl-[6px] rounded border-l-4 bg-white border-error-600">
                                <span class="text-xxs md:text-sm text-gray-500 line-clamp-1">{{ $t('public.withdrawal') }}:</span>
                                <span class="text-sm smd:text-base md:text-lg font-semibold text-error-600">$ {{ formatAmount(personalTotalWithdrawal) }}</span>
                            </div>
                        </div>

                        <div class="absolute right-0 top-0 z-0 block md:hidden">
                            <img src="/assets/agent_greeting_banner_sm.svg" alt="mobile banner" class="w-full h-full object-contain">
                        </div>

                        <div class="absolute right-0 top-0 z-0 hidden md:block">
                            <img src="/assets/agent_greeting_banner.svg" alt="desktop banner" class="w-full h-full object-contain">
                        </div>
                    </div>
                    <div v-else class="bg-white rounded-lg h-[60px] md:h-20 shadow-box relative overflow-hidden px-3 py-[10px] md:px-6 md:py-4 items-center w-full">
                        <div class="flex flex-col gap-1 items-start justify-center w-[calc(100%-50px)]">
                            <span class="md:text-base text-sm text-gray-950 font-bold line-clamp-1">{{ $t('public.welcome_back', {'name': user.first_name}) }}</span>
                            <span class="md:text-sm text-xs text-gray-700 line-clamp-1">{{ $t('public.member_welcome_back_caption') }}</span>
                        </div>

                        <div class="absolute right-0 top-0 z-0 block md:hidden">
                            <img src="/assets/greeting_banner_sm.svg" alt="mobile banner" class="w-full h-full object-contain">
                        </div>

                        <div class="absolute right-0 top-0 z-0 hidden md:block">
                            <img src="/assets/greeting_banner.svg" alt="desktop banner" class="w-full h-full object-contain">
                        </div>
                    </div>

                    <!-- overview data -->
                    <div
                        class="grid gap-3 md:gap-5 w-full grid-cols-2 xl:grid-cols-3"
                    >
                        <div
                            class="flex flex-col justify-center p-2 md:px-6 md:py-4 rounded-lg w-full shadow-card bg-white md:min-w-[240px] xl:min-w-[200px]"
                            :class="item.borderColor, { 'cursor-pointer !pb-0': isClickable(item.route) }"
                            v-for="(item, index) in dataOverviews"
                            :key="index"
                            @click="isClickable(item.route) ? navigateWithQueryParams(item.route, item.query, item.type) : null"
                        >
                            <div class="flex flex-row justify-between items-center gap-2">
                                <component :is="item.icon" class="w-6 h-6 md:w-9 md:h-9 grow-0 shrink-0" />
                                <div class="flex flex-col items-end truncate">
                                    <span class="text-gray-500 text-xxs md:text-sm text-right w-full truncate">{{ item.label }}</span>
                                    <div v-if="item.type === 'trade_points'" class="text-gray-950 md:text-lg font-semibold text-right w-full truncate">
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

                    <div class="flex flex-col gap-3 relative overflow-hidden items-center w-full">
                        <div class="flex flex-row items-center justify-between self-stretch">
                            <span class="text-sm md:text-base font-bold text-gray-950">{{ $t('public.highlights') }}</span>
                        </div>
                        <Carousel v-if="pinnedAnnouncements.length > 0" :value="pinnedAnnouncements" 
                            :numVisible="1.43" :numScroll="1" circular :autoplayInterval="3000"
                            :responsiveOptions="responsiveOptions"
                            :showNavigators="false" :showIndicators="false"
                            class="w-full relative">
                            <template #item="slotProps">
                                <div class="flex w-full justify-start">
                                    <div
                                        class="relative w-full h-[170px] smd:h-auto smd:max-h-60 md:h-[250px] md:max-h-none overflow-hidden hover:opacity-80 hover:cursor-pointer 
                                        rounded-[10px] shadow-box"
                                        @click="router.get(route('highlights'))"
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

                <div class="grid grid-cols-2 gap-3 self-stretch h-20 font-semibold md:hidden">
                    <!-- <div class="col-span-1 text-center shadow-box h-full w-full flex items-center justify-center rounded-lg hover:cursor-pointer bg-cover bg-no-repeat overflow-hidden" 
                    :style="{ backgroundImage: `url('/assets/breaking_news.png')` }"
                    @click="router.get(route('forum'))" >
                        {{ $t('public.breaking_news') }}
                    </div> -->
                    <!-- <div class="col-span-1 text-center shadow-box h-full w-full flex items-center justify-center rounded-lg hover:cursor-pointer bg-cover bg-no-repeat overflow-hidden" 
                    :style="{ backgroundImage: `url('/assets/referral.png')` }"
                    @click="referralDialog = true">
                        {{ $t('public.referral') }}
                    </div> -->
                    <div
                        class="relative col-span-1 text-center shadow-box h-full w-full flex items-center justify-center rounded-lg hover:cursor-pointer overflow-hidden"
                        @click="router.get(route('forum'))"
                    >
                        <img
                        src="/assets/breaking_news.png"
                        alt="cover"
                        class="w-full h-full object-cover"
                        />
                        <div
                            class="absolute inset-x-3 flex items-center justify-start text-gray-950 font-semibold w-2/3 text-left"
                        >
                            {{ $t('public.breaking_news') }}
                        </div>
                    </div>
                    <div
                        class="relative col-span-1 text-center shadow-box h-full w-full flex items-center justify-center rounded-lg hover:cursor-pointer overflow-hidden"
                        @click="referralDialog = true"
                    >
                        <img
                        src="/assets/referral.png"
                        alt="cover"
                        class="w-full h-full object-cover"
                        />
                        <div
                            class="absolute inset-x-3 flex items-center justify-start text-gray-950 font-semibold w-2/3 text-left"
                        >
                            {{ $t('public.referral') }}
                        </div>
                    </div>
                </div>

                <div
                    v-if="user.role === 'agent'"
                    class="flex flex-col py-2 px-3 md:p-6 gap-3 items-center self-stretch w-full bg-white rounded-lg shadow-box"
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
            <img :src="currentAnnouncement.thumbnail" alt="announcement_image" class="w-full h-[170px] smd:h-full smd:max-h-72 md:h-[310.5px] md:max-h-none" />

            <span class="text-sm md:text-lg font-bold text-gray-950">{{ currentAnnouncement.title }}</span>

            <!-- need to ask nic about this content if got html tag -->
            <span class="text-sm md:text-md font-regular text-gray-950 whitespace-pre-line" v-html="currentAnnouncement.content"></span>

        </div>
        <template #footer>
            <div class="flex flex-col gap-2 w-full pt-4">
                <label v-if="currentAnnouncement.popup_login && currentAnnouncement.popup_login==='every'" class="flex items-center gap-2">
                    <Checkbox binary v-model="checkboxDaily" class="w-4 h-4 flex-shrink-0" />
                    <span class="text-gray-500 text-sm">{{ $t('public.disable_popup') }}</span>
                </label>

                <Button 
                    type="button"
                    variant="primary-flat"
                    size="base"
                    class="w-full"
                    @click="handlePopupClose" 
                >
                    {{ $t('public.close') }}
                </Button>
            </div>
        </template>
    </Dialog>

    <Dialog
        v-model:visible="referralDialog"
        modal
        :header="$t('public.referral_qr_code')"
        class="dialog-xs md:dialog-md"
    >
        <div class="flex flex-col pt-4 md:pt-6 gap-6 md:gap-8 items-center self-stretch">
            <span class="text-xs md:text-base text-gray-500">{{ $t('public.referral_qr_caption_1') }}</span>

            <!-- qr code -->
            <div class="flex flex-col items-center gap-5 self-stretch">
                <div
                    ref="qrcodeContainer">
                    <qrcode-vue
                        ref="qrcode"
                        :value="registerLink"
                        :margin="2"
                        :size="200"
                    />
                </div>
                <Button
                    type="button"
                    variant="primary-flat"
                    size="lg"
                    @click="downloadQrCode"
                >
                    {{ $t('public.download_qr_caption') }}
                </Button>
            </div>

            <div class="flex gap-3 items-center self-stretch">
                <div class="h-[1px] bg-gray-200 rounded-[5px] w-full"></div>
                <div class="text-xs md:text-sm text-gray-500 text-center min-w-[145px] md:w-full">{{ $t('public.referral_qr_caption_2') }}</div>
                <div class="h-[1px] bg-gray-200 rounded-[5px] w-full"></div>
            </div>

            <div class="flex gap-3 items-center self-stretch relative">
                <InputText
                    v-model="registerLink"
                    class="truncate w-full"
                    readonly
                />
                <Tag
                    v-if="tooltipText === 'copied'"
                    class="absolute -top-7 -right-3"
                    severity="contrast"
                    :value="$t(`public.${tooltipText}`)"
                ></Tag>
                <Button
                    type="button"
                    variant="gray-text"
                    iconOnly
                    pill
                    @click="copyToClipboard(registerLink)"
                >
                    <IconCopy size="20" color="#667085" stroke-width="1.25" />
                </Button>
            </div>
        </div>
    </Dialog>
</template>

<style scoped>
:deep(.p-carousel-items-content) {
    scroll-padding-left: 11%;
}
</style>

