<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import {ref, watchEffect, computed, onMounted, onUnmounted, h, watch} from "vue";
import { useForm, usePage, router, Link, Head } from '@inertiajs/vue3';
import { IconPlus } from "@tabler/icons-vue";
import { FirstPlaceIcon, SecondPlaceIcon, ThirdPlaceIcon } from "@/Components/Icons/solid.jsx";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import { transactionFormat } from "@/Composables/index.js";
import {useLangObserver} from "@/Composables/localeObserver.js";
import Empty from "@/Components/Empty.vue";
import Loader from "@/Components/Loader.vue";
import { differenceInDays, differenceInHours, differenceInMinutes, differenceInSeconds } from 'date-fns';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import { Vue3Lottie } from 'vue3-lottie';
import OneStarBadge from '@/Components/Icons/OneStarBadge.json';

const props = defineProps({
    competitions: Object,
})

const { formatAmount, formatDate } = transactionFormat();
const {locale} = useLangObserver();

const loading = ref(false);
const participants = ref();
const activeCompetitionId = ref(props.competitions[0]?.id || null);

// A computed property to get the full competition object from the ID
const activeCompetition = computed(() => {
    if (!activeCompetitionId.value) return null;
    return props.competitions.find(comp => comp.id === activeCompetitionId.value);
});

const getResults = async () => {
    // console.log(activeCompetition.value)
    if (!activeCompetition.value) return;

    loading.value = true;
    try {
        const response = await axios.get(`/competition/getParticipants?competition_id=${activeCompetition.value.id}`);
        
        participants.value = response.data.participants;
    } catch (error) {
        console.error('Error changing locale:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

// Create reactive variables to store the countdown values
const days = ref(0);
const hours = ref(0);
const minutes = ref(0);
const seconds = ref(0);
let timer = null;

// Determine if the countdown should be to the start or end date
// We'll countdown to the start date if it's in the future, otherwise we countdown to the end date
const countdownTarget = computed(() => {
    const now = new Date();
    const startDate = new Date(activeCompetition.value.start_datetime);

    if (now < startDate) {
        return startDate; // Countdown to the start of the competition
    } else {
        return new Date(activeCompetition.value.end_datetime); // Countdown to the end
    }
});

const isCompetitionActive = computed(() => {
    const now = new Date();
    const startDate = new Date(activeCompetition.value.start_datetime);
    const endDate = new Date(activeCompetition.value.end_datetime);

    return now >= startDate && now < endDate;
});

// The core function to calculate and update the countdown
const updateCountdown = () => {
    const now = new Date();
    const target = countdownTarget.value;

    const totalSeconds = differenceInSeconds(target, now);

    if (totalSeconds <= 0) {
        // Stop the countdown if the target date has passed
        clearInterval(timer);
        days.value = 0;
        hours.value = 0;
        minutes.value = 0;
        seconds.value = 0;
        return;
    }

    days.value = differenceInDays(target, now);
    const remainingHours = differenceInHours(target, now) - (days.value * 24);
    const remainingMinutes = differenceInMinutes(target, now) - (days.value * 24 * 60) - (remainingHours * 60);
    const remainingSeconds = totalSeconds - (days.value * 24 * 60 * 60) - (remainingHours * 60 * 60) - (remainingMinutes * 60);

    hours.value = remainingHours;
    minutes.value = remainingMinutes;
    seconds.value = remainingSeconds;
};

const tableSize = ref(null);

const checkScreenSize = () => {
    tableSize.value = window.innerWidth < 768 ? 'small' : null;
};

onMounted(() => {
    if (props.competitions.length > 0) {
        activeCompetitionId.value = props.competitions[0].id;
    }

    updateCountdown(); 
    timer = setInterval(updateCountdown, 1000);

    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);
});

watch(activeCompetitionId, (newId) => {
    if (newId) {
        updateCountdown(); 
        timer = setInterval(updateCountdown, 1000);
        getResults();
    }
}, { immediate: true });

onUnmounted(() => {
    clearInterval(timer);
    window.removeEventListener('resize', checkScreenSize);
});

const dynamicSuffix = computed(() => {
    if (activeCompetition.value.category === 'profit_rate') {
        return ' %';
    } else if (activeCompetition.value.category === 'trade_lot') {
        return ' Ł';
    }
    return '';
});

const getRankContent = (rank) => {
    if (rank === 1) {
        return FirstPlaceIcon;
    } else if (rank === 2) {
        return SecondPlaceIcon;
    } else if (rank === 3) {
        return ThirdPlaceIcon;
    } else {
        return rank;
    }
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});
</script>

<template>
    <AuthenticatedLayout :title="$t('public.competition')">
        <!-- Competition Information -->
        <div class="w-full flex flex-col gap-3 md:gap-5 justify-center items-center p-3 md:py-6 md:px-12 rounded-t-lg bg-primary-500 bg-blend-multiply bg-[url(/assets/Competition/competition-bg.jpg)] bg-cover bg-center">
            <Tabs v-model:value="activeCompetitionId">
                <TabList>
                    <Tab v-for="competition in competitions" :key="competition.id" :value="competition.id" 
                                :pt="{
                        root: ({ context }) => ({
                            class: [
                                'relative shrink-0 p-3 outline-transparent font-semibold cursor-pointer select-none whitespace-nowrap user-select-none text-xs md:text-sm',
                                {
                                    'text-gray-200 hover:text-white': !context.active,
                                    'text-white border-b-2 border-white': context.active
                                }
                            ]
                        })
                    }">
                            {{ competition.name[locale] }}
                    </Tab>
                </TabList>
            </Tabs>
            <div v-if="activeCompetition" class="flex flex-col self-stretch gap-5 md:gap-8 items-center py-5 px-2 md:py-8 md:px-[60px] bg-black">
                <div class="flex flex-col items-center gap-2 ">
                    <span class="text-white text-base md:text-xl font-bold">{{ activeCompetition.name[locale] }}</span>
                    <div class="flex px-2 py-1 justify-center items-center bg-primary-600 rounded-[50px]">
                        <span class="text-center text-white text-xs">{{ formatDate(activeCompetition.start_datetime) }} - {{ formatDate(activeCompetition.end_datetime) }}</span>
                    </div>
                </div>
                <div v-if="isCompetitionActive" class="flex flex-col w-full gap-5 md:gap-8 ">
                    <div class="flex items-center self-stretch flex-1">
                        <div class="flex flex-col items-center gap-1 flex-1">
                            <span class="text-xxl font-medium text-white">{{ days }}</span>
                            <span class="text-xs text-gray-500">{{ $t('public.day_s') }}</span>
                        </div>
                        <div class="flex flex-col items-center gap-1 flex-1">
                            <span class="text-xxl font-medium text-white">{{ hours }}</span>
                            <span class="text-xs text-gray-500">{{ $t('public.hour_s') }}</span>
                        </div>
                        <div class="flex flex-col items-center gap-1 flex-1">
                            <span class="text-xxl font-medium text-white">{{ minutes }}</span>
                            <span class="text-xs text-gray-500">{{ $t('public.minute_s') }}</span>
                        </div>
                        <div class="flex flex-col items-center gap-1 flex-1">
                            <span class="text-xxl font-medium text-white">{{ seconds }}</span>
                            <span class="text-xs text-gray-500">{{ $t('public.second_s') }}</span>
                        </div>
                    </div>
                    <span class="text-xxs md:text-xs text-gray-300 text-center animate-pulse">{{ $t('public.competition_live_desc') }}</span>
                </div>
                <div v-else class="flex flex-col w-full gap-8">                 
                    <div v-if="countdownTarget > new Date()" class="flex items-center self-stretch flex-1">
                        <div class="flex flex-col items-center gap-1 flex-1">
                            <span class="text-xxl font-medium text-white">{{ days }}</span>
                            <span class="text-xs text-gray-500">{{ $t('public.day_s') }}</span>
                        </div>
                        <div class="flex flex-col items-center gap-1 flex-1">
                            <span class="text-xxl font-medium text-white">{{ hours }}</span>
                            <span class="text-xs text-gray-500">{{ $t('public.hour_s') }}</span>
                        </div>
                        <div class="flex flex-col items-center gap-1 flex-1">
                            <span class="text-xxl font-medium text-white">{{ minutes }}</span>
                            <span class="text-xs text-gray-500">{{ $t('public.minute_s') }}</span>
                        </div>
                        <div class="flex flex-col items-center gap-1 flex-1">
                            <span class="text-xxl font-medium text-white">{{ seconds }}</span>
                            <span class="text-xs text-gray-500">{{ $t('public.second_s') }}</span>
                        </div>
                    </div>
                    <p class="text-xxs md:text-xs text-gray-300 text-center animate-pulse">
                        <span v-if="countdownTarget > new Date()">{{ $t('public.competition_soon_desc') }}</span>
                        <span v-else>{{ $t('public.competition_ended_desc') }}</span>
                    </p>
                </div>
            </div>
        </div>
        <!-- Competition Rank List -->
        <div class="w-full flex flex-col justify-center items-center p-3 md:py-5 md:px-6 gap-5 rounded-b-lg bg-white ">
            <!-- Ranking List -->
            <DataTable
                :value="participants"
                removableSort
                :rows="21"
                ref="dt"
                :loading="loading"
                :size="tableSize"
            >
                <!-- <template #header>
                </template> -->
                <template #empty>
                    <Empty 
                        :title="$t('public.empty_participants_title')" 
                        :message="$t('public.empty_participants_message')" 
                    />
                </template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <Loader />
                        <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                    </div>
                </template>
                <template v-if="participants?.length > 0">
                    <Column field="rank" :header="$t('public.rank')" headerClass="hidden md:table-cell" class="w-1/6">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                <component :is="getRankContent(slotProps.data.rank)" v-if="typeof getRankContent(slotProps.data.rank) === 'object'" />
                                <template v-else>
                                    {{ getRankContent(slotProps.data.rank) }}
                                </template>
                            </div>
                        </template>
                    </Column>
                    <Column field="name" :header="$t('public.participant')" headerClass="hidden md:table-cell" >
                        <template #body="slotProps">
                            <div class="flex gap-3 items-center">
                                <img :src="slotProps.data.rank_badge" class="w-8 h-8"/>
                                <span class="text-gray-950 text-sm truncate">{{ slotProps.data.name }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column field="score" :header="$t(`public.${activeCompetition.category}`)" headerClass="hidden md:table-cell">
                        <template #body="slotProps">
                            <div class="flex px-2 py-1 items-center rounded-[50px] bg-primary-100 text-sm text-primary-600 w-fit whitespace-nowrap">
                                {{ formatAmount(slotProps.data.score) }}{{ dynamicSuffix }}
                            </div>
                        </template>
                    </Column>
                    <Column field="title" :header="$t('public.category')" class="hidden md:table-cell">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm max-w-full">
                                {{ slotProps.data.title ? slotProps.data.title[locale] : '-' }}
                            </div>
                        </template>
                    </Column>
                    <Column field="points_rewarded" :header="$t('public.rewards') + ' (tp)'" headerClass="hidden md:table-cell" class="w-1/5" size="small">
                        <template #body="slotProps">
                            <div class="flex gap-0.5 items-center md:hidden">
                                <div class="w-4 h-4">
                                    <Vue3Lottie :animationData="OneStarBadge" :loop="true" :autoplay="true" />
                                </div>
                                <span class="text-gray-950 text-sm">{{ slotProps.data.points_rewarded ? slotProps.data.points_rewarded + 'tp' : '0tp' }}</span>
                            </div>
                            <span class="text-gray-950 text-sm hidden md:flex">{{ slotProps.data.points_rewarded ? slotProps.data.points_rewarded : '-' }}</span>
                        </template>
                    </Column>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>
