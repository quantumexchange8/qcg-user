<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { EyeIcon, EyeOffIcon, CheckIcon } from '@heroicons/vue/outline'
import GuestLayout from '@/Layouts/Guest.vue'
import Input from 'primevue/inputtext'
import Label from '@/Components/InputLabel.vue'
import Button from '@/Components/Button.vue'
import Select from 'primevue/select'
import {ref, watch, computed, watchEffect} from "vue";
// import RegisterCaption from "@/Components/Auth/RegisterCaption.vue";
// import ReferralPic from "@/Components/Auth/ReferralPic.vue";
import InputError from "@/Components/InputError.vue";
import BaseListbox from 'primevue/listbox'
import CountryLists from '/public/data/countries.json'
import Checkbox from "@/Components/Checkbox.vue";

const props = defineProps({
    countries: Array,
    nationality: Array,
    referral_code: String
})

const selectedCountry = ref(null);
const countryList = ref(CountryLists); 

const formStep = ref(1);
const showPassword = ref(false);
const showPassword2 = ref(false);
const isButtonDisabled = ref(false)
const buttonText = ref('Send OTP')
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});
const signUpTerm = 'register';
const country = ref(null);

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const togglePasswordVisibilityConfirm = () => {
    showPassword2.value = !showPassword2.value;
}

const form = useForm({
    form_step: 1,
    name: '',
    chinese_name: '',
    email: '',
    username: '',
    dial_code: '',
    phone: '',
    password: '',
    password_confirmation: '',
    dob_year: '',
    dob_month: '',
    dob_day: '',
    country: '',
    front_identity: null,
    back_identity: null,
    // verification_via: 'email',
    // verification_code: '',
    referral_code: props.referral_code ? props.referral_code : '',
    terms: false,
    market: false,
    responsible: false,
    compensate: false,
    all: false,
    nationality: '',
});

watch(() => [form.terms, form.market, form.responsible, form.compensate], (newValues) => {
    form.all = newValues.every(value => value);
});

watch(() => form.all, (newValue) => {
    form.terms = newValue;
    form.market = newValue;
    form.responsible = newValue;
    form.compensate = newValue;
});

watch(country, (newCountry) => {
    if (newCountry) {
        form.country = newCountry.value;
        const foundNationality = props.nationality.find(nationality => nationality.id === newCountry.value);
        if (foundNationality) {
            form.nationality = foundNationality.value;
        } else {
            form.nationality = ''; // Reset if not found
        }
    } else {
        country.value = null;
        form.nationality = '';
    }
});

const handleFrontIdentity = (event) => {
    form.front_identity = event.target.files[0];
};

const handleBackIdentity = (event) => {
    form.back_identity = event.target.files[0];
};

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}

const nextStep = () => {
    form.post(route('register.first.step'), {
        onSuccess: () => {
            formStep.value++;
            form.form_step++;
        },
    });
}

const prevStep = () => {
    formStep.value--;
    form.form_step--;
}
const passwordRules = [
    { message: 'register_terms_1', regex: /.{6,}/ },
    { message: 'register_terms_2', regex: /[A-Z]+/ },
    { message: 'register_terms_3', regex: /[a-z]+/ },
    { message: 'register_terms_4', regex: /[0-9]+/ },
    { message: 'register_terms_5', regex: /[!@#$%^&*()_+{}\[\]:;<>,.?~\\-]+/ }
];

const passwordValidation = () => {
    let valid = false;
    let messages = [];

    for (let condition of passwordRules) {
        const isConditionValid = condition.regex.test(form.password);

        if (isConditionValid) {
            valid = true;
        }

        messages.push({
            message: condition.message,
            valid: isConditionValid,
        });
    }

    // Check if the new password matches the confirm password
    const isMatch = form.password === form.password_confirmation;

    // Set valid to false if there's any condition that failed
    valid = valid && isMatch;

    return { valid, messages };
};
</script>

<template>
    <GuestLayout :title="$t('public.sign_up')">
        <form class = "w-full">
            <div class="grid gap-6">
                <!-- Progress Bar -->
                <div class="w-full py-6">
                    <div class="flex gap-3 items-center">
                        <div class="flex gap-2 items-center justify-center w-1/3">
                            <div class="relative">
                                <div class="w-10 h-10 mx-auto rounded-full text-lg text-white flex items-center" :class="{'bg-primary-400': formStep === 1, 'bg-gray-400 dark:bg-dark-eval-2': formStep !== 1}">
                                    <span class="text-center text-white w-full font-montserrat font-semibold">1</span>
                                </div>
                            </div>
                            <span class="text-center">{{$t('public.basic_details')}}</span>
                        </div>
                        <div class="flex-grow border-black border-solid border-t mx-0 my-5">

                        </div>
                        <div class="flex gap-2 items-center justify-center w-1/3">
                            <div class="relative">
                                <div class="w-10 h-10 mx-auto rounded-full text-lg text-white flex items-center" :class="{'bg-primary-400': formStep === 2, 'bg-gray-400 dark:bg-dark-eval-2': formStep !== 2}">
                                    <span class="text-center text-white w-full font-montserrat font-semibold">2</span>
                                </div>
                            </div>
                            <span>{{$t('public.set_password')}}</span>
                        </div>
                    </div>
                </div>

                <!-- Page 1 -->
                <div v-show="formStep === 1" class="space-y-4">
                    <div class="space-y-1.5">
                        <Label
                            for="username"
                            :value="$t('public.full_name')"
                        />
                        <Input
                            id="username"
                            type="text"
                            class="block w-full"
                            :placeholder="$t('public.full_name')"
                            v-model="form.username"
                            :invalid="form.errors.username"
                        />
                        <InputError :message="form.errors.username" />
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
                            :placeholder="$t('public.email')"
                            v-model="form.email"
                            autocomplete="email"
                            autofocus
                            :invalid="form.errors.email"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="phone"
                            :value="$t('public.phone_number')"
                        />
                        <div class="flex gap-3">
                            <Select v-model="selectedCountry" :options="countryList" optionLabel="name" :placeholder="$t('public.select_country')" class="w-full md:w-56">
                                <template #value="{ value }">
                                    <div v-if="value" class="flex items-center">
                                        <!-- <img :alt="value.label" :src="value.imgUrl"  class="mr-2 h-4 w-auto"/> -->
                                        <div class="text-black">{{ value.value }}</div>
                                    </div>
                                    <span v-else>{{ $t('public.select_country') }}</span>
                                </template>
                                <template #option="{ option }">
                                    <div class="flex items-center">
                                        <!-- <img :alt="option.label" :src="option.imgUrl"   class="mr-2 h-4 w-auto"/> -->
                                        <div class="text-black">{{ option.label }} ({{ option.value }})</div>
                                    </div>
                                </template>
                            </Select>

                            <Input
                                id="phone"
                                type="text"
                                class="block w-full"
                                :placeholder="$t('public.phone_number')"
                                v-model="form.phone"
                                :invalid="form.errors.phone"
                            />
                        </div>
                        <InputError :message="form.errors.phone"/>
                    </div>
                </div>

                <!-- Page 2 -->
                <div v-show="formStep === 2" class="space-y-4">
                    <div class="space-y-1.5">
                        <Label for="password" :value="$t('public.password')" />
                        <div class="relative">
                            <Input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="block w-full"
                                :placeholder="$t('public.new_password')"
                                :invalid="form.errors.password"
                                v-model="form.password"
                            />
                            <div
                                class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                                @click="togglePasswordVisibility"
                            >
                                <template v-if="showPassword">
                                    <EyeIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                </template>
                                <template v-else>
                                    <EyeOffIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                </template>
                            </div>
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
                                :placeholder="$t('public.confirm_password')"
                                :invalid="form.errors.password"
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
                    <!-- <div class="flex flex-col items-start gap-3 self-stretch">
                        <div v-for="message in passwordValidation().messages" :key="message.key" class="flex items-center gap-2 self-stretch">
                            <div
                                :class="{
                                        'bg-success-500': message.valid,
                                        'bg-gray-400 dark:bg-dark-eval-3': !message.valid
                                    }"
                                class="flex justify-center items-center w-5 h-5 rounded-full grow-0 shrink-0"
                            >
                                <CheckIcon aria-hidden="true" class="text-white" />
                            </div>
                            <div
                                class="text-sm"
                                :class="{
                                        'text-gray-600 dark:text-gray-300': message.valid,
                                        'text-gray-400 dark:text-gray-500': !message.valid
                                    }"
                            >
                                {{ $t('public.' + message.message) }}
                            </div>
                        </div>
                    </div> -->
                </div>

                <!-- Page 3 -->
                <div v-show="formStep === 3" class="flex flex-col">
                    <!-- <div class="flex justify-center">
                        <ReferralPic class="w-full" />
                    </div> -->

                    <div class="space-y-1.5 w-full">
                        <Label
                            for="referral_code"
                            :value="$t('public.referral_code')"
                        />
                        <Input
                            id="referral_code"
                            type="text"
                            class="block w-full"
                            :placeholder="$t('public.referral_code')"
                            v-model="form.referral_code"
                            :invalid="form.errors.referral_code"
                        />
                        <InputError :message="form.errors.referral_code" />
                    </div>
                </div>

                <div v-if="formStep === 3" class="flex flex-col gap-1">
                    <label class="flex items-start gap-2">
                        <div class="flex">
                            <Checkbox name="remember" v-model:checked="form.terms" />
                            <span class="ml-2 text-xs text-gray-600 dark:text-white">
                                {{ $t('public.terms_acknowledgment') }}
                                <!-- <Terms :type=signUpTerm /> -->
                            </span>
                        </div>
                    </label>
                    <label class="flex items-start gap-2">
                        <div class="flex">
                            <Checkbox name="remember" v-model:checked="form.market" />
                            <span class="ml-2 text-xs text-gray-600 dark:text-white">
                                {{ $t('public.market_information_disclosure') }}
                            </span>
                        </div>
                    </label>
                    <label class="flex items-start gap-2">
                        <div class="flex">
                            <Checkbox name="remember" v-model:checked="form.responsible" />
                            <span class="ml-2 text-xs text-gray-600 dark:text-white">
                                {{ $t('public.trade_assessment_acknowledgment') }}
                            </span>
                        </div>
                    </label>
                    <label class="flex items-start gap-2">
                        <div class="flex">
                            <Checkbox name="remember" v-model:checked="form.compensate" />
                            <span class="ml-2 text-xs text-gray-600 dark:text-white">
                                {{ $t('public.compensation_disclosure') }}
                            </span>
                        </div>
                    </label>
                    <label class="flex items-start gap-2">
                        <div class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.all" />
                            <span class="ml-2 font-semibold text-sm text-gray-600 dark:text-white">
                                {{ $t('public.accept_all') }}
                            </span>
                        </div>
                    </label>

                    <InputError :message="form.errors.all" />
                </div>

                <div class="flex items-center justify-center gap-8 mt-4">
                    <Button v-if="formStep !== 3" variant="primary-flat" size="base" class="w-full" @click="nextStep" >
                        <span>{{ $t('public.continue') }}</span>
                    </Button>

                    <Button v-else @click="submit" :disabled="form.processing" class="w-full">
                        <span>{{ $t('public.register') }}</span>
                    </Button>
                </div>

                <p class="text-sm text-gray-700 font-medium text-center">
                    {{ $t('public.have_account') }}
                    <Link :href="route('login')" class="font-semibold text-primary-500 hover:text-primary-600 focus:underline focus:text-primary-600">
                        {{ $t('public.log_in') }}
                    </Link>
                </p>

            </div>
        </form>
    </GuestLayout>
</template>
