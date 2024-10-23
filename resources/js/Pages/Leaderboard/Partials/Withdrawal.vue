<script setup>
import Dialog from "primevue/dialog";
import Button from "@/Components/Button.vue";
import { ref } from "vue";

const visible = ref(false);

const closeDialog = () => {
    visible.value = false;
}

</script>

<template>
    <Button
        type="button"
        variant="primary-flat"
        class="flex-1"
        @click="visible = true"
    >
        {{ $t('public.withdrawal') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.incentive_withdrawal')"
        class="dialog-xs md:dialog-md"
    >
        <form>
           <div>
            
           </div>
        </form>
    </Dialog>
</template>