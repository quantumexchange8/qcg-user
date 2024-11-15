<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { EyeIcon, EyeOffIcon, CheckIcon } from '@heroicons/vue/outline'
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

const props = defineProps({
    countries: Array,
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
    email: '',
    country: '',
    phone_code: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('sign_up'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}

const validate = (activateCallback) => {
    // console.log(selectedCountry.value);
    if(selectedCountry.value){
        form.country = selectedCountry.value.name_en;
        form.phone_code = selectedCountry.value.phone_code;
    }

    form.post(route('sign_up.first.step'), {
        onSuccess: () => {
            activateCallback(2);
        },
    });
}


</script>

<template>
    <GuestLayout :title="$t('public.sign_up')">
        <form class="w-full">
            <Stepper v-model:value="activeStep" linear>
                <StepList>
                    <Step :value="1">{{$t('public.basic_details')}}</Step>
                    <Step :value="2">{{$t('public.set_password')}}</Step>
                </StepList>
                <StepPanels>
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
                                        :invalid="form.errors.first_name"
                                    />
                                    <InputError :message="form.errors.first_name" />
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
                                        :invalid="form.errors.email"
                                    />
                                    <InputError :message="form.errors.email" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label
                                        for="phone"
                                        :value="$t('public.phone_number')"
                                    />
                                    <div class="flex gap-2 items-center self-stretch">
                                        <Select filter :filterFields="['name_en', 'phone_code']" v-model="selectedCountry" :options="countryList" optionLabel="name_en" :placeholder="$t('public.phone_code')" class="w-[100px]"
                                        :invalid="form.errors.phone_code">
                                            <template #value="slotProps" >
                                                <div v-if="slotProps.value" class="flex items-center">
                                                    <div class="text-black">{{ slotProps.value.phone_code }}
                                                    </div>
                                                </div>
                                                <span v-else>{{ $t('public.phone_code') }}</span>
                                            </template>
                                            <template #option="slotProps">
                                                <div class="flex items-center">
                                                    <div class="text-black">{{ slotProps.option.name_en }} ({{ slotProps.option.phone_code }})</div>
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
                                            :invalid="form.errors.password"
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
                            </div>

                            <div class="grid items-center gap-4">
                                <Button variant="primary-flat" size="base" class="w-full px-4 py-3" @click="submit" :disabled="form.processing">
                                    <span>{{ $t('public.sign_up') }}</span>
                                </Button>

                                <Button
                                type="button"
                                variant="primary-text"
                                @click="activateCallback(1)"
                                class="px-4 py-3"
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
