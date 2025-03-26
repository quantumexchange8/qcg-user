<script setup>
import InputLabel from "@/Components/InputLabel.vue";
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Datepicker from 'primevue/datepicker';
import {useForm} from "@inertiajs/vue3";
import Button from "@/Components/Button.vue"
import InputError from "@/Components/InputError.vue";
import { IconInfoCircle, IconUpload, IconX } from '@tabler/icons-vue';
import {ref, watch} from "vue";
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    account: Object,
})

const {formatAmount} = transactionFormat()
const emit = defineEmits(['update:visible'])

const selectedAttachment = ref(null);
const selectedAttachmentName = ref(null);
const handleAttachment = (event) => {
    const attachmentInput = event.target;
    const file = attachmentInput.files[0];

    if (file) {
        // Display the selected image
        const reader = new FileReader();
        reader.onload = () => {
            selectedAttachment.value = reader.result;
        };
        reader.readAsDataURL(file);
        selectedAttachmentName.value = file.name;
        form.screenshot = event.target.files[0];
    } else {
        selectedAttachment.value = null;
    }
};

const removeAttachment = () => {
    selectedAttachment.value = null;
    form.screenshot = '';
};

const form = useForm({
    meta_login: props.account.meta_login,
    amount: null,
    deposit_date: '',
    txid: '',
    screenshot: '',
})

const submitForm = () => {
    form.post(route('accounts.missing_amount'), {
        onSuccess: () => {
            closeDialog();
        },
    });
}

const closeDialog = () => {
    emit('update:visible', false)
}
</script>

<template>
    <form @submit.prevent="submitForm">
        <div class="flex flex-col items-center gap-8 self-stretch md:gap-10">
            <div class="flex flex-col py-6 gap-5">
                <div class="flex flex-row gap-3 items-start">
                    <div><IconInfoCircle size="24" color="#030712" stroke-width="2"/></div>
                    <div class="flex flex-col gap-1">
                        <span class="text-sm font-semibold text-gray-950">{{ $t('public.missing_information') }}</span>
                        <span class="text-sm text-gray-700">{{ $t('public.missing_information_caption') }}</span>
                    </div>
                </div>

                <div class="flex flex-col items-start gap-2 self-stretch">
                    <InputLabel for="amount" :value="$t('public.amount')" />
                    <InputNumber
                        inputId="amount"
                        autofocus
                        prefix="$ "
                        class="block w-full"
                        :min-fraction-digits="2"
                        :max-fraction-digits="2"
                        v-model="form.amount"
                        :placeholder="'$ ' + formatAmount(0)"
                        :invalid="!!form.errors.amount"
                    />
                    <span class="self-stretch text-gray-500 text-xs">{{ $t('public.missing_amount_caption') }}</span>
                    <InputError :message="form.errors.amount" />
                </div>

                <div class="flex flex-col items-start gap-2 self-stretch">
                    <InputLabel
                        for="deposit_date"
                        :value="$t('public.deposit_date')"
                        :invalid="!!form.errors.deposit_date"
                    />
                    <Datepicker
                        v-model="form.deposit_date"
                        selectionMode="single"
                        dateFormat="yy/mm/dd"
                        showIcon
                        iconDisplay="input"
                        :placeholder="$t('public.date_placeholder')"
                        class="w-full font-normal"
                    />
                    <InputError :message="form.errors.deposit_date" />
                </div>

                <div class="flex flex-col items-start gap-2 self-stretch">
                    <InputLabel
                        for="txid"
                        :value="$t('public.txid')"
                        :invalid="!!form.errors.txid"
                    />
                    <InputText
                        id="txid"
                        type="text"
                        class="block w-full"
                        v-model="form.txid"
                        :invalid="!!form.errors.txid"
                    />
                    <InputError :message="form.errors.txid" />
                </div>

                <div class="flex flex-col gap-2 items-start self-stretch">
                    <InputLabel
                        for="upload_screenshot"
                        :value="$t('public.upload_screenshot')"
                        :invalid="!!form.errors.screenshot"
                    />
                    <div class="flex flex-col gap-3 items-start self-stretch">
                        <span class="text-xs text-gray-500">{{ $t('public.attachment_caption') }}</span>
                        <div class="flex flex-col gap-3">
                            <input
                                ref="attachmentInput"
                                id="attachment"
                                type="file"
                                class="hidden"
                                accept="image/*"
                                @change="handleAttachment"
                            />
                            <Button
                                type="button"
                                variant="primary-flat"
                                @click="$refs.attachmentInput.click()"
                            >
                                <IconUpload size="20" stroke-width="1.25" stroke-linejoin="round"/>
                                {{ $t('public.choose') }}
                            </Button>
                        </div>
                        <InputError :message="form.errors.screenshot" />

                        <div
                            v-if="selectedAttachment"
                            class="relative w-full py-3 pl-4 flex justify-between rounded-xl bg-gray-50"
                        >
                            <div class="inline-flex items-center gap-3">
                                <img :src="selectedAttachment" alt="Selected Image" class="max-w-full h-9 object-contain rounded" />
                                <div class="text-sm text-gray-950">
                                    {{ selectedAttachmentName }}
                                </div>
                            </div>
                            <Button
                                type="button"
                                variant="gray-text"
                                @click="removeAttachment"
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
        <div class="flex justify-end items-center pt-5 gap-4 self-stretch sm:pt-7">
            <Button
                type="button"
                variant="gray-tonal"
                class="w-full"
                @click="closeDialog()"
                :disabled="form.processing"
            >
                {{ $t('public.cancel') }}
            </Button>
            <Button
                type="submit"
                variant="primary-flat"
                class="w-full"
                :disabled="form.processing"
            >
                {{ $t('public.submit') }}
            </Button>
        </div>
    </form>
</template>
