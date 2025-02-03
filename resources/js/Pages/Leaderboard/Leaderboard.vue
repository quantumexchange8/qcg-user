<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import { usePage, useForm } from "@inertiajs/vue3";
import { transactionFormat } from "@/Composables/index.js";
import { ref, watch, watchEffect, computed } from "vue";
import ProgressBar from 'primevue/progressbar';
import Empty from "@/Components/Empty.vue";
import StatusBadge from "@/Components/StatusBadge.vue";
import dayjs from "dayjs";
import TotalIncentiveGraph from "@/Pages/Leaderboard/Partials/TotalIncentiveGraph.vue";
import IncentiveWithdrawal from "@/Pages/Leaderboard/Partials/IncentiveWithdrawal.vue";
import ViewIncentiveReport from "@/Pages/Leaderboard/Partials/ViewIncentiveReport.vue";

const isLoading = ref(false);
const incentiveProfiles = ref([]);
// const currentPage = ref(1);
// const rowsPerPage = ref(6);
// const totalRecords = ref(0);
const {formatAmount} = transactionFormat();

const getResults = async () => {
    isLoading.value = true;

    try {
        let url = `/leaderboard/getAchievements`;

        const response = await axios.get(url);
        incentiveProfiles.value = response.data.incentiveProfiles;

    } catch (error) {
        console.error('Error getting masters:', error);
    } finally {
        isLoading.value = false;
    }
}

getResults();

// // Watch for changes to the category ref
// watch(category, (newCategory) => {
//     getResults();
// });

// const onPageChange = (event) => {
//     currentPage.value = event.page + 1;
//     getResults(currentPage.value, rowsPerPage.value);
// };

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.leaderboard')">
        <div class="flex flex-col items-center self-stretch gap-5">
            <div class="grid grid-cols-1 md:grid-cols-3 justify-center items-start gap-5 self-stretch">
                <TotalIncentiveGraph class="col-span-1 md:col-span-2" />
                <IncentiveWithdrawal class="col-span-1" />
            </div>
            <div class="flex flex-col justify-center items-center p-6 gap-6 self-stretch rounded-lg bg-white shadow-card">
                <div class="flex h-6 flex-col justify-center self-stretch text-gray-950  font-bold">{{ $t('public.my_achievements') }}</div>

                <div v-if="!incentiveProfiles?.length">
                    <Empty
                        :title="$t('public.empty_incentive_profiles_title')"
                        :message="$t('public.empty_incentive_profiles_caption')"
                    >
                    </Empty>
                </div>

                <div
                    v-else
                    class="w-full"
                    :class="{
                        'grid grid-cols-1 gap-5 self-stretch xl:grid-cols-2': isLoading
                    }"
                >
                    <div
                        v-if="isLoading"
                        class="w-full flex flex-col items-center p-4 gap-3 self-stretch rounded border border-gray-100 bg-white shadow-card md:p-6 md:gap-6"
                    >
                        <div class="w-full flex px-4 py-2 items-center text-white self-stretch bg-primary-900">
                            {{ $t('public.incentive_threshold') }}
                        </div>

                        <div class="w-full flex flex-wrap justify-between items-center content-center gap-3 self-stretch">
                            <div class="flex flex-wrap items-center gap-2">
                                <StatusBadge variant="gray">{{ $t('public.mode') }}</StatusBadge>
                                <StatusBadge variant="gray">{{ $t('public.sales_category') }}</StatusBadge>
                            </div>
                            <div class="h-2.5 bg-gray-200 rounded-full w-40 my-1"></div>
                        </div>

                        <div class="w-full flex flex-col justify-center items-center content-center gap-3 self-stretch md:flex-row">
                            <!-- Target amount -->
                                <div class="w-full min-w-[120px] flex flex-col items-center py-3 px-2 gap-1 bg-gray-50 animate-pulse">
                                <div class="h-3 bg-gray-200 rounded-full w-20 mt-2 mb-1"></div>
                                <div class="h-2 bg-gray-200 rounded-full w-14 my-1.5"></div>
                            </div>

                            <!-- Target amount -->
                            <div class="w-full min-w-[120px] flex flex-col items-center py-3 px-2 gap-1 bg-gray-50 animate-pulse">
                                <div class="h-3 bg-gray-200 rounded-full w-20 mt-2 mb-1"></div>
                                <div class="h-2 bg-gray-200 rounded-full w-14 my-1.5"></div>
                            </div>
                        </div>

                        <!-- Progress bar -->
                        <div class="flex flex-col gap-1.5 items-center self-stretch w-full">
                            <ProgressBar
                                class="w-full"
                                :value="0"
                                :show-value="false"
                            />
                            <div class="flex justify-between items-center self-stretch text-gray-950 font-medium animate-pulse">
                                <div class="h-2 bg-gray-200 rounded-full w-14 my-2"></div>
                                <div class="h-2 bg-gray-200 rounded-full w-14 my-2"></div>
                            </div>
                        </div>

                    </div>

                    <div v-else-if="!incentiveProfiles?.length">
                        <Empty
                            :title="$t('public.empty_incentive_profiles_title')"
                            :message="$t('public.empty_incentive_profiles_caption')"
                        />
                    </div>

                    <div
                        v-else
                        class="w-full grid grid-cols-1 gap-5 self-stretch xl:grid-cols-2"
                    >
                        <div
                            v-for="profile in incentiveProfiles"
                            class="w-full flex flex-col items-center p-4 gap-3 self-stretch rounded border border-gray-100 bg-white shadow-card md:p-6 md:gap-6"
                        >
                            <div class="w-full flex px-4 py-2 items-center text-white self-stretch bg-primary-900">
                                {{ $t('public.incentive_threshold', { threshold: profile.calculation_threshold }) }}
                            </div>

                            <div class="w-full flex flex-wrap justify-between items-center content-center gap-3 self-stretch">
                                <div class="flex flex-wrap items-center gap-2">
                                    <StatusBadge variant="gray">{{ $t('public.' + profile.sales_calculation_mode) }}</StatusBadge>
                                    <StatusBadge variant="gray">{{ $t('public.' + profile.sales_category) }}</StatusBadge>
                                </div>
                                <span class="text-error-600 text-xs font-medium">
                                    {{ dayjs(profile.last_payout_date).format('YYYY/MM/DD') }}
                                    &nbsp;-&nbsp;
                                    {{ dayjs(profile.next_payout_date).format('YYYY/MM/DD') }}
                                </span>
                            </div>

                            <!-- Data -->
                            <div class="w-full flex flex-col justify-center items-center content-center gap-3 self-stretch md:flex-row">
                                <!-- Target amount -->
                                <div class="w-full min-w-[120px] flex flex-col items-center py-3 px-2 gap-1 bg-gray-50">
                                    <span class="self-stretch text-center text-lg font-semibold text-primary-600">
                                        $&nbsp;{{ (formatAmount(profile.incentive_amount)) }}
                                    </span>
                                    <span class="self-stretch text-center text-sm text-gray-500">
                                        {{ `${$t('public.incentive')}&nbsp;${profile.sales_category === 'trade_volume' ? `(${profile.incentive_rate})` : `(${profile.incentive_rate}%)`}` }}
                                    </span>
                                </div>

                                <!-- Target amount -->
                                <div class="w-full min-w-[120px] flex flex-col items-center py-3 px-2 gap-1 bg-gray-50">
                                    <span class="self-stretch text-center text-lg font-semibold text-gray-950">{{ formatAmount(profile.achieved_percentage) + '%' }}</span>
                                    <span class="self-stretch text-center text-xs text-gray-500">{{ $t('public.achieved') }}</span>
                                </div>
                            </div>


                            <!-- Progress bar -->
                            <div class="flex flex-col gap-1.5 items-center self-stretch w-full">
                                <ProgressBar
                                    class="w-full"
                                    :value="profile.achieved_percentage"
                                    :show-value="false"
                                />
                                <div class="flex justify-between items-center self-stretch text-gray-700">
                                    <div>
                                        <span v-if="profile.sales_category !== 'trade_volume'">$</span>
                                        {{ profile.achieved_amount % 1 === 0 ? formatAmount(profile.achieved_amount, 0) : formatAmount(profile.achieved_amount) }}
                                        <span v-if="profile.sales_category === 'trade_volume'">Ł</span>
                                    </div>
                                    <div>
                                        <span v-if="profile.sales_category !== 'trade_volume'">$</span>
                                        {{ profile.target_amount % 1 === 0 ? formatAmount(profile.target_amount, 0) : formatAmount(profile.target_amount) }}
                                        <span v-if="profile.sales_category === 'trade_volume'">Ł</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-4 px-4 py-3 items-center self-stretch w-full">
                                <ViewIncentiveReport
                                    :profile="profile"
                                />
                            </div>
                        </div>
                    </div>
                    <!-- <Paginator
                        v-if="!isLoading && incentiveProfiles?.length"
                        :first="(currentPage - 1) * rowsPerPage"
                        :rows="rowsPerPage"
                        :totalRecords="totalRecords"
                        @page="onPageChange"
                    /> -->
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>