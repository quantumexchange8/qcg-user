<!-- <script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/Guest.vue'
import Button from '@/Components/Button.vue'

const props = defineProps({
    status: String
})

const form = useForm()

const submit = () => {
    form.post(route('verification.send'))
}

const verificationLinkSent = computed(() => props.status === 'verification-link-sent')
</script>

<template>
    <GuestLayout :title="$t('public.email_verification')">
        <div class="w-full flex flex-col justify-center items-center self-stretch">
            <img src="/assets/verify-email.svg" alt="verify-email">
            <div class="grid gap-8 ">
                <div class="grid gap-3">
                    <div class="text-xl text-gray-950 font-bold">
                        {{ $t('public.verify_email') }}
                    </div>

                    <div
                        class=" text-gray-500"
                        v-if="verificationLinkSent"
                    >
                        {{ $t('public.verify_email_caption') }}
                    </div>
                </div>

                <form @submit.prevent="submit" class="grid gap-6">
                    <div class="flex items-center justify-between mt-4">
                        <Button variant="primary-flat" size="base" class="w-full px-4 py-3" :href="route('logout')" method="post" >
                            <span>{{ $t('public.logout') }}</span>
                        </Button>

                        <div class="flex justify-between text-sm text-gray-700">
                            {{ $t('public.not_received_email') }}

                            <Link
                                class="text-gray-300 font-semibold"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                {{ $t('public.resend') }}
                            </Link>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template> -->

<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/Guest.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { usePage } from "@inertiajs/vue3";
import Button from '@/Components/Button.vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
const form = useForm({});

const submitted = ref(false);
const countdown = ref(60);
let interval;
const startCountdown = () => {
    countdown.value = 60;
    interval = setInterval(() => {
        if (countdown.value > 0) {
            countdown.value -= 1;
        } else {
            clearInterval(interval);
            submitted.value = false;
        }
    }, 1000);
};

const submit = () => {
    form.post(route('verification.send'), {
        onSuccess: () => {
            submitted.value = true;
            startCountdown();
        },
    });
};

const logout = () => {
    form.post(route('logout'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout :title="$t('public.email_verification')">
        <img src="/assets/verify-email.svg" alt="verify-email" class="max-w-md mx-auto">
        <div class="w-full flex flex-col items-center justify-center gap-8 pt-8 md:pt-0 max-w-md mx-auto">
            <div class="flex flex-col items-start gap-3 self-stretch">
                <div class="self-stretch text-center text-gray-950 text-xl font-semibold">{{ $t('public.verify_email') }}</div>
                <div class="flex flex-col self-stretch text-center text-gray-500 gap-1">{{ $t('public.verify_email_caption') }}
                </div>
            </div>
            <div class="flex flex-col items-center justify-center gap-6 self-stretch">
                <Button size="base" variant="primary-flat" class="w-full" :disabled="form.processing" @click.prevent="logout">{{ $t('public.log_out') }}</Button>
                <div class="flex justify-between items-center self-stretch">
                    <div class="text-gray-700 text-sm font-medium">{{ $t('public.not_receive_email') }}</div>
                    <div
                        v-if="!submitted"
                        class="text-right text-sm text-primary-500 font-semibold"
                        :class="{
                            'opacity-25 pointer-events-none cursor-not-allowed': form.processing,
                            'cursor-pointer': !form.processing
                        }"
                        @click.prevent="submit"
                    >
                        {{ $t('public.resend') }}
                    </div>
                    <div v-else class="text-gray-300 text-right text-sm font-semibold">{{ $t('public.resend_in') }} {{ countdown }}s</div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

