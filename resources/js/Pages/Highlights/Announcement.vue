<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import { ref, computed } from "vue";
import Button from "@/Components/Button.vue";
import Divider from 'primevue/divider';
import Dialog from 'primevue/dialog';
// import PerfectScrollbar from '@/Components/PerfectScrollbar.vue'
import dayjs from 'dayjs';

const announcements = ref([]);

const getAnnouncementData = async () => {
    try {
        const response = await axios.get('/highlights/getAnnouncement');
        announcements.value = response.data.announcements;
    } catch (error) {
        console.error('Error getting announcement:', error);
    }
};

getAnnouncementData();

const visible = ref(false);
const data = ref({});
const openDialog = (announcementData) => {
    visible.value = true;
    data.value = announcementData;
};
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.highlights')">
        <div class="flex flex-col gap-5 items-center justify-center self-stretch">
            <!-- <PerfectScrollbar>
                <div class="flex flex-row gap-5 px-4">
                    <div
                    v-for="announcement in announcements"
                    class="rounded-lg w-[400px] h-[225px] flex-shrink-0 bg-black"
                    @click="openDialog(announcement)"
                    >
                    <div class="flex flex-col p-3 gap-1 w-full h-full justify-end">
                        <span class="font-semibold text-white">{{ announcement.title }}</span>
                        <span class="text-sm text-white">{{ announcement.content }}</span>
                    </div>
                    </div>
                </div>
            </PerfectScrollbar> -->
            <!-- <div class="w-full overflow-x-auto">
                <div class="flex flex-row gap-5 px-4">
                    <div
                    v-for="announcement in announcements"
                    class="rounded-lg w-[400px] h-[225px] flex-shrink-0 bg-black"
                    @click="openDialog(announcement)"
                    >
                    <div class="flex flex-col p-3 gap-1 w-full h-full justify-end">
                        <span class="font-semibold text-white">{{ announcement.title }}</span>
                        <span class="text-sm text-white">{{ announcement.content }}</span>
                    </div>
                    </div>
                </div>
            </div> -->

            <div class="flex flex-col gap-3 p-6 items-center justify-center rounded-md shadow-card bg-white self-stretch">
                <span class="text-gray-950 font-bold self-stretch">{{ $t('public.announcements') }}</span>
                <div class="flex flex-col self-stretch">
                    <div v-for="announcement in announcements" class="flex flex-row gap-5 self-stretch py-5 hover:opacity-60 hover:cursor-pointer" @click="openDialog(announcement)">
                        <div class="flex flex-col gap-1 w-[60px] flex-shrink-0">
                            <span class="font-semibold text-gray-500">
                                {{ dayjs(announcement.start_date).format('MMM DD') }}
                            </span>
                            <span class="text-gray-500">
                                {{ dayjs(announcement.start_date).format('YYYY') }}
                            </span>
                        </div>
                        <Divider layout="vertical" />
                        <div class="flex flex-col gap-1 w-full">
                            <span class="font-semibold text-gray-950">
                                {{ announcement.title }}
                            </span>
                            <span class="text-sm text-gray-700">
                                {{ announcement.content }}
                            </span>
                        </div>
                        <img v-if="announcement.thumbnail" :src="announcement.thumbnail" alt="announcement_image" class="w-40 h-[90px] flex-shrink-0">
                    </div>
                </div>
                <Button
                    type="button"
                    variant="primary-outlined"
                    size="lg"
                >
                    {{ $t('public.load_more') }}
                </Button>
            </div>
        </div>
    </AuthenticatedLayout>

    <Dialog v-model:visible="visible" modal :header="$t('public.announcement')"  class="dialog-md no-header-border" :dismissableMask="true">
        <div class="flex flex-col justify-center items-start gap-8 pb-6 self-stretch">
            <img v-if="data.thumbnail" :src="data.thumbnail" alt="announcement_image" class="w-full h-[310.5px]" />

            <span class="text-lg font-bold text-gray-950">{{ data.title }}</span>

            <!-- need to ask nic about this content if got html tag -->
            <span class="text-md font-regular text-gray-950" v-html="data.content"></span>

        </div>
    </Dialog>
</template>
