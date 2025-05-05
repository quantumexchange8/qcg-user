<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { EyeIcon, EyeOffIcon, CheckIcon } from '@heroicons/vue/outline'
import {
    IconX,
    IconUpload
} from "@tabler/icons-vue"
import GuestLayout from '@/Layouts/Guest.vue'
import Input from 'primevue/inputtext'
import Label from '@/Components/InputLabel.vue'
import Button from '@/Components/Button.vue'
import Select from 'primevue/select'
import {ref, watch, computed, watchEffect} from "vue";
import InputError from "@/Components/InputError.vue";
import CountryLists from '/public/data/countries.json'
import Stepper from 'primevue/stepper';
import StepList from 'primevue/steplist';
import StepPanels from 'primevue/steppanels';
import Step from 'primevue/step';
import StepPanel from 'primevue/steppanel';
import {KycFemale, KycMale} from "@/Components/Icons/solid.jsx";

const props = defineProps({
    countries: Array,
    referral_code: String
})

const activeStep = ref(1);

const countryList = ref(props.countries); 
const selectedCountry = ref();
const showPassword = ref(false);
const showPassword2 = ref(false);

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};


const togglePasswordVisibilityConfirm = () => {
    showPassword2.value = !showPassword2.value;
}

const form = useForm({
    first_name: '',
    chinese_name: '',
    email: '',
    country: '',
    phone_code: '',
    phone: '',
    phone_number: '',
    password: '',
    password_confirmation: '',
    referral_code: props.referral_code ? props.referral_code : '',
    kyc_verification: [],
    form_step: 1,
});

const submit = () => {
    form.post(route('sign_up'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}

const validate = (activateCallback) => {
    // console.log(selectedCountry.value);
    if(selectedCountry.value){
        form.country = selectedCountry.value.name;
        form.phone_code = selectedCountry.value.phone_code;
        form.phone_number = selectedCountry.value.phone_code + form.phone;
    }

    form.post(route('sign_up.first.step'), {
        onSuccess: () => {
            // activateCallback(2);

            // if (form.form_step < 3) {
            //     form.form_step += 1;
            //     activeStep.value = form.form_step; // Track active step
            //     activateCallback(activeStep.value);
            //     // activateCallback(form.form_step);
            //     console.log(form.form_step)
            //     console.log(form)
            // }
            if (activeStep.value < 3) {
                activeStep.value += 1; // Increase step
                form.form_step = activeStep; 
                activateCallback(activeStep.value); // Update step panel
            }
        },
    });
}

// const selectedKycVerification = ref(null);
const selectedKycVerificationName = ref(null);
// const handleKycVerification = (event) => {
//     const kycVerificationInput = event.target;
//     const file = kycVerificationInput.files[0];

//     if (file) {
//         // Display the selected image
//         const reader = new FileReader();
//         reader.onload = () => {
//             selectedKycVerification.value = reader.result;
//         };
//         reader.readAsDataURL(file);
//         selectedKycVerificationName.value = file.name;
//         form.kyc_verification = event.target.files[0];
//     } else {
//         selectedKycVerification.value = null;
//     }
// };

// const removeKycVerification = () => {
//     selectedKycVerification.value = null;
//     form.kyc_verification = '';
// };

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

</script>

<template>
    <GuestLayout :title="$t('public.sign_up')">
        <form class="w-full">
            <Stepper v-model:value="activeStep" linear>
                <StepList class="hidden md:flex">
                    <Step :value="1">{{$t('public.basic_details')}}</Step>
                    <Step :value="2">{{$t('public.set_password')}}</Step>
                    <Step :value="3">{{$t('public.kyc_verification')}}</Step>
                </StepList>
                <StepPanels class="max-w-md mx-auto">
                    <StepPanel v-slot="{ activateCallback }" :value="1">
                        <div class="grid gap-6">
                            <div class ="grid gap-3 text-center">
                                <p class="text-gray-950 text-xl font-bold">
                                    {{$t('public.basic_details')}}
                                </p>
                                <p class="text-gray-500">
                                    {{$t('public.details_caption')}}
                                </p>
                            </div>

                            <div class="space-y-4">
                                <div class="space-y-1.5">
                                    <Label
                                        for="first_name"
                                        :value="$t('public.full_name')"
                                    />
                                    <Input
                                        id="first_name"
                                        type="text"
                                        class="block w-full"
                                        :placeholder="$t('public.name_placeholder')"
                                        autofocus
                                        v-model="form.first_name"
                                        :invalid="!!form.errors.first_name"
                                    />
                                    <InputError :message="form.errors.first_name" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label
                                        for="chinese_name"
                                    >{{ $t('public.chinese_name') }}</Label>
                                    <Input
                                        id="chinese_name"
                                        type="text"
                                        class="block w-full"
                                        :placeholder="$t('public.chinese_name_placeholder')"
                                        autofocus
                                        v-model="form.chinese_name"
                                    />
                                </div>

                                <div class="space-y-1.5">
                                    <Label
                                        for="email"
                                        :value="$t('public.email')"
                                    />
                                    <Input
                                        id="email"
                                        type="email"
                                        class="block w-full"
                                        :placeholder="$t('public.enter_email')"
                                        v-model="form.email"
                                        autocomplete="email"
                                        :invalid="!!form.errors.email"
                                    />
                                    <InputError :message="form.errors.email" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label
                                        for="phone"
                                        :value="$t('public.phone_number')"
                                    />
                                    <div class="flex gap-2 items-center">
                                        <Select filter :filterFields="['name', 'phone_code']" v-model="selectedCountry" :options="countryList" optionLabel="name" :placeholder="$t('public.phone_code')" class="min-w-[110px] max-w-[110px]"
                                        :invalid="!!form.errors.phone_code">
                                            <template #value="slotProps" >
                                                <div v-if="slotProps.value" class="flex items-center">
                                                    <div class="text-black">{{ slotProps.value.phone_code }}
                                                    </div>
                                                </div>
                                                <span v-else>{{ $t('public.phone_code') }}</span>
                                            </template>
                                            <template #option="slotProps">
                                                <div class="flex items-center">
                                                    <div class="text-black">{{ slotProps.option.name }} ({{ slotProps.option.phone_code }})</div>
                                                </div>
                                            </template>
                                        </Select>

                                        <Input
                                            id="phone"
                                            type="text"
                                            class="block w-full"
                                            :placeholder="$t('public.phone_number')"
                                            v-model="form.phone"
                                            :invalid="!!form.errors.phone"
                                        />
                                    </div>
                                    <InputError :message="form.errors.phone_code"/>
                                    <InputError :message="form.errors.phone"/>
                                </div>
                            </div>

                            <div class="grid items-center gap-6">
                                <Button type="button" variant="primary-flat" size="base" class="w-full px-4 py-3" @click="() => validate(activateCallback)" >
                                    <span>{{ $t('public.continue') }}</span>
                                </Button>

                                <p class="flex gap-3 text-sm text-gray-700 font-medium text-center justify-center">
                                    {{ $t('public.have_account') }}
                                    <Link :href="route('login')" class="font-semibold text-primary-500 hover:text-primary-600 focus:underline focus:text-primary-600">
                                        {{ $t('public.log_in') }}
                                    </Link>
                                </p>
                            </div>
                        </div>
                    </StepPanel>
                    <StepPanel v-slot="{ activateCallback }" :value="2">
                        <div class="grid gap-6">
                            <div class ="grid gap-3 text-center">
                                <p class="text-gray-950 text-xl font-bold">
                                    {{$t('public.set_password')}}
                                </p>
                                <p class="text-gray-500">
                                    {{$t('public.choose_password_caption')}}
                                </p>
                            </div>

                            <div class="space-y-4">
                                <div class="space-y-1.5">
                                    <Label for="password" :value="$t('public.password')" />
                                    <div class="relative">
                                        <Input
                                            id="password"
                                            :type="showPassword ? 'text' : 'password'"
                                            class="block w-full"
                                            placeholder="••••••••"
                                            :invalid="!!form.errors.password"
                                            v-model="form.password"
                                        />
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                                            @click="togglePasswordVisibility"
                                        >
                                            <template v-if="showPassword">
                                                <EyeIcon aria-hidden="true" class="w-5 h-5 text-gray-500" />
                                            </template>
                                            <template v-else>
                                                <EyeOffIcon aria-hidden="true" class="w-5 h-5 text-gray-500" />
                                            </template>
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{$t('public.password_rule')}}
                                    </div>

                                    <InputError :message="form.errors.password" class="mt-2" />
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="password_confirmation" :value="$t('public.confirm_password')" />
                                    <div class="relative">
                                        <Input
                                            id="password_confirmation"
                                            :type="showPassword2 ? 'text' : 'password'"
                                            class="block w-full"
                                            placeholder="••••••••"
                                            :invalid="!!form.errors.password"
                                            v-model="form.password_confirmation"
                                        />
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                                            @click="togglePasswordVisibilityConfirm"
                                        >
                                            <template v-if="showPassword2">
                                                <EyeIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                            </template>
                                            <template v-else>
                                                <EyeOffIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                            </template>
                                        </div>
                                    </div>
                                    <InputError :message="form.errors.password_confirmation" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid items-center gap-4">
                                <Button type="button" variant="primary-flat" size="base" class="w-full px-4 py-3" @click="() => validate(activateCallback)" >
                                    <span>{{ $t('public.continue') }}</span>
                                </Button>
                                <!-- <Button variant="primary-flat" size="base" class="w-full px-4 py-3" @click="submit" :disabled="form.processing">
                                    <span>{{ $t('public.sign_up') }}</span>
                                </Button> -->

                                <Button
                                type="button"
                                variant="primary-text"
                                @click="() => {
                                    if (activeStep > 1) {
                                        activeStep -= 1;
                                        activateCallback(activeStep);
                                    }
                                }"
                                class="px-4 py-3"
                                >
                                    <span>{{ $t('public.previous_page') }}</span>
                                </Button>
                            </div>
                        </div>
                    </StepPanel>
                    <StepPanel v-slot="{ activateCallback }" :value="3">
                        <div class="grid gap-8">
                            <div class ="grid gap-3 text-center">
                                <span class="text-gray-950 text-xl font-bold">
                                    {{$t('public.kyc_verification')}}
                                </span>
                                <span class="text-gray-500">
                                    {{$t('public.kyc_verification_caption')}}
                                </span>
                            </div>

                            <div class="flex flex-col items-center gap-3 self-stretch">
                                <span class="text-xs text-gray-500">{{$t('public.kyc_instruction')}}</span>

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
                                        <span class="text-xs text-gray-500">{{ $t('public.kyc_attachment_caption') }}</span>
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
                                        <!-- <div
                                            v-if="selectedKycVerification"
                                            class="relative w-full py-3 pl-4 flex justify-between rounded-xl bg-gray-50"
                                        >
                                            <div class="inline-flex items-center gap-3">
                                                <img :src="selectedKycVerification" alt="Selected Image" class="max-w-full h-9 object-contain rounded" />
                                                <div class="text-sm text-gray-950">
                                                    {{ selectedKycVerificationName }}
                                                </div>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="gray-text"
                                                @click="removeKycVerification"
                                                pill
                                                iconOnly
                                            >
                                                <IconX class="text-gray-700 w-5 h-5" />
                                            </Button>
                                        </div> -->
                                    </div>
                                </div>
                            </div>

                            <div class="grid items-center gap-4">
                                <Button variant="primary-flat" size="base" class="w-full px-4 py-3" @click="submit" :disabled="form.processing">
                                    <span>{{ $t('public.sign_up') }}</span>
                                </Button>

                                <Button
                                    type="button"
                                    variant="primary-text"
                                    @click="() => {
                                        if (activeStep > 1) {
                                            activeStep -= 1;
                                            activateCallback(activeStep);
                                        }
                                    }"
                                    class="px-4 py-3"
                                    :disabled="form.processing"
                                >
                                    <span>{{ $t('public.previous_page') }}</span>
                                </Button>
                            </div>
                        </div>
                    </StepPanel>
                </StepPanels>
            </Stepper>
        </form>
    </GuestLayout>
</template>
