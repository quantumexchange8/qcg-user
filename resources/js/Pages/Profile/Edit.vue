<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
// import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue';
import ChangeProfilePhoto from "@/Pages/Profile/Partials/ChangeProfilePhoto.vue";
import CryptocurrencyWalletForm from '@/Pages/Profile/Partials/CryptocurrencyWalletForm.vue';
import KycVerification from '@/Pages/Profile/Partials/KycVerification.vue';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    countries: Array,
})

</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.profile')">
        <div class="w-full grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="flex flex-col justify-start items-center gap-5 self-stretch">
                <ChangeProfilePhoto />
                <UpdateProfileInformationForm 
                    :countries="props.countries"
                />
                <KycVerification />
            </div>

            <div class="flex flex-col justify-start items-center gap-5 self-stretch">
                <CryptocurrencyWalletForm />
                <UpdatePasswordForm />
                <!-- <DeleteUserForm /> -->
            </div>
        </div>
    </AuthenticatedLayout>
</template>
