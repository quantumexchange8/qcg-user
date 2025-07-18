<script setup>
import Button from "@/Components/Button.vue";
import {
    IconEdit,
    IconX,
    IconCheck,
    IconUpload
} from "@tabler/icons-vue";
import Dialog from "primevue/dialog";
import {ref} from "vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputText from "primevue/inputtext";
import {useForm} from "@inertiajs/vue3";
import TipTapEditor from "@/Components/TipTapEditor.vue";
import Avatar from 'primevue/avatar';

const props = defineProps({
    authorName: String,
})

const visible = ref(false);

const form = useForm({
    display_name: props.authorName,
    subject: '',
    message: '',
    attachment: ''
})

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
        form.attachment = event.target.files[0];
    } else {
        selectedAttachment.value = null;
    }
};

const removeAttachment = () => {
    selectedAttachment.value = null;
    form.attachment = '';
};

const submitForm = () => {
    form.post(route('forum.createPost'), {
        onSuccess: () => {
            visible.value = false;
            form.reset();
        }
    })
}

const closeDialog = () => {
    visible.value = false;
    form.reset();
}
</script>

<template>
    <Button
        type="button"
        variant="primary-flat"
        size="base"
        @click="visible = true"
        class="w-full md:max-w-40 self-end"
    >
        <IconEdit size="20" stroke-width="1.25" />
        {{ $t('public.create_post') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.create_post')"
        class="dialog-xs md:dialog-md"
    >
        <form @submit.prevent="submitForm()">
            <div class="flex flex-col py-6 gap-8 items-center self-stretch">
                <div class="flex flex-col gap-5 items-center self-stretch">
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="display_name"
                            :value="$t('public.display_name')"
                            :invalid="!!form.errors.display_name"
                        />
                        <InputText
                            id="display_name"
                            type="text"
                            class="block w-full"
                            v-model="form.display_name"
                            :placeholder="$t('public.eg_excellent_financial_analyst')"
                            :invalid="!!form.errors.display_name"
                            autofocus
                        />
                        <InputError :message="form.errors.display_name" />
                        <span class="text-gray-500 text-xs">{{ $t('public.display_name_caption') }}</span>
                    </div>

                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="subject"
                        >
                            {{ $t('public.subject') }}
                        </InputLabel>
                        <InputText
                            id="subject"
                            type="text"
                            class="block w-full"
                            v-model="form.subject"
                            :placeholder="$t('public.enter_subject')"
                        />
                    </div>

                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="message"
                            :invalid="!!form.errors.message"
                        >
                            {{ $t('public.message') }}
                        </InputLabel>
                        <TipTapEditor
                            v-model="form.message"
                        />
                        <InputError :message="form.errors.message" />
                    </div>
                </div>

                <div class="flex flex-col gap-3 items-start self-stretch">
                    <span class="text-sm text-gray-950 font-bold">{{ $t('public.attachment') }}</span>
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
                            <InputError :message="form.errors.kyc_verification" />
                        </div>
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

            <div class="pt-6 flex justify-end items-center gap-4 self-stretch">
                <Button
                    variant="gray-outlined"
                    size="base"
                    class="w-full"
                    @click.prevent="closeDialog()"
                    :disabled="form.processing"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    variant="primary-flat"
                    size="base"
                    class="w-full"
                    :disabled="form.processing"
                >
                    {{ $t('public.create') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
