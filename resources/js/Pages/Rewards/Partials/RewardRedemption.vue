<script setup>
import { usePage } from "@inertiajs/vue3";
import Button from "@/Components/Button.vue";
import Select from "primevue/select";
import { IconPlus } from "@tabler/icons-vue";
import {
    PointIcon
} from '@/Components/Icons/outline.jsx';
import { ref, watch, watchEffect } from "vue";
import {useLangObserver} from "@/Composables/localeObserver.js";
import { transactionFormat } from "@/Composables/index.js";

const {locale} = useLangObserver();
// const selectedReward = ref('least_trade_point');
const { formatAmount, formatDate, formatDateTime } = transactionFormat();

const rewardFilters = ref([
    { name: 'Least Trade Point', value: 'least_trade_point' },
    { name: 'Most Redeemed', value: 'most_redeemed' },
    { name: 'Cash Rewards Only', value: 'cash_rewards_only' },
    { name: 'Physical Rewards Only', value: 'physical_rewards_only' },
]);

const rewards = ref([]);
const loading = ref(false);

const getRewardData = async () => {
    if (loading.value) return;
    loading.value = true;

    try {
        let url = `rewards/getRewardsData`;

        // if (selectedReward) {
        //     url += `?filter=${selectedReward}`;
        // }

        const response = await axios.get(url);
        rewards.value = response.data.rewards;
    } catch (error) {
        console.error('Error fetching rewards:', error);
    } finally {
        loading.value = false;
    }
};

getRewardData();

// watch(selectedReward, (newReward) => {
//     getRewardData(newReward);
// });


// watchEffect(() => {
//     if (usePage().props.toast !== null) {
//         getRewardData();
//     }
// });
</script>

<template>

    <div class="flex flex-col justify-center items-center p-6 gap-5 self-stretch rounded-lg bg-white shadow-card">
        <div class="w-full flex flex-row items-center justify-between">
            <span class="text-gray-950 font-bold">{{ $t('public.rewards_catalog_n_redemption') }}</span>
            <!-- <Select 
                v-model="selectedReward" 
                :options="rewardFilters" 
                optionLabel="name" 
                optionValue="value"
                :placeholder="$t('public.reward_placeholder')"
                class="w-full md:w-60 font-normal truncate" scroll-height="236px" 
            /> -->
        </div>
        <div class="grid gap-3 md:gap-5 w-full grid-cols-1 md:grid-cols-2 2xl:grid-cols-3">
            <div v-for="(item, index) in rewards" :key="index"
                class="flex flex-col gap-2 justify-center px-3 md:px-4 py-3 rounded w-full shadow-card bg-white border border-gray-100"
            >
                <img :src="item.reward_thumbnail" alt="reward_image" class="h-[186px] md:h-[225px] xl:h-[321px]"/>
                <div class="flex flex-col gap-3 w-full py-2">
                    <div class="flex flex-col gap-2">
                        <div class="flex flex-row justify-between">
                            <span class="text-sm text-gray-500">{{ item.code }}</span>
                            <span class="flex flex-row gap-1 text-sm text-warning-500 font-medium items-center">
                                <PointIcon class="w-4 h-4"/>
                                <span>{{ item.trade_point_required }} tp</span>
                            </span>
                        </div>
                        <span class="text-gray-950 font-semibold">{{ item.type === 'cash_rewards' ? '💰 ' : '🎁 '  }}{{ item.name[locale] }}</span>
                    </div>
                    <div class="flex flex-col gap-[6px] items-center">
                        <Button
                            type="button"
                            variant="primary-flat"
                            class="w-full"
                        >
                            {{ $t('public.redeem') }}
                        </Button>
                        <span class="text-gray-600 text-sm">
                            {{
                                item.expiry_date
                                ? formatDateTime(item.expiry_date)
                                : $t('public.no_expiry_date')
                            }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>