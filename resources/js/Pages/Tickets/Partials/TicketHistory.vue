<script setup>
import {ref, watch, watchEffect} from "vue";
import dayjs from "dayjs";
import {wTrans} from "laravel-vue-i18n";
import {usePage, useForm, router} from "@inertiajs/vue3";
import {usePermission} from "@/Composables/permissions.js";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import StatusBadge from '@/Components/StatusBadge.vue';
import {useLangObserver} from "@/Composables/localeObserver.js";
import Loader from "@/Components/Loader.vue";
import Dialog from "primevue/dialog";
import TicketReplies from "@/Pages/Tickets/Partials/TicketReplies.vue";

const {locale} = useLangObserver();

const visible = ref(false);
const loading = ref(false);
const dt = ref(null);
const tickets = ref();
const status = ref('resolved');
// const { hasPermission } = usePermission();

const getResults = async () => {
    loading.value = true;

    try {
        let url = `/tickets/getTickets?ticket_status=${status.value}`;

        const response = await axios.get(url);
        tickets.value = response.data.tickets;
        // console.log(tickets.value)
    } catch (error) {
        console.error('Error getting tickets:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

// dialog
const data = ref({});
const openDialog = (rowData) => {
    visible.value = true;
    data.value = rowData;
};

const visiblePhoto = ref(false);
const selectedAttachment = ref(null);
const openPhotoDialog = (attachment) => {
    visiblePhoto.value = true;
    selectedAttachment.value = attachment;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});


</script>

<template>

<DataTable
        :value="tickets"
        removableSort
        ref="dt"
        :loading="loading"
        selectionMode="single"
        @row-click="(event) => openDialog(event.data)"
    >
        <template #empty>
            <!-- <Empty 
                :title="$t('public.empty_pending_tickets_title')" 
                :message="$t('public.empty_pending_tickets_message')" 
            /> -->
        </template>
        <template #loading>
            <div class="flex flex-col gap-2 items-center justify-center">
                <Loader />
                <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
            </div>
        </template>
        <template v-if="tickets?.length > 0">
            <Column field="ticket_id" sortable :header="$t('public.ticket_id')" class="hidden md:table-cell md:w-[20%] lg:w-[15%] xl:w-[10%]">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm">
                        #{{ String(slotProps.data.ticket_id).padStart(6, '0') }}
                    </div>
                </template>
            </Column>
            <Column field="status" :header="$t('public.status')" class="hidden md:table-cell md:w-[20%] lg:w-[15%] xl:w-[10%]">
                <template #body="slotProps">
                    <div class="flex items-center">
                        <StatusBadge :variant="slotProps.data.status" :value="$t('public.' + slotProps.data.status)" />
                    </div>
                </template>
            </Column>
            <Column field="subject" :header="$t('public.subject')" headerClass="hidden" class="md:table-cell md:w-[30%] lg:w-[35%] xl:w-[40%] max-w-0">
                <template #body="slotProps">
                    <div class="flex flex-col items-start max-w-full gap-1 truncate">
                        <div class="text-gray-950 text-sm font-semibold md:font-normal truncate max-w-full">
                            {{ slotProps.data.subject || '-' }}
                        </div>
                        <div class="flex flex-row md:hidden max-w-full gap-1 items-center text-gray-500 text-xs truncate">
                            <div class="w-1.5 h-1.5 flex-shrink-0" 
                                :class="{
                                    'bg-success-500': slotProps.data.status === 'resolved', 
                                    'bg-info-500': slotProps.data.status === 'new',
                                    'bg-warning-500': slotProps.data.status === 'in_progress',
                                }"
                            ></div>
                            <div>
                                {{ $t('public.' + slotProps.data.status) }}
                            </div>
                            <div>|</div>
                            <div>
                                #{{ String(slotProps.data.ticket_id).padStart(6, '0') }}
                            </div>
                            <div>|</div>
                            <div class="truncate">
                                {{ slotProps.data?.category[locale] }}
                            </div>
                        </div>
                    </div>
                </template>
            </Column>
            <Column field="category" :header="$t('public.category')" class="hidden md:table-cell md:w-[30%] lg:w-[35%] xl:w-[40%]">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm">
                        {{ slotProps.data?.category[locale] }}
                    </div>
                </template>
            </Column>
        </template>
    </DataTable>

    <Dialog v-model:visible="visible" modal :header="$t('public.ticket')" class="dialog-xs md:dialog-lg">
        <template #header>
            <div class="flex flex-col gap-0 md:gap-1 justify-center max-w-full truncate">
                <span class="text-gray-950 text-base md:text-lg font-semibold">#{{ String(data.ticket_id).padStart(6, '0') }}</span>
                <div class="flex flex-row gap-2 md:gap-3 items-center text-gray-500 text-xs md:text-sm max-w-full truncate">
                    <StatusBadge :variant="data.status" :value="$t('public.' + data.status)" class="hidden md:flex"/>
                    <div class="flex flex-row gap-1 items-center md:hidden">
                        <div class="w-1.5 h-1.5 flex-shrink-0" 
                            :class="{
                                'bg-success-500': data.status === 'resolved', 
                                'bg-info-500': data.status === 'new',
                                'bg-warning-500': data.status === 'in_progress',
                            }"
                        ></div>
                        <span>{{ $t(`public.${data.status}`) }}</span>
                    </div>
                    <div>|</div>
                    <span class="truncate">{{ data.category[locale] }}</span>
                </div>
            </div>
        </template>
        <div class="flex flex-col justify-center items-center gap-5 self-stretch pt-4 md:pt-6">
            <div class="flex flex-col items-center gap-2 self-stretch">
                <div class="flex flex-row justify-between items-center self-stretch">
                    <span class="text-xs font-semibold text-gray-950">{{ data.name }}</span>
                    <span class="text-xs text-gray-500">{{ dayjs(data.created_at).format('YYYY/MM/DD HH:mm') }}</span>
                </div>
                <div class="flex flex-col p-2 justify-center items-center gap-2 self-stretch rounded bg-primary-100">
                    <span class="text-sm font-semibold text-gray-950 self-stretch">{{ data.subject }}</span>
                    <span class="text-sm text-gray-950 self-stretch">{{ data.description }}</span>
                    <div v-if="data.ticket_attachments.length !== 0" class="grid grid-cols-2 md:grid-cols-3 gap-2 self-stretch">
                        <div v-for="file in data.ticket_attachments" :key="file.id" @click="openPhotoDialog(file)" 
                            class="flex items-center gap-3 w-full p-2 bg-white rounded border border-gray-200 cursor-pointer hover:bg-gray-100"
                        >
                            <img :src="file.original_url" :alt="file.file_name" class="w-8 h-6 md:w-16 md:h-12 rounded" />
                            <span class="text-sm text-gray-700 truncate">{{ file.file_name }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <TicketReplies 
                :ticket_id="data.ticket_id"
            />
        </div>

    </Dialog>

    <Dialog v-model:visible="visiblePhoto" modal headless class="dialog-xs md:dialog-md" :dismissableMask="true">
        <img
            :src="selectedAttachment?.original_url"
            class="w-full"
            alt="attachment"
        />
    </Dialog>
</template>