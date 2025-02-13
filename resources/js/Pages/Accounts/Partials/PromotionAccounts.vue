<script setup>
import { IconAlertCircleFilled, IconChecks } from '@tabler/icons-vue';
import {ref, onMounted, watchEffect, computed, h} from "vue";
import StatusBadge from '@/Components/StatusBadge.vue';
import Action from "@/Pages/Accounts/Partials/Action.vue";
import ActionButton from "@/Pages/Accounts/Partials/ActionButton.vue";
import Empty from '@/Components/Empty.vue';
import {generalFormat, transactionFormat} from "@/Composables/index.js";
import {usePage} from "@inertiajs/vue3";
import Button from "@/Components/Button.vue";
import {useForm} from "@inertiajs/vue3";
import dayjs from "dayjs";
import Dialog from 'primevue/dialog';
import TermsAndCondition from "@/Components/TermsAndCondition.vue";
import {trans} from "laravel-vue-i18n";
import { useConfirm } from 'primevue/useconfirm';

const isLoading = ref(false);
const accounts = ref([]);
const accountType = ref('promotion');
const { formatAmount } = transactionFormat();
const { formatRgbaColor } = generalFormat();
const data = ref({});
const showClaimDialog = ref(false);

// Fetch live accounts from the backend
const fetchLiveAccounts = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/accounts/getLiveAccount?accountType=${accountType.value}`);
        accounts.value = response.data;
    } catch (error) {
        console.error('Error fetching live accounts:', error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchLiveAccounts();
});

// Function to get the button variant (color) based on the account state
const statusVariant = (account) => {
    if (account.claimable_status) {
        return 'primary-button';
    } else {
        return 'gray-button';
    }
};

const form = useForm({
    user_id: '',
    account_id: '',
    meta_login: '',
    promotion_type: '',
    bonus_type: '',
    amount: 0,
    claim_amount: 0,
})

const submitForm = (account) => {
    form.user_id = account.user_id;
    form.account_id = account.id;
    form.meta_login = account.meta_login;
    form.promotion_type = account.promotion_type;
    form.bonus_type = account.bonus_type;
    form.amount = account.claimable_amount;
//     if (account.achieved_amount > account.target_amount) {
//         form.amount = account.target_amount;
//     }
//     else {
//         form.amount = account.achieved_amount;
//    }

    form.post(route('accounts.claim_bonus'), {
        onSuccess: () => {
            closeDialog();
        }
    });
}

const openDialog = (accountData) => {
    showClaimDialog.value = true;
    data.value = accountData;
}

const closeDialog = () => {
    showClaimDialog.value = false;
}

const confirm = useConfirm();
const confirmationBox = (accountType, details) => {
    // console.log("ClaimBonusConfirmation executed", formData);
    const messages = {
        bonus: {
            group: 'headless',
            color: 'primary',
            icon: h(IconChecks),
            header: trans('public.bonus_request_submitted'),
            message: trans('public.bonus_request_message'),
            actionType: 'bonus',
            acceptButton: trans('public.alright'),
            action: () => {
                confirm.close();
            },
            content: () => h('div', { class: 'flex flex-col p-3 gap-3 bg-gray-50' }, [
                h('div', { class: 'flex flex-col md:flex-row gap-1 flex-wrap' }, [
                    h('p', { class: 'text-sm text-gray-500 min-w-[140px]' }, trans('public.date')),
                    h('p', { class: 'text-sm font-medium text-gray-950' }, dayjs(details.created_at).format('YYYY/MM/DD')),
                ]),
                h('div', { class: 'flex flex-col md:flex-row gap-1 flex-wrap' }, [
                    h('p', { class: 'text-sm text-gray-500 min-w-[140px]' }, trans('public.from')),
                    h('p', { class: 'text-sm font-medium text-gray-950' }, details.to_meta_login),
                ]),
                h('div', { class: 'flex flex-col md:flex-row gap-1 flex-wrap' }, [
                    h('p', { class: 'text-sm text-gray-500 min-w-[140px]' }, trans('public.requested_bonus')),
                    h('p', { class: 'text-sm font-medium text-gray-950' }, `$ ${formatAmount(details.transaction_amount)}`),
                ])
            ])
        },
        withdrawal: {
            group: 'headless',
            color: 'primary',
            icon: h(IconChecks),
            header: trans('public.withdrawal_request_submitted'),
            message: trans('public.withdrawal_request_message'),
            actionType: 'withdrawal',
            acceptButton: trans('public.alright'),
            action: () => {
                confirm.close();
            },
            content: () => h('div', { class: 'flex flex-col p-3 gap-3 bg-gray-50' }, [
                h('div', { class: 'flex flex-col md:flex-row gap-1 flex-wrap' }, [
                    h('p', { class: 'text-sm text-gray-500 min-w-[140px]' }, trans('public.date')),
                    h('p', { class: 'text-sm font-medium text-gray-950' }, dayjs(details.created_at).format('YYYY/MM/DD')),
                ]),
                h('div', { class: 'flex flex-col md:flex-row gap-1 flex-wrap' }, [
                    h('p', { class: 'text-sm text-gray-500 min-w-[140px]' }, trans('public.transaction_id')),
                    h('p', { class: 'text-sm font-medium text-gray-950' }, details.transaction_number),
                ]),
                h('div', { class: 'flex flex-col md:flex-row gap-1 flex-wrap' }, [
                    h('p', { class: 'text-sm text-gray-500 min-w-[140px]' }, trans('public.from')),
                    h('p', { class: 'text-sm font-medium text-gray-950' }, details.from_meta_login),
                ]),
                h('div', { class: 'flex flex-col md:flex-row gap-1 flex-wrap' }, [
                    h('p', { class: 'text-sm text-gray-500 min-w-[140px]' }, trans('public.requested_amount')),
                    h('p', { class: 'text-sm font-medium text-gray-950' }, `$ ${formatAmount(details.transaction_amount)}`),
                ]),
                h('div', { class: 'flex flex-col md:flex-row gap-1 flex-wrap' }, [
                    h('p', { class: 'text-sm text-gray-500 min-w-[140px]' }, trans('public.receiving_address')),
                    h('p', { class: 'text-sm font-medium text-gray-950' }, details.to_wallet_address),
                ])
            ])
        },
    };

    const { group, color, icon, header, message, actionType, acceptButton, action, content } = messages[accountType];

    confirm.require({
        group,
        color,
        icon,
        header,
        message,
        actionType,
        acceptButton,
        accept: action,
        content: content()
    });

}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        fetchLiveAccounts();
    }

    if (usePage().props.notification !== null) {
        confirmationBox(usePage().props.notification.type, usePage().props.notification.details);
        fetchLiveAccounts();
        usePage().props.notification = null;
    }
});
</script>

<template>
    <div
        v-if="isLoading"
        class="flex flex-col justify-center items-center py-3 pl-4 pr-3 gap-5 flex-grow md:pr-6 rounded-lg border-l-8 bg-white shadow-card w-1/2 animate-pulse"
    >
        <div class="flex items-center gap-5 self-stretch">
            <div class="w-32 h-3 bg-gray-200 rounded-full my-2"></div>
            <div
                class="flex px-2 py-1 justify-center items-center text-xs font-semibold hover:-translate-y-1 transition-all duration-300 ease-in-out rounded"
            >
                <div class="w-20 h-2.5 bg-gray-200 rounded-full my-2"></div>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-2 self-stretch xl:grid-cols-4">
            <div class="w-full flex items-start gap-1 flex-col">
                <span class="w-16 text-gray-500 text-xs">{{ $t('public.balance') }}:</span>
                <div class="w-20 h-2 bg-gray-200 rounded-full my-2"></div>
            </div>
            <div class="w-full flex items-start gap-1 flex-col">
                <span class="w-16 text-gray-500 text-xs">{{ $t('public.equity') }}:</span>
                <div class="w-20 h-2 bg-gray-200 rounded-full my-2"></div>
            </div>
            <div class="w-full flex items-start gap-1 flex-col">
                <span class="w-16 text-gray-500 text-xs">{{ $t('public.credit') }}:</span>
                <div class="w-20 h-2 bg-gray-200 rounded-full my-2"></div>
            </div>
            <div class="w-full flex items-start gap-1 flex-col">
                <span class="w-16 text-gray-500 text-xs">{{ $t('public.leverage') }}:</span>
                <div class="w-20 h-2 bg-gray-200 rounded-full my-2"></div>
            </div>
        </div>
    </div>

    <div
        v-if="!isLoading && accounts.length > 0"
        class="w-full grid grid-cols-1 gap-5 md:grid-cols-2"
    >
        <div
            v-for="account in accounts"
            :key="account.id"
            class="flex flex-col justify-center items-center py-3 pl-4 pr-3 gap-3 flex-grow md:pr-6 rounded-lg border-l-8 bg-white shadow-card w-full"
            :style="{'borderColor': `#${account.account_type_color}`}"
            :class="{'opacity-50': account.is_active === 'inactive'}"
        >
            <div class="flex items-center gap-5 self-stretch">
                <div class="flex items-center content-center gap-y-2 gap-x-4 flex-grow">
                    <span class="text-gray-950 font-semibold text-lg">#{{ account.meta_login }}</span>
                    <div
                        class="flex px-2 py-1 justify-center items-center text-xs rounded-sm text-white"
                        :style="{
                            backgroundColor: `#${account.account_type_color}`,
                        }"
                    >
                        {{ $t(`public.${account.account_type}`) }}
                    </div>
                    <IconAlertCircleFilled :size="20" stroke-width="1.25" class="text-error-500" v-if="account.is_active === 'inactive'" v-tooltip.top="$t('public.account_inactive_warning')" />
                </div>
                <Action :account="account" :type="accountType" />
            </div>
            <div class="grid grid-cols-2 gap-2 self-stretch xl:grid-cols-4">
                <div class="w-full flex items-start gap-1 flex-col">
                    <span class="w-16 text-gray-500 text-xs">{{ $t('public.balance') }}:</span>
                    <span class="text-gray-950 text-xs font-medium">$&nbsp;{{ formatAmount(account.balance) }}</span>
                </div>
                <div class="w-full flex items-start gap-1 flex-col">
                    <span class="w-16 text-gray-500 text-xs">{{ $t('public.equity') }}:</span>
                    <span class="text-gray-950 text-xs font-medium">$&nbsp;{{ formatAmount(account.equity) }}</span>
                </div>
                <div class="w-full flex items-start gap-1 flex-col">
                    <span class="w-16 text-gray-500 text-xs">{{ $t('public.credit') }}:</span>
                    <span class="text-gray-950 text-xs font-medium">$&nbsp;{{ formatAmount(account.credit) }}</span>
                </div>
                <div class="w-full flex items-start gap-1 flex-col">
                    <span class="w-16 text-gray-500 text-xs">{{ $t('public.leverage') }}:</span>
                    <span class="text-gray-950 text-xs font-medium">1:{{ account.leverage }}</span>
                </div>
            </div>
            <div class="flex justify-end items-center gap-3 self-stretch">
                <ActionButton :account="account" :type="accountType"/>
            </div>
            <div class="flex flex-col items-center self-stretch bg-gray-50 rounded-xl p-4 gap-2 shadow-inner">
                <div class="flex items-center self-stretch justify-between">
                    <div class="flex items-start gap-y-1 flex-col">
                        <span class="text-gray-950 font-semibold text-sm">{{ account.promotion_title }}</span>
                        <span class="text-gray-700 text-xs">{{ account.promotion_description }}</span> 
                    </div>
                    <Button
                        v-if="!(account.promotion_type === 'trade_volume' && account.is_claimed == null)"
                        class="rounded-xl"
                        size="sm"
                        :variant="statusVariant(account)"
                        :disabled="!account.claimable_status"
                        @click="openDialog(account)"
                    >
                        {{ $t(`public.${account.is_claimed}`) }}
                    </Button>
                </div>
                <div class="flex flex-col items-center self-stretch w-full">
                    <div class="flex flex-row w-full">
                        <div class="w-full bg-white rounded-[32px] my-[10px] flex-1 relative z-0 p-2 shadow-inner overflow-hidden">
                            <div class="h-full rounded-[32px] bg-gradient-to-r from-green-400 to-green-600"
                                :style="{
                                    width: `${Math.min((account.achieved_amount / account.target_amount) * 100, 100)}%`,
                                    boxShadow: `inset 0px -2px 4px #00000040, inset 2px 2px 2px #9BDA9E`
                                }">
                            </div>
                        </div>
                        <div class="flex justify-center items-center w-[52px] h-[52px] bg-white rounded-full pt-1 pl-1 -ml-[26px] shadow-inner overflow-hidden">
                            <img src="/assets/Coin.png" alt="coin" class="z-10">
                        </div>
                    </div>
                    <div class="flex items-center self-stretch justify-between pr-12">
                        <span v-if="account.promotion_type==='trade_volume'" class="text-xs">{{ $t('public.filled_volume') }}: <span class="font-medium">{{formatAmount(account.achieved_amount)}}Ł/{{formatAmount(account.target_amount)}}Ł</span></span>
                        <span v-else class="text-xs">{{ $t(`public.${account.bonus_type}_unlocked`) }}: <span class="font-medium">${{formatAmount(account.achieved_amount ?? 0)}}/${{formatAmount(account.target_amount)}}</span></span>
                        <!-- compute for date -->
                        <span v-if="account.days_left > 0" class="text-xs font-medium">{{ account.days_left }} day(s) left</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Empty
        v-else-if="!isLoading && accounts.length === 0"
        :title="$t('public.empty_trading_account_title')"
        :message="$t('public.empty_trading_account_message')"
    />

    <Dialog v-model:visible="showClaimDialog" :header="$t('public.claim_bonus')" modal class="dialog-xs sm:dialog-sm">
        <form @submit.prevent="submitForm()">
            <div class="flex flex-col py-6 gap-5">
                <div class="flex flex-col gap-1 px-8 py-3 bg-gray-100">
                    <span class="text-gray-500 text-center text-xs">#{{ data.meta_login }} - {{ $t('public.claimable_credit_bonus') }}</span>
                    <span class="text-gray-950 text-center text-lg font-semibold">$ {{ formatAmount(data.claimable_amount) }}</span>
                </div>

                <div class="self-stretch">
                    <div class="text-gray-500 text-xs">{{ $t('public.acknowledgement') }}
                        <TermsAndCondition
                        />.
                    </div>
                </div>
            </div>
            <div class="flex justify-end items-center pt-5 gap-4 self-stretch sm:pt-6">
                <Button
                    type="button"
                    variant="gray-tonal"
                    class="w-full"
                    @click.prevent="closeDialog()"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    variant="primary-flat"
                    class="w-full"
                    @click.prevent="submitForm(data)"
                    :disabled="form.processing"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </form>
    </Dialog>

</template>
