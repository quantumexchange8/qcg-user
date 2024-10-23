<script setup>
import { transactionFormat } from "@/Composables/index.js";
import { ref, computed, watch, onMounted } from "vue";
import Select from 'primevue/select';
import Chart from 'primevue/chart';

const { formatAmount, formatDate } = transactionFormat();
// Chart labels (static, for months)
const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

// To hold the chart data from the API
const chartData = ref(new Array(12).fill(0)); // Initialize with zeros for each month

// Chart options (optional for additional customization)
const chartOptions = ref({
    responsive: true,
    scales: {
        y: {
            grid: {
                display: true, // Enable y-axis grid lines
                drawBorder: false,
            },
            border: {
                display: false,  // This removes the x-axis baseline
            },
            ticks: {
                display: false, // Display y-axis labels
                stepSize: 1
            },
            max: 5,
            min: 0
        },
        x: {
            grid: {
                display: false, // Disable x-axis grid lines if not needed
                drawBorder: false,
            },
            ticks: {
                display: true // Keep x-axis labels visible
            }
        }
    },
    plugins: {
        legend: {
            display: false, // Disable the legend
        },
        title: {
            display: false, // Disable the title
        }
    }
});

const totalYearlyIncentive = ref(0);  // Declare as a ref to make it reactive
const monthlyIncentives = ref([]);

// Initialize selectedYear to the current year
const selectedYear = ref(new Date().getFullYear());
const loading = ref(false);

// Available years for the dropdown
const availableYears = ref([2024, 2023, 2022, 2021]);

// Function to calculate the maximum value from monthly incentives
const getMaxMonthlyIncentive = (incentives) => {
    if (incentives.length === 0) return 0; // Handle empty case
    return Math.max(...incentives.map(item => item.total || 0)); // Assuming `total` is the key for the incentive value
};

// Function to fetch results
const getResults = async (incentiveYear = selectedYear.value) => {
    loading.value = true;

    try {
        let url = `/leaderboard/getTotalIncentiveGraph?year=${incentiveYear}`;

        const response = await axios.get(url);
        totalYearlyIncentive.value = response.data.totalYearlyIncentive;
        monthlyIncentives.value = Array.isArray(response.data.monthlyIncentives) ? response.data.monthlyIncentives : [];

        // Map the monthly incentives to align with the chart labels (Jan to Dec)
        const monthlyData = new Array(12).fill(0); // Initialize an array for 12 months (Jan to Dec)

        // Ensure monthlyIncentives is an array before using .forEach
        if (Array.isArray(monthlyIncentives.value)) {
            monthlyIncentives.value.forEach(item => {
                if (item.month >= 1 && item.month <= 12) {
                    monthlyData[item.month - 1] = item.total || 0; // Use item.total or 0 if undefined
                }
            });
        }

        chartData.value = monthlyData; // Set the chart data to be used in the chart

        console.log(totalYearlyIncentive.value, monthlyIncentives.value);
    } catch (error) {
        console.error('Error fetching data:', error);
    } finally {
        loading.value = false;
    }
};

// Watch for changes in selectedYear and call getResults when it changes
watch(selectedYear, (newYear) => {
    getResults(newYear);
});

// Call getResults initially with the current year
getResults();

// Combine the static labels with the dynamic data from the API
const formattedChartData = computed(() => {
    return {
        labels,
        datasets: [
            {
                label: 'Incentives',
                backgroundColor: '#FF9800',
                data: chartData.value // This should always have 12 values, filled with zeros or real data
            }
        ]
    };
});

</script>

<template>
    <div class="flex flex-col px-8 py-6 w-[610px] items-center gap-3 rounded-lg bg-white shadow-card">
        <div class="flex justify-center items-start gap-8 self-stretch">
            <div class="flex flex-col items-start gap-2 flex-1">
                <span class="text-gray-500 text-sm">{{ $t('public.total_incentive_earned') }}</span>
                <span class="text-gray-950 text-xxl font-semibold">{{ `$&nbsp;${formatAmount(totalYearlyIncentive)}` }}</span>
            </div>
            <div>
                <Select
                    v-model="selectedYear"
                    :options="availableYears"
                />
            </div>
        </div>
        <Chart type="bar" :data="formattedChartData" :options="chartOptions" class="w-full flex px-20 py-0 justify-between items-end flex-shrink-0"/>
    </div>

</template>