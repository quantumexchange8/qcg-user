<script setup>
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
                        class="text-md text-gray-500"
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
</template>
