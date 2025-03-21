<script setup>
import { transactionFormat } from "@/Composables/index.js";
import { ref, computed, watch, onMounted } from "vue";
import RebateDetailsTable from '@/Pages/Report/Partials/RebateDetailsTable.vue';
import Chart from 'primevue/chart';
import { trans, wTrans } from "laravel-vue-i18n";
import dayjs from 'dayjs'

const { formatDate, formatDateTime, formatAmount } = transactionFormat();

// Define reactive variables
const rebateBreakdown = ref([]);
const totalVolume = ref(0);
const totalRebate = ref(0);
const month = ref([]);

// Function to fetch rebate summary data
const getResults = async (month) => {
    let url = `/report/getRebateBreakdown`;

    if (month) {
            let formattedMonth = month;

            if (!formattedMonth.startsWith('select_') && !formattedMonth.startsWith('last_')) {
                formattedMonth = dayjs(month, 'DD MMMM YYYY').format('MMMM YYYY');
            }

            url += `?selectedMonth=${formattedMonth}`;
        }

    try {
        const response = await axios.get(url);
        const data = response.data;

        // Update the rebateSummary and totals
        rebateBreakdown.value = data.rebateBreakdown;
        totalVolume.value = data.totalVolume;
        totalRebate.value = data.totalRebate;
    } catch (error) {
        console.error('Error fetching rebate summary:', error);
    }
};

// Watch for changes in the dateRange and fetch rebate summary data
watch(month, (newMonth) => {
    if (newMonth === null || newMonth === undefined) {
        // Handle null or undefined newDateRange
        getResults(null);
    } else {
        getResults(newMonth);
    }
});

// Handle the update-date event from RebateListingTable
const handleUpdateMonth = (newMonth) => {
    // console.log('Date Range Received:', newDateRange);
    month.value = newMonth;
};

// Function to calculate percentage of each rebate
const calculatePercentages = (rebates, totalRebate) => {
    if (totalRebate === 0) return rebates.map(() => 0);
    return rebates.map(item => (item.rebate / totalRebate) * 100);
};

// Chart options (optional for additional customization)
const chartOptions = ref({
    responsive: true,
    maintainAspectRatio: true,
    rotation: 0, // Start from the top
    circumference: 360, // Complete the full circle
    plugins: {
        legend: {
            position: 'bottom',
            labels:{
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 20
            }
        },
        title: {
            display: false, // Disable the title
        }
    }
});

// Base colors
const baseColors = [
    '#204724',
    '#2A6B2D',
    '#42A547',
    '#9BDA9E',
    '#E2F6E3',
];

// Function to darken color
const darkenColor = (color, percent) => {
    let r = parseInt(color.slice(1, 3), 16);
    let g = parseInt(color.slice(3, 5), 16);
    let b = parseInt(color.slice(5, 7), 16);

    r = Math.max(0, Math.min(255, r - r * percent));
    g = Math.max(0, Math.min(255, g - g * percent));
    b = Math.max(0, Math.min(255, b - b * percent));

    return `#${Math.round(r).toString(16).padStart(2, '0')}${Math.round(g).toString(16).padStart(2, '0')}${Math.round(b).toString(16).padStart(2, '0')}`;
};

// Darken each color by 20%
const darkerColors = baseColors.map(color => darkenColor(color, 0.2));

const chartData = computed(() => {
    if (rebateBreakdown.value.length === 0 || totalRebate.value === 0) {
        return {
            labels: [trans('public.' + 'no_data')],
            datasets: [
                {
                    data: [100],
                    backgroundColor: ['#F2F4F7'],
                    hoverBackgroundColor: ['#E0E2E5'],
                    borderWidth: 0, 
                }
            ]
        };
    }

    const percentages = calculatePercentages(rebateBreakdown.value, totalRebate.value);
    return {
        labels: rebateBreakdown.value.map(item => trans('public.' + item.symbol_group)),
        datasets: [
            {
                data: percentages,
                backgroundColor: baseColors,
                hoverBackgroundColor: darkerColors,
                borderWidth: 0, 
            }
        ]
    };
});

</script>

<template>
    <div class="flex flex-col items-center gap-5 self-stretch">
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 justify-center items-center self-stretch">
            <div class="flex flex-col px-8 py-6 items-center gap-7 flex-1 self-stretch rounded-lg bg-white shadow-card">
                <span class="text-gray-950  font-bold">
                    {{ $t('public.rebate_percentage_breakdown') }}
                </span>
                <div class="">
                    <Chart 
                        type="doughnut" 
                        :data="chartData" 
                        :options="chartOptions"
                        style="width: 100%;"
                    />
                </div>
            </div>
            <div class="flex flex-col px-8 py-6 items-center gap-3 flex-1 rounded-lg bg-white shadow-card">
                <div class="flex items-center gap-5 self-stretch">
                    <div class="flex flex-col py-3 items-center gap-2 flex-1">
                        <span class="text-gray-500 text-center">{{ $t('public.total_trade_volume') }} (Ł)</span>
                        <span class="text-gray-950 text-xxl font-semibold">{{ formatAmount(totalVolume) }}</span>
                    </div>
                    <div class="w-[1px] h-[52px] rounded-full bg-gray-300"></div>
                    <div class="flex flex-col py-3 items-center gap-2 flex-1">
                        <span class="text-gray-500 text-center">{{ $t('public.total_rebate_earned') }} ($)</span>
                        <span class="text-gray-950 text-xxl font-semibold">{{ formatAmount(totalRebate) }}</span>
                    </div>
                </div>
                <div class="flex flex-col justify-center items-center self-stretch">
                    <div v-for="(item, index) in rebateBreakdown" :key="index" class="flex items-center py-3 gap-4 self-stretch md:gap-5">
                        <!-- <img :src="`/img/rebate/3d-${item.symbol_group}.svg`"  alt=""  class="w-9 h-9 flex-shrink-0 md:w-10 md:h-10"> -->
                        <!-- sm -->
                        <div class="w-full flex flex-col items-start md:hidden">
                            <span class="self-stretch truncate text-gray-950 text-sm font-semibold">
                                {{ $t('public.' + item.symbol_group) }}
                            </span>
                            <span class="self-stretch text-gray-500 text-sm">
                                {{ item.volume > 0 ? `${formatAmount(item.volume)}&nbsp;Ł` : '-' }}
                            </span>
                        </div>
                        <!-- md -->
                        <span class="w-full hidden md:block truncate text-gray-950 text-base font-medium">
                            {{ $t('public.' + item.symbol_group) }}
                        </span>
                        <span class="w-full hidden md:block text-right text-gray-950 text-base">
                            {{ item.volume > 0 ? `${formatAmount(item.volume)}&nbsp;Ł` : '-' }}
                        </span>
                        <span class="w-full truncate text-gray-950 text-right font-semibold md md:font-normal">
                            {{ item.rebate > 0 ? `$&nbsp;${formatAmount(item.rebate)}` : '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <RebateDetailsTable  @update-month="handleUpdateMonth"/>
    </div>
</template>