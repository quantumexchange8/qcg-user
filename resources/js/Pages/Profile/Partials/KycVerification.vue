<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Password from 'primevue/password';
import { useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Button from "@/Components/Button.vue";
import { IconCircleCheckFilled, IconExclamationCircleFilled, IconClockFilled, IconCircleXFilled, IconUpload, IconX } from "@tabler/icons-vue";
import {usePage} from "@inertiajs/vue3";
import Dialog from "primevue/dialog";
import {KycFemale, KycMale} from "@/Components/Icons/solid.jsx";

const user = usePage().props.auth.user;

const visible = ref(false);
const dialogType = ref('');
const kycVerifications = ref([]);

const form = useForm({
    kyc_verification: [],
});

const resetForm = () => {
    form.reset();
}

const getKycVerification = async () => {
    try {
        const response = await axios.get('/profile/getKycVerification');
        kycVerifications.value = response.data.kycVerification;
    } catch (error) {
        console.error('Error getting kyc:', error);
    }
};

getKycVerification();

const selectedKycVerification = ref(null);
const openDialog = (type, verification = null) => {
    dialogType.value = type;
    visible.value = true;

    if (type === 'view_kyc') {
        selectedKycVerification.value = verification;
    }
}

const selectedKycVerifications = ref([]); // Array for up to 2 images

const handleKycVerification = (event) => {
    const files = Array.from(event.target.files);

    if (files.length > 2) {
        alert("You can only upload up to 2 files.");
        return;
    }

    selectedKycVerifications.value = [];
    form.kyc_verification = [];

    files.forEach((file) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            selectedKycVerifications.value.push({
                name: file.name,
                url: e.target.result,
                file: file,
            });
        };
        reader.readAsDataURL(file);
        form.kyc_verification.push(file);
    });
};

const removeKycVerification = (index) => {
    selectedKycVerifications.value.splice(index, 1);
    form.kyc_verification.splice(index, 1);
};

const submitForm = () => {
    form.post(route('profile.updateKyc'), {
        onSuccess: () => {
            visible.value = false;

            setTimeout(() => {
                window.location.reload();
            }, 1500);
        }
    })
}

</script>

<template>
    <div class="w-full flex flex-col items-center p-3 gap-8 rounded-lg bg-white shadow-card md:p-6">
        <div class="w-full flex flex-col justify-center items-start gap-1">
            <div class="w-full flex flex-row gap-2 items-center">
                <span class="text-gray-950 font-bold">{{ $t('public.kyc_verification') }}</span>
                <IconCircleCheckFilled v-if="user.kyc_approval === 'verified'" size="16" stroke-width="1.25" class="text-success-500 grow-0 shrink-0" />
                <IconClockFilled v-else-if="user.kyc_approval === 'pending'" size="16" stroke-width="1.25" class="text-warning-500 grow-0 shrink-0" />
                <IconExclamationCircleFilled v-else size="16" stroke-width="1.25" class="text-error-500 grow-0 shrink-0" />
            </div>
            <span v-if="user.kyc_approval === 'verified'" class="text-gray-500 text-xs">{{ $t('public.kyc_approved_caption') }}</span>
            <span v-else-if="user.kyc_approval === 'pending'" class="text-gray-500 text-xs">{{ $t('public.kyc_pending_caption') }}</span>
            <span v-else class="text-gray-500 text-xs">{{ $t('public.kyc_unverified_caption') }}</span>
        </div>

        <div class="flex flex-col gap-5 items-center self-stretch w-full">
            <div v-for="file in kycVerifications" :key="file.id" @click="openDialog('view_kyc', file)" 
                class="flex items-center gap-3 w-full px-3 py-4 bg-white rounded border border-gray-200 cursor-pointer hover:bg-gray-100"
            >
                <img :src="file.original_url" :alt="file.file_name" class="w-12 h-9 rounded" />
                <span class="text-sm text-gray-700">{{ file.file_name }}</span>
            </div>
        </div>

        <div v-if="user.kyc_approval === 'unverified' && user.kyc_approved_at != null" class="w-full flex flex-col justify-center items-start gap-1">
            <span class="text-error-500 text-sm font-semibold">{{ $t('public.kyc_verification_failed') }}</span>
            <div class="flex flex-col gap-2 justify-center items-start">
                <span class="text-gray-500 text-xs">{{ $t('public.kyc_rejected_reason') }}</span>
                <span class="text-gray-500 text-xs">{{ user.kyc_approval_description ?? '-' }}</span>
                <span class="text-gray-500 text-xs">{{ $t('public.kyc_rejected_caption') }}</span>
            </div>
        </div>

        <div v-if="user.kyc_approval === 'unverified'" class="flex justify-end items-center gap-4 self-stretch">
            <Button
                type="button"
                variant="primary-flat"
                @click="openDialog('submit_kyc')"
            >
                {{ user.kyc_approved_at != null ? $t('public.submit_again') : $t('public.submit_kyc_now') }}
            </Button>
        </div>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t(`public.${dialogType}`)"
        class="dialog-md"
    >
        <template v-if="dialogType === 'view_kyc'">
            <img
                :src="selectedKycVerification?.original_url || '/img/member/kyc_sample_illustration.png'"
                class="w-full"
                alt="kyc_verification"
            />
        </template>

        <template v-if="dialogType === 'submit_kyc'">
            <form>
                <div class="flex flex-col gap-6">
                    <div class ="flex flex-col gap-1">
                        <span class="text-gray-950 font-bold">
                            {{$t('public.kyc_guideline')}}
                        </span>
                        <span class="text-gray-500 text-xs">
                            {{$t('public.kyc_instruction')}}
                        </span>
                    </div>

                    <div class="flex flex-col items-center gap-3 self-stretch">
                        <div class="flex items-center gap-5 self-stretch">
                            <div class="flex justify-center bg-primary-500 w-full pt-2.5">
                                <KycFemale />
                            </div>
                            <div class="flex justify-center bg-primary-900 w-full pt-2.5">
                                <KycMale />
                            </div>
                        </div>

                        <div class="flex flex-col items-center self-stretch">
                            <div class="text-gray-950 font-semibold text-sm self-stretch">
                                {{ $t('public.kyc_upload') }}
                            </div>
                            <div class="flex flex-col gap-3 items-start self-stretch">
                                <span class="text-xs text-gray-500">{{ $t('public.attachment_caption') }}</span>
                                <div class="flex flex-col gap-3">
                                    <input
                                        ref="kycVerificationInput"
                                        id="kyc_verification"
                                        type="file"
                                        class="hidden"
                                        accept="image/*"
                                        @change="handleKycVerification"
                                        multiple
                                    />
                                    <Button
                                        type="button"
                                        variant="primary-tonal"
                                        @click="$refs.kycVerificationInput.click()"
                                    >
                                        <IconUpload size="16" stroke-width="1.25" />
                                        {{ $t('public.choose') }}
                                    </Button>
                                </div>
                                <div v-if="selectedKycVerifications.length" class="flex flex-col gap-2 w-full">
                                    <div 
                                        v-for="(file, index) in selectedKycVerifications"
                                        :key="index"
                                        class="relative py-3 pl-4 flex justify-between rounded-xl bg-gray-50"
                                    >
                                        <div class="inline-flex items-center gap-3">
                                            <img :src="file.url" alt="Selected Image" class="max-w-full h-9 object-contain rounded" />
                                            <div class="text-sm text-gray-950">
                                                {{ file.name }}
                                            </div>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="gray-text"
                                            @click="removeKycVerification(index)"
                                            pill
                                            iconOnly
                                        >
                                            <IconX class="text-gray-700 w-5 h-5" />
                                        </Button>
                                    </div>
                                </div>
                                <InputError :message="form.errors.kyc_verification" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 items-center gap-4">
                        <Button type="button" variant="gray-outlined" size="base" class="w-full px-4 py-3" @click="visible=false">
                            <span>{{ $t('public.cancel') }}</span>
                        </Button>

                        <Button
                            variant="primary-flat"
                            class="w-full px-4 py-3"
                            :disabled="form.processing"
                            @click.prevent="submitForm"
                        >
                            <span>{{ $t('public.submit') }}</span>
                        </Button>
                    </div>
                </div>
            </form>
        </template>
    </Dialog>
</template>
