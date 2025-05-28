<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import { ref, computed } from "vue";
import Button from "@/Components/Button.vue";
import Divider from 'primevue/divider';
import Dialog from 'primevue/dialog';
import dayjs from 'dayjs';

const announcements = ref([]);
const pinnedAnnouncements = ref([]);

const getAnnouncementData = async () => {
    try {
        const response = await axios.get('/highlights/getAnnouncement');
        announcements.value = response.data.announcements;
        pinnedAnnouncements.value = response.data.pinnedAnnouncements;
    } catch (error) { 
        console.error('Error getting announcement:', error);
    }
};

getAnnouncementData();

const visibleCount = ref(4)

const visibleAnnouncements = computed(() =>
  announcements.value.slice(0, visibleCount.value)
)

function loadMore() {
  visibleCount.value += 4
}

const visible = ref(false);
const data = ref({});
const openDialog = (announcementData) => {
    visible.value = true;
    data.value = announcementData;
};

const closeDialog = () => {
    visible.value = false;
    data.value = {};
};

const isToday = (date) => {
  return dayjs(date).isSame(dayjs(), 'day')
}

const getTimeLabel = (announcement) => {
  if (isToday(announcement.start_date)) {
    const start = dayjs(announcement.start_date)
    const updated = dayjs(announcement.updated_at)
    return start.isSame(updated) ? '00:00' : updated.format('HH:mm')
  } else {
    return dayjs(announcement.start_date).format('YYYY')
  }
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.highlights')">
        <div class="flex flex-col gap-5 items-center justify-center w-full ">
            <div class="flex flex-row gap-5 overflow-x-auto w-full">
                <div
                    v-for="announcement in pinnedAnnouncements"
                    class="relative rounded-lg w-[400px] h-[225px] flex-shrink-0 overflow-hidden hover:opacity-60 hover:cursor-pointer"
                    :class="{ 'bg-black': !announcement.thumbnail }"
                    @click="openDialog(announcement)"
                    >
                    <!-- Image -->
                    <img
                        :src="announcement.thumbnail"
                        alt="cover"
                        class="w-full h-full object-fill"
                    />
                    <!-- reminder that justify-end does not work with line-clamp (use mt in child of flex container instead) -->
                    <!-- <div
                        class="absolute inset-0 p-3 flex flex-col items-start overflow-hidden"
                    >
                        <div class="mt-auto flex flex-col gap-1 max-h-[112px] w-full overflow-hidden">
                            <span class="font-semibold text-gray-100 w-full line-clamp-1">
                            {{ announcement.title }}
                            </span>
                            <span class="text-sm text-gray-100 w-full line-clamp-2" v-html="announcement.content">
                            </span>
                        </div>
                    </div> -->
                </div>
            </div>

            <div class="flex flex-col gap-3 p-6 items-center justify-center rounded-md shadow-card bg-white self-stretch">
                <span class="text-gray-950 font-bold self-stretch">{{ $t('public.announcements') }}</span>
                <div class="flex flex-col self-stretch gap-1 md:gap-0">
                    <div v-for="announcement in visibleAnnouncements" class="flex flex-row gap-2 md:gap-5 self-stretch py-2 md:py-5 hover:opacity-60 hover:cursor-pointer" @click="openDialog(announcement)">
                        <div class="flex flex-col gap-1 w-[60px] flex-shrink-0">
                            <span class="font-semibold text-gray-500">
                                {{ isToday(announcement.start_date) ? $t('public.today') : dayjs(announcement.start_date).format('MMM DD') }}
                            </span>
                            <span class="text-gray-500">
                                {{ getTimeLabel(announcement) }}
                            </span>
                        </div>
                        <Divider layout="vertical"/>
                        <div class="flex flex-col gap-1 w-full md:flex-row md:gap-5">
                            <div class="flex flex-col gap-1 w-full">
                                <span class="font-semibold text-gray-950 md:line-clamp-1">
                                    {{ announcement.title }}
                                </span>
                                <span class="text-sm text-gray-700 hidden md:line-clamp-4" v-html="announcement.content">
                                </span>
                            </div>
                            <img v-if="announcement.thumbnail" :src="announcement.thumbnail" alt="announcement_image" class="w-40 h-[90px] flex-shrink-0 \n">
                        </div>
                        <!-- <img v-if="announcement.thumbnail" :src="announcement.thumbnail" alt="announcement_image" class="w-40 h-[90px] flex-shrink-0 hidden md:flex"> -->
                    </div>
                </div>
                <Button
                    v-if="visibleCount < announcements.length"
                     @click="loadMore"
                    type="button"
                    variant="primary-outlined"
                    size="lg"
                >
                    {{ $t('public.load_more') }}
                </Button>
            </div>
        </div>
    </AuthenticatedLayout>

    <Dialog v-model:visible="visible" modal :header="$t('public.announcement')" :closable="false" class="dialog-xs md:dialog-md no-header-border" :dismissableMask="true">
        <div class="flex flex-col justify-center items-start gap-8 pb-6 self-stretch">
            <img v-if="data.thumbnail" :src="data.thumbnail" alt="announcement_image" class="w-full h-[144px] md:h-[310.5px]" />

            <span class="text-lg font-bold text-gray-950">{{ data.title }}</span>

            <!-- need to ask nic about this content if got html tag -->
            <span class="text-md font-regular text-gray-950 whitespace-pre-line" v-html="data.content"></span>

        </div>
        <Button
            @click="closeDialog"
            type="button"
            variant="primary-flat"
            class="w-full"
            size="base"
        >
            {{ $t('public.close') }}
        </Button>
    </Dialog>
</template>

<!-- <style scoped>
.horizontal-scrollpanel {
  width: 100%;
  height: 250px;
  position: relative;
}

/* 1. Enable horizontal scrolling */
.horizontal-scrollpanel ::v-deep(.p-scrollpanel-wrapper) {
  overflow-x: auto !important;
  overflow-y: hidden !important;
  height: 100%;
}

/* 2. Force horizontal layout */
.horizontal-scrollpanel ::v-deep(.p-scrollpanel-content) {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  min-width: max-content; /* Forces horizontal overflow */
  height: 100%;
}

/* 3. Structure for your items */
.scroll-inner {
  display: flex;
  gap: 1.25rem; /* Tailwind's gap-5 */
}
</style> -->