<script setup>
import { IconAlertCircleFilled } from '@tabler/icons-vue';
import {ref, onMounted, watchEffect} from "vue";
import StatusBadge from '@/Components/StatusBadge.vue';
import Action from "@/Pages/Accounts/Partials/Action.vue";
import ActionButton from "@/Pages/Accounts/Partials/ActionButton.vue";
import Empty from '@/Components/Empty.vue';
import {generalFormat, transactionFormat} from "@/Composables/index.js";
import {usePage} from "@inertiajs/vue3";
import {trans} from "laravel-vue-i18n";
import { useConfirm } from 'primevue/useconfirm';

const isLoading = ref(false);
const accounts = ref([]);
const accountType = ref('individual');
const { formatAmount } = transactionFormat();
const { formatRgbaColor } = generalFormat();

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

const confirm = useConfirm();
const confirmationBox = (accountType, details) => {
    // console.log("ClaimBonusConfirmation executed", formData);
    const messages = {
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
        class="flex flex-col justify-center items-center py-3 pl-4 pr-3 gap-5 flex-grow md:pr-6 rounded-lg border-l-8 bg-white shadow-card w-full md:w-1/2 animate-pulse"
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
                <ActionButton :account="account" :type="accountType" />
            </div>
        </div>
    </div>

    <Empty
        v-else-if="!isLoading && accounts.length === 0"
        :title="$t('public.empty_trading_account_title')"
        :message="$t('public.empty_trading_account_message')"
    />

</template>
