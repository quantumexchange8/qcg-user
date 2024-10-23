<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import { usePage, useForm } from "@inertiajs/vue3";
import { transactionFormat } from "@/Composables/index.js";
import { IconCircleXFilled, IconSearch, IconPencilMinus } from "@tabler/icons-vue";
import { ref, watch, watchEffect, computed } from "vue";
import Loader from "@/Components/Loader.vue";
import { wTrans, trans } from "laravel-vue-i18n";
import Button from '@/Components/Button.vue';
import Select from 'primevue/select';
import Paginator from 'primevue/paginator';
import ProgressBar from 'primevue/progressbar';
import Empty from "@/Components/Empty.vue";
import StatusBadge from "@/Components/StatusBadge.vue";
import debounce from "lodash/debounce.js";
import dayjs from "dayjs";
import TotalIncentiveGraph from "@/Pages/Leaderboard/Partials/TotalIncentiveGraph.vue";
import IncentiveWithdrawal from "@/Pages/Leaderboard/Partials/IncentiveWithdrawal.vue";
import ViewIncentiveReport from "@/Pages/Leaderboard/Partials/ViewIncentiveReport.vue";

const props = defineProps({
    profileCount: Number
})

// const isLoading = ref(false);
// const incentiveProfiles = ref([]);
// const currentPage = ref(1);
// const rowsPerPage = ref(6);
// const totalRecords = ref(0);
// const {formatAmount} = transactionFormat();

// const getResults = async (page = 1, filterRowsPerPage = rowsPerPage.value) => {
//     isLoading.value = true;

//     try {
//         let url = `/leaderboard/getAchievements?page=${page}&paginate=${filterRowsPerPage}`;

//         if (category.value) {
//             url += `&category=${category.value}`;
//         }

//         const response = await axios.get(url);
//         incentiveProfiles.value = response.data.incentiveProfiles;
//         totalRecords.value = response.data.totalRecords;
//         currentPage.value = response.data.currentPage;
//     } catch (error) {
//         console.error('Error getting masters:', error);
//     } finally {
//         isLoading.value = false;
//     }
// }

// getResults();

// // Watch for changes to the category ref
// watch(category, (newCategory) => {
//     getResults();
// });

// const onPageChange = (event) => {
//     currentPage.value = event.page + 1;
//     getResults(currentPage.value, rowsPerPage.value);
// };

// watchEffect(() => {
//     if (usePage().props.toast !== null) {
//         getResults();
//     }
// });
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.leaderboard')">
        <div class="flex flex-col items-center self-stretch gap-5">
            <div class="flex justify-center items-start gap-5 self-stretch">
                <TotalIncentiveGraph/>
                <IncentiveWithdrawal/>
            </div>
        </div>
    </AuthenticatedLayout>
</template>