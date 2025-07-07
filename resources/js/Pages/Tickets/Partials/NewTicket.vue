<script setup>
import Button from "@/Components/Button.vue";
import Dialog from 'primevue/dialog';
import {h, ref, watch, computed, watchEffect} from "vue";
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from 'primevue/inputtext';
import {useForm, usePage} from '@inertiajs/vue3';
import Select from "primevue/select";
import { IconPlus, IconUpload, IconX, IconAlertTriangle, IconSearch, IconCircleXFilled } from "@tabler/icons-vue";
import { transactionFormat } from "@/Composables/index.js";
import Textarea from "primevue/textarea";
import { trans, wTrans } from "laravel-vue-i18n";
import debounce from "lodash/debounce.js";
import dayjs from "dayjs";
import Empty from "@/Components/Empty.vue";
import {useLangObserver} from "@/Composables/localeObserver.js";

const {locale} = useLangObserver();

const { formatAmount, formatDate } = transactionFormat();

const visible = ref(false)

const openDialog = () => {
    form.reset();
    form.clearErrors();
    removeAttachment();
    visible.value = true;
}

// const closeDialog = () => {
//     visible.value = false;
// }

const form = useForm({
    category: null,
    subject: '',
    description: '',
    ticket_attachment: [],
});

const selectedAttachments = ref([]); // Array for up to 2 images

const handleAttachment = (event) => {
    const files = Array.from(event.target.files);

    // if (files.length > 2) {
    //     alert("You can only upload up to 2 files.");
    //     return;
    // }

    selectedAttachments.value = [];
    form.ticket_attachment = [];

    files.forEach((file) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            selectedAttachments.value.push({
                name: file.name,
                url: e.target.result,
                file: file,
            });
        };
        reader.readAsDataURL(file);
        form.ticket_attachment.push(file);
    });
};

const removeAttachment = (index) => {
    selectedAttachments.value.splice(index, 1);
    form.ticket_attachment.splice(index, 1);
};

const submitForm = () => {
    // console.log(form);
    form.post(route('tickets.createTicket'), {
        // onError: () => {
        //     console.log(form.errors)
        // },
        onSuccess: () => {
            visible.value = false;
            form.reset();
            removeAttachment();
        },
    });
}

const loading = ref(false);
const categories = ref([]);
const is_blocked = ref(true);
const getResults = async () => {
    loading.value = true;

    try {
        let url = `/getTicketSettings`;

        const response = await axios.get(url);
        categories.value = response.data.categories;
        is_blocked.value = response.data.is_blocked;
        // console.log(categories)
        // console.log(schedule_status)
    } catch (error) {
        console.error('Error getting categories:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

</script>

<template>
    <Button
        type="button"
        variant="primary-flat"
        size="sm"
        class="w-full md:w-auto"
        @click="openDialog()"
    >
        {{ $t('public.new_ticket') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.new_ticket')"
        class="dialog-xs md:dialog-md"
        :closeOnEscape="false"
    >
        <template v-if="!!is_blocked">
            <div class="w-full flex flex-col justify-center items-stretch gap-4 pt-6 self-stretch">
                <Empty 
                    :title="$t('public.new_ticket_unavailable_title')" 
                    :message="$t('public.new_ticket_unavailable_message')" 
                />
                <Button
                    type="button"
                    size="base"
                    class="w-full"
                    variant="primary-flat"
                    @click="visible=false"
                >
                    {{ $t('public.close') }}
                </Button>
            </div>
        </template>
        <template v-else>
            <form @submit.prevent="submitForm()">
                <div class="flex flex-col py-4 gap-6 self-stretch md:py-6 md:gap-8">
                    <div class="flex flex-col gap-3">
                        <div class="flex flex-col gap-5">
                            <div class="flex flex-col gap-2">
                                <InputLabel
                                    for="category"
                                    :value="$t('public.category')"
                                />
                                <Select
                                    v-model="form.category"
                                    :options="categories"
                                    :optionLabel="(option) => option.name[locale]"
                                    optionValue="value"
                                    :placeholder="$t('public.category_placeholder')"
                                    class="w-full font-normal"
                                    scroll-height="236px"
                                    :invalid="!!form.errors.category"
                                />
                                <InputError :message="form.errors.category" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <InputLabel
                                    for="subject"
                                    :value="$t('public.subject')"
                                />
                                <InputText
                                    id="subject"
                                    type="text"
                                    class="block w-full"
                                    v-model="form.subject"
                                    :placeholder="$t('public.subject_placeholder')"
                                    :invalid="!!form.errors.subject"
                                />
                                <InputError :message="form.errors.subject" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <InputLabel
                                    for="description"
                                    :value="$t('public.description')"
                                />
                                <Textarea
                                    id="description"
                                    type="text"
                                    class="w-full h-24"
                                    v-model="form.description"
                                    :placeholder="$t('public.description_placeholder')"
                                    :invalid="!!form.errors.description"
                                    rows="5"
                                    cols="30"
                                />
                                <span class="self-stretch text-gray-500 text-xs">{{ $t('public.description_caption') }}</span>
                                <InputError :message="form.errors.description" />
                            </div>
                            <div class="flex flex-col gap-3">
                                <div class="flex flex-col gap-3">
                                    <InputLabel for="attachment">
                                        {{ $t('public.attachments_optional') }}
                                    </InputLabel>
                                    <span class="self-stretch text-gray-500 text-xs">{{ $t('public.ticket_attachment_caption') }}</span>
                                </div>
                                <div class="flex flex-col gap-3">
                                    <input
                                        ref="attachmentInput"
                                        id="attachment"
                                        type="file"
                                        class="hidden"
                                        accept="image/*"
                                        @change="handleAttachment"
                                        multiple
                                    />
                                    <Button
                                        type="button"
                                        variant="primary-tonal"
                                        @click="$refs.attachmentInput.click()"
                                        class="w-fit"
                                    >
                                        <IconUpload size="20" color="#2E7D32" stroke-width="1.25" />
                                        {{ $t('public.choose') }}
                                    </Button>
                                    <InputError :message="form.errors.ticket_attachment" />
                                </div>
                                <div v-if="selectedAttachments.length" class="grid grid-cols-2 gap-2 self-stretch">
                                    <div 
                                        v-for="(file, index) in selectedAttachments"
                                        :key="index"
                                        class="flex items-center gap-3 w-full p-2 bg-gray-100 rounded-xl justify-between"
                                    >
                                        <div class="inline-flex items-center gap-3 truncate">
                                            <img :src="file.url" alt="Selected Image" class="w-8 h-6 md:w-16 md:h-12 object-contain rounded" />
                                            <div class="text-sm text-gray-950 truncate">
                                                {{ file.name }}
                                            </div>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="gray-text"
                                            @click="removeAttachment(index)"
                                            pill
                                            iconOnly
                                        >
                                            <IconX class="text-gray-700 w-5 h-5" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-full md:flex md:flex-row grid grid-cols-2 justify-center items-stretch gap-4 pt-6 self-stretch">
                    <Button
                        type="button"
                        size="base"
                        class="w-full"
                        variant="gray-outlined"
                        @click="visible=false"
                    >
                        {{ $t('public.cancel') }}
                    </Button>
                    <Button
                        type="button"
                        variant="primary-flat"
                        size="base"
                        class="w-full"
                        @click="submitForm()"
                        :disabled="form.processing"
                    >
                        {{ $t('public.submit_ticket') }}
                    </Button>
                </div>
            </form>
        </template>
    </Dialog>

    <!-- <Dialog v-model:visible="previewVisible" modal :header="$t('public.preview')"  class="dialog-xs md:dialog-md no-header-border" :dismissableMask="true">
        <div class="flex flex-col justify-center items-start gap-8 pb-6 self-stretch">
            <img v-if="data.thumbnail" :src="selectedAttachment" :alt="data.thumbnail.name" class="w-full h-[144px] md:h-[310.5px]" />

            <span class="text-lg font-bold text-gray-950">{{ data.subject }}</span>

            <span class="text-md font-regular text-gray-950 whitespace-pre-line" v-html="data.message"></span>

        </div>
    </Dialog> -->
</template>
