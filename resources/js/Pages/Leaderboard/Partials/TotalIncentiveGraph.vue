<script setup>
import { transactionFormat } from "@/Composables/index.js";
import { ref, computed, watch, onMounted } from "vue";
import Select from 'primevue/select';
import { wTrans, trans } from "laravel-vue-i18n";
// import Chart from 'primevue/chart';
import dayjs from "dayjs";
import Chart from 'chart.js/auto'

const { formatAmount, formatDate } = transactionFormat();
let chartInstance = null;
const chartData = ref({
    labels: [],
    datasets: [],
});

const totalYearlyIncentive = ref(0);  // Declare as a ref to make it reactive

// Initialize selectedYear to the current year
const selectedYear = ref('');

// Available years for the dropdown
const availableYears = ref([]);
const startYear = 2024;
const endYear = dayjs().year();

for (let year = startYear; year <= endYear; year++) {
    availableYears.value.push({
        value: year.toString()
    });
}

selectedYear.value = dayjs().year().toString();

const fetchData = async () => {
    try {
        // Fetch the chart data based on the selected year
        const response = await axios.get('/leaderboard/getTotalIncentiveData', { params: { year: selectedYear.value } });
        const { labels, datasets } = response.data.chartData;

        datasets.forEach((dataset) => {
            dataset.label = wTrans(dataset.label);
        });

        chartData.value.labels = labels;
        chartData.value.datasets = datasets;
        totalYearlyIncentive.value = response.data.totalEarnedIncentive;
        // Update the chart data
        if (chartInstance) {
            chartInstance.data = chartData.value;
            chartInstance.update();
        }
    } catch (error) {
        console.error('Error fetching chart data:', error);
    }
};

const initializeChart = () => {
    const ctx = document.getElementById('total_incentive_chart');
    if (!ctx) return;

    // Initialize the chart
    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: chartData.value,
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    ticks: {
                        color: '#667085',
                        font: {
                            family: 'Poppins, sans-serif',
                            size: 12,
                            weight: 400,
                        },
                        display: false,
                        count: 6,
                    },
                    beginAtZero: true,
                    border: {
                        display: false
                    },
                    grid: {
                        drawTicks: false,
                        color: '#F2F4F7',
                    },
                },
                x: {
                    ticks: {
                        color: '#667085',
                        font: {
                            family: 'Poppins, sans-serif',
                            size: 12,
                            weight: 400,
                        },
                    },
                    grid: {
                        drawTicks: false,
                        color: 'transparent'
                    },
                }
            },
            plugins: {
                legend: {
                    display: false
                },
            }
        }
    });
};

onMounted(() => {
    // Initialize the chart when the component is mounted
    initializeChart();
    // Fetch the initial data for the chart
    fetchData();
});

// Watch for changes in selectedYear and call getResults when it changes
watch(selectedYear, (newYear) => {
    fetchData();
});

</script>

<template>
    <div class="flex flex-col px-8 py-6 items-center gap-3 rounded-lg bg-white shadow-card">
        <div class="flex md:justify-center items-start gap-3 md:gap-8 self-stretch">
            <div class="flex flex-col items-start gap-2 flex-1">
                <span class="text-gray-500 text-sm">{{ $t('public.total_incentive_earned') }}</span>
                <span class="text-gray-950 text-xxl font-semibold">{{ `$&nbsp;${formatAmount(totalYearlyIncentive)}` }}</span>
            </div>
            <div>
                <Select
                    v-model="selectedYear"
                    :options="availableYears"
                    optionLabel="value"
                    optionValue="value"
                    class="border-none shadow-none font-medium text-gray-700"
                    scroll-height="236px"
                />
            </div>
        </div>
        <div class="w-full">
            <canvas id="total_incentive_chart" height="200"></canvas>
        </div>
    </div>

</template>