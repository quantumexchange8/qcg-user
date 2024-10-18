<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Button from "@/Components/Button.vue"
import { useForm, usePage } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import {ref, watch} from "vue";

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});

const resetForm = () => {
    form.reset();
}

const submitForm = () => {
    form.post(route('profile.update'), {
        onSuccess: () => {
            visible.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <form class="w-full h-full flex flex-col items-end p-3 gap-8 rounded-lg bg-white shadow-card md:p-6">
        <div class="w-full flex flex-col justify-center items-start gap-8">
            <div class="flex flex-col gap-1 items-start justify-center w-full">
                <span class="text-gray-950 font-bold">{{ $t('public.cryptocurrency_wallet') }}</span>
                <span class="text-gray-500 text-xs">{{ $t('public.add_cryptocurrency_caption') }}</span>
            </div>

            <div class="flex flex-col gap-5 items-center self-stretch w-full">
                <div class="flex flex-col gap-1 w-full">
                    <InputLabel for="wallet1">
                        {{ $t('public.wallet') }} #1
                    </InputLabel>
                    <InputText
                        id="name"
                        type="text"
                        class="block w-full"
                        v-model="form.name"
                        :placeholder="$t('public.wallet_name')"
                        :invalid="!!form.errors.name"
                        autocomplete="name"
                        disabled
                    />
                    <InputText
                        id="name"
                        type="text"
                        class="block w-full"
                        v-model="form.name"
                        :placeholder="$t('public.token_address')"
                        :invalid="!!form.errors.name"
                        autocomplete="name"
                        disabled
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="flex flex-col gap-1 w-full">
                    <InputLabel for="wallet2">
                        {{ $t('public.wallet') }} #2
                    </InputLabel>
                    <InputText
                        id="name"
                        type="text"
                        class="block w-full"
                        v-model="form.name"
                        :placeholder="$t('public.wallet_name')"
                        :invalid="!!form.errors.name"
                        autocomplete="name"
                        disabled
                    />
                    <InputText
                        id="name"
                        type="text"
                        class="block w-full"
                        v-model="form.name"
                        :placeholder="$t('public.token_address')"
                        :invalid="!!form.errors.name"
                        autocomplete="name"
                        disabled
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="flex flex-col gap-1 w-full">
                    <InputLabel for="wallet3">
                        {{ $t('public.wallet') }} #3
                    </InputLabel>
                    <InputText
                        id="name"
                        type="text"
                        class="block w-full"
                        v-model="form.name"
                        :placeholder="$t('public.wallet_name')"
                        :invalid="!!form.errors.name"
                        autocomplete="name"
                        disabled
                    />
                    <InputText
                        id="name"
                        type="text"
                        class="block w-full"
                        v-model="form.name"
                        :placeholder="$t('public.token_address')"
                        :invalid="!!form.errors.name"
                        autocomplete="name"
                        disabled
                    />
                    <InputError :message="form.errors.name" />
                </div>
            </div>
        </div>


        <div class="flex justify-end items-center pt-10 md:pt-7 gap-4 self-stretch">
            <Button
                type="button"
                variant="gray-tonal"
                
                @click="resetForm"
                disabled
            >
                {{ $t('public.cancel') }}
            </Button>
            <Button
                variant="primary-flat"
                
                @click="submitForm"
                disabled
            >
                {{ $t('public.save_changes') }}
            </Button>
        </div>
    </form>
</template>
