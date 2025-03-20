<script setup>
import { usePage, router } from "@inertiajs/vue3";
import Button from "@/Components/Button.vue";
import Select from "primevue/select";
import { IconGift, IconChecks } from "@tabler/icons-vue";
import {
    PointIcon
} from '@/Components/Icons/outline.jsx';
import { ref, watch, watchEffect, computed, h } from "vue";
import {useLangObserver} from "@/Composables/localeObserver.js";
import { transactionFormat } from "@/Composables/index.js";
import { trans, wTrans } from "laravel-vue-i18n";
import { useConfirm } from "primevue/useconfirm";
import CashRewardContent from "./CashRewardContent.vue";
import PhysicalRewardContent from "./PhysicalRewardContent.vue";
import dayjs from "dayjs";
import RedeemHistory from "./RedeemHistory.vue";

const props = defineProps({
    trade_points: Number,
});

const {locale} = useLangObserver();
const { formatAmount, formatDate, formatDateTime } = transactionFormat();

const selectedReward = ref('all_rewards');
const rewardFilters = computed(() => [
    { name: trans('public.all_rewards'), value: 'all_rewards' },
    { name: trans('public.cash_rewards_only'), value: 'cash_rewards_only' },
    { name: trans('public.physical_rewards_only'), value: 'physical_rewards_only' },
]);

const rewards = ref([]);
const loading = ref(false);

const getRewardData = async (selectedReward = '') => {
    if (loading.value) return;
    loading.value = true;

    try {
        let url = `rewards/getRewardsData`;

        if (selectedReward) {
            url += `?filter=${selectedReward}`;
        }

        const response = await axios.get(url);
        rewards.value = response.data.rewards;
    } catch (error) {
        console.error('Error fetching rewards:', error);
    } finally {
        loading.value = false;
    }
};

getRewardData(selectedReward.value);

const formattedExpiryDate = (expiry_date) => {
    return expiry_date
        ? `${trans('public.available_until')} ${formatDateTime(expiry_date)}`
        : trans('public.no_expiry_date');
};

watch(selectedReward, (newReward) => {
    getRewardData(newReward);
});

const confirm = useConfirm();

const requireConfirmation = (action_type, details) => {

    const messages = {
        redeem_cash_rewards: () => ({
            group: 'headless',
            color: 'primary',
            icon: h(IconGift),
            header: trans('public.redeem_rewards'),
            message: trans('public.redeem_cash_rewards_caption', {reward_name: `${details.name[locale.value]}`, reward_points: `${details.trade_point_required}`}),
            cancelButton: null,
            acceptButton: null,
            content: () => 
                h(CashRewardContent, {
                    reward_id: details.reward_id,
                    'onUpdate:visible': () => {
                        confirm.close();
                    },
                })
        }),
        redeem_physical_rewards: () => ({
            group: 'headless',
            color: 'primary',
            icon: h(IconGift),
            header: trans('public.redeem_rewards'),
            message: trans('public.redeem_physical_rewards_caption', {reward_name: `${details.name[locale.value]}`, reward_points: `${details.trade_point_required}`}),
            cancelButton: null,
            acceptButton: null,
            content: () => 
                h(PhysicalRewardContent, {
                    reward_id: details.reward_id,
                    'onUpdate:visible': () => {
                        confirm.close();
                    },
                })
        }),
        redeem_cash_success: () => ({
            group: 'headless',
            color: 'primary',
            icon: h(IconChecks),
            header: trans('public.redemption_successful'),
            message: trans('public.redemption_successful_caption'),
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
                    h('p', { class: 'text-sm text-gray-500 min-w-[140px]' }, trans('public.rewards_code')),
                    h('p', { class: 'text-sm font-medium text-gray-950' }, details.reward.code),
                ]),
                h('div', { class: 'flex flex-col md:flex-row gap-1 flex-wrap' }, [
                    h('p', { class: 'text-sm text-gray-500 min-w-[140px]' }, trans('public.rewards_name')),
                    h('p', { class: 'text-sm font-medium text-gray-950' }, '💰 ' , details.reward.name[locale.value]),
                ]),
                h('div', { class: 'flex flex-col md:flex-row gap-1 flex-wrap' }, [
                    h('p', { class: 'text-sm text-gray-500 min-w-[140px]' }, trans('public.points_used')),
                    h('p', { class: 'text-sm font-medium text-gray-950' }, details.reward.trade_point_required, 'tp'),
                ]),
                h('div', { class: 'flex flex-col md:flex-row gap-1 flex-wrap' }, [
                    h('p', { class: 'text-sm text-gray-500 min-w-[140px]' }, trans('public.from')),
                    h('p', { class: 'text-sm font-medium text-gray-950' }, details.receiving_account),
                ])
            ])
        }),
        redeem_physical_success: () => ({
            group: 'headless',
            color: 'primary',
            icon: h(IconChecks),
            header: trans('public.redemption_successful'),
            message: trans('public.redemption_successful_caption'),
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
                    h('p', { class: 'text-sm text-gray-500 min-w-[140px]' }, trans('public.rewards_code')),
                    h('p', { class: 'text-sm font-medium text-gray-950' }, details.reward.code),
                ]),
                h('div', { class: 'flex flex-col md:flex-row gap-1 flex-wrap' }, [
                    h('p', { class: 'text-sm text-gray-500 min-w-[140px]' }, trans('public.rewards_name')),
                    h('p', { class: 'text-sm font-medium text-gray-950' }, '🎁 ' , details.reward.name[locale.value]),
                ]),
                h('div', { class: 'flex flex-col md:flex-row gap-1 flex-wrap' }, [
                    h('p', { class: 'text-sm text-gray-500 min-w-[140px]' }, trans('public.points_used')),
                    h('p', { class: 'text-sm font-medium text-gray-950' }, details.reward.trade_point_required, 'tp'),
                ]),
                h('div', { class: 'flex flex-col md:flex-row gap-1 flex-wrap' }, [
                    h('p', { class: 'text-sm text-gray-500 min-w-[140px]' }, trans('public.recipient_name')),
                    h('p', { class: 'text-sm font-medium text-gray-950' }, details.recipient_name),
                ]),
                h('div', { class: 'flex flex-col md:flex-row gap-1 flex-wrap' }, [
                    h('p', { class: 'text-sm text-gray-500 min-w-[140px]' }, trans('public.phone_number')),
                    h('p', { class: 'text-sm font-medium text-gray-950' }, details.phone_number),
                ]),
                h('div', { class: 'flex flex-col md:flex-row gap-1 flex-wrap' }, [
                    h('p', { class: 'text-sm text-gray-500 min-w-[140px]' }, trans('public.provided_address')),
                    h('p', { class: 'text-sm font-medium text-gray-950' }, details.address),
                ])
            ])
        }),
    };

    const { group, color, icon, header, message, cancelButton, acceptButton, action, content } = messages[action_type]();

    confirm.require({
        group,
        color,
        icon,
        header,
        message,
        cancelButton,
        acceptButton,
        accept: action,
        content: content()
    });
};

const rewardRedemption = (reward) => {
    if (reward.type === 'cash_rewards') {
        requireConfirmation('redeem_cash_rewards', reward)
    } else {
        requireConfirmation('redeem_physical_rewards', reward)
    }
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getRewardData(selectedReward.value);
    }

    if (usePage().props.notification !== null) {
        requireConfirmation(usePage().props.notification.type, usePage().props.notification.details);
        getRewardData(selectedReward.value);
        usePage().props.notification = null;
    }
});
</script>

<template>
    <div class="flex flex-col justify-center items-center p-3 md:p-6 gap-6 self-stretch rounded-lg bg-white shadow-card">
        <div class="w-full flex flex-col md:flex-row gap-3 items-start justify-between">
            <span class="text-gray-950 font-bold">{{ $t('public.rewards_catalog_n_redemption') }}</span>
            <div class="flex flex-row gap-2">
                <RedeemHistory />
                <Select 
                    v-model="selectedReward" 
                    :options="rewardFilters" 
                    optionLabel="name" 
                    optionValue="value"
                    :placeholder="$t('public.reward_placeholder')"
                    class="font-normal truncate" scroll-height="236px" 
                />
            </div>
        </div>
        <div class="grid gap-3 md:gap-5 w-full grid-cols-2 xl:grid-cols-3 3xl:grid-cols-4">
            <div v-for="(item, index) in rewards" :key="index"
                class="flex flex-col gap-2 justify-center rounded w-full "
            >
                <img :src="item.reward_thumbnail" alt="reward_image" class="h-[97.5px] smd:h-[138.75px] md:h-[247px] xl:h-[223px] 3xl:h-[247px]"/>
                <div class="flex flex-col gap-2 md:gap-3 w-full py-2 flex-1">
                    <div class="flex flex-col gap-2 flex-1">
                        <div class="flex flex-row justify-between">
                            <span class="text-xs md:text-sm text-gray-500">{{ item.code }}</span>
                            <span class="flex flex-row gap-1 text-xs md:text-sm text-warning-500 font-medium items-center">
                                <PointIcon class="w-4 h-4"/>
                                <span>{{ item.trade_point_required }} tp</span>
                            </span>
                        </div>
                        <span class="text-sm md:text-base text-gray-950 font-semibold flex items-center h-full">
                            {{ item.type === 'cash_rewards' ? '💰 ' : '🎁 '  }}{{ item.name[locale] }}
                        </span>
                    </div>
                    <div class="flex flex-col gap-[6px] items-center">
                        <Button
                            type="button"
                            variant="primary-flat"
                            class="w-full !px-2"
                            size="sm"
                            :disabled="(props.trade_points ?? 0) < item.trade_point_required || item.current_status != 'redeem'"
                            @click="rewardRedemption(item)"
                        >
                            {{ $t(`public.${item.current_status}`) }}
                        </Button>
                        <span class="text-gray-500 text-xxs md:text-xs truncate w-full">
                            {{ formattedExpiryDate(item.expiry_date) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>