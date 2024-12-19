<script setup>
import {ref, onMounted, watch, nextTick, watchEffect} from "vue";
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
import dayjs from "dayjs";
import Image from 'primevue/image';
import {wTrans} from "laravel-vue-i18n";
import Button from "@/Components/Button.vue";
import Empty from "@/Components/Empty.vue";
import Skeleton from 'primevue/skeleton';
import {usePage} from "@inertiajs/vue3";
import CreatePost from "@/Pages/Dashboard/Partials/CreatePost.vue";
import {usePermission} from "@/Composables/permissions.js";
import { IconThumbUpFilled, IconThumbDownFilled } from "@tabler/icons-vue";

const props = defineProps({
    postCounts: Number,
    authorName: String,
})

const posts = ref([]);
const loading = ref(false);
const { hasPermission } = usePermission();

const getResults = async () => {
    loading.value = true;

    try {
        let url = '/dashboard/getPosts';

        const response = await axios.get(url);
        posts.value = response.data;
    } catch (error) {
        console.error('Error changing locale:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

const expandedPosts = ref([]);
const isTruncated = ref([]);

const toggleExpand = (index) => {
    expandedPosts.value[index] = !expandedPosts.value[index];
};

const checkContentHeight = async () => {
    // Ensure DOM updates are applied before measuring
    await nextTick();

    posts.value.forEach((post) => {
        const contentElement = document.getElementById(`content-${post.id}`);
        if (contentElement) {
            isTruncated.value[post.id] = contentElement.scrollHeight > 82;
        }
    });
};

onMounted(() => {
    checkContentHeight();
});

watch(posts, () => {
    checkContentHeight();
});

const formatPostDate = (date) => {
    const now = dayjs();
    const postDate = dayjs(date);

    if (postDate.isSame(now, 'day')) {
        return postDate.format('HH:mm');
    } else if (postDate.isSame(now.subtract(1, 'day'), 'day')) {
        return wTrans('public.yesterday');
    } else {
        return postDate.format('ddd, DD MMM');
    }
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

const handleUserInteraction = async (postId, type) => {
    try {
        const response = await axios.post(route('dashboard.postInteraction'), {
            postId: postId,
            type: type,
        });

        if (response.data.success) {
            const updatedPost = response.data.post; 
            const index = posts.value.findIndex((post) => post.id === postId);
            if (index !== -1) {
                posts.value[index] = updatedPost; 
            }
        } else {
            console.error('Failed to update user interaction:', response.data.message);
        }
    } catch (error) {
        console.error('Error interacting with the server:', error);
    }
};

const isEnlarged = ref(false); // Track if image is enlarged
const imageStyle = ref({
  transform: 'scale(1) translate3d(0, 0, 0)', // Initial transform
  transition: 'transform 0.3s ease', // Transition applied directly
  transformOrigin: 'center center', // Initial transform origin
});

const scaleFactor = 3; // Scale factor for the image

// Toggle enlarged state of the image
const toggleEnlarged = (event) => {
  isEnlarged.value = !isEnlarged.value;

  // Prevent triggering the preview callback by stopping the event
  event.stopImmediatePropagation(); // Prevent the image preview action

  const rect = event.target.getBoundingClientRect();
  const mouseX = event.clientX - rect.left;
  const mouseY = event.clientY - rect.top;

  if (isEnlarged.value) {
    // Adjust transform-origin to focus on the clicked position
    imageStyle.value.transformOrigin = `${(mouseX / rect.width) * 100}% ${(mouseY / rect.height) * 100}%`;

    // Apply the scale transform
    imageStyle.value.transform = `scale(${scaleFactor}) translate3d(0, 0, 0)`;
  } else {
    // Reset transform when not enlarged
    imageStyle.value.transform = 'scale(1) translate3d(0, 0, 0)';
    imageStyle.value.transformOrigin = 'center center'; // Reset transform origin to center
  }
};

// Handle the mouse-follow behavior when enlarged
const followMouse = (event) => {
  if (isEnlarged.value) {
    const rect = event.currentTarget.getBoundingClientRect();
    const mouseX = event.clientX - rect.left;
    const mouseY = event.clientY - rect.top;

    // Amplify the translation values to make the movement more noticeable
    const translateX = ((mouseX / rect.width) - 0.5) * -100 / scaleFactor;
    const translateY = ((mouseY / rect.height) - 0.5) * -100 / scaleFactor;

    imageStyle.value.transform = `scale(${scaleFactor}) translate3d(${translateX}%, ${translateY}%, 0)`;
  }
};

// Reset the image transform to its original state
const resetImageTransform = () => {
  isEnlarged.value = false;
  imageStyle.value.transform = 'scale(1) translate3d(0, 0, 0)';
  imageStyle.value.transformOrigin = 'center center';
};

</script>

<template>
    <div
        class="flex flex-col gap-5 self-stretch p-4 md:py-6 md:px-8 bg-white rounded-lg shadow-card w-full h-[680px]"
    >
        <div class="flex justify-between items-center w-full">
            <span class="text-gray-950 font-bold">{{ $t('public.welcome_to_forum') }}</span>
            <CreatePost
                v-if="hasPermission('post_forum')"
                :authorName="authorName"
            />
        </div>
        <div
            v-if="postCounts === 0 && !posts.length"
            class="flex flex-col items-center justify-center self-stretch h-full"
        >
            <Empty
                :title="$t('public.no_posts_yet')"
                :message="$t('public.no_posts_yet_caption')"
            >
                <template #image>
                    <img src="/img/no_data/illustration-forum.svg" alt="no data" />
                </template>
            </Empty>
        </div>

        <div
            v-else-if="loading"
            class="py-6 flex flex-col gap-5 items-center self-stretch"
        >
            <div class="flex justify-between items-start self-stretch">
                <div class="flex flex-col items-start text-sm">
                    <Skeleton width="9rem" height="0.6rem" borderRadius="2rem"></Skeleton>
                </div>
                <Skeleton width="2rem" height="0.6rem" class="my-1" borderRadius="2rem"></Skeleton>
            </div>

            <!-- content -->
            <div class="flex flex-col gap-5 items-start self-stretch">
                <Skeleton width="10rem" height="4rem"></Skeleton>
                <div class="flex flex-col gap-3 items-start self-stretch text-sm text-gray-950">
                    <Skeleton width="9rem" height="0.6rem" borderRadius="2rem"></Skeleton>
                    <Skeleton width="9rem" height="0.6rem" borderRadius="2rem"></Skeleton>
                </div>
            </div>
        </div>

        <div v-else class="overflow-y-auto">
            <div
                v-for="post in posts"
                :key="post.id"
                class="border-b border-gray-200 last:border-transparent py-6 flex flex-col gap-5 items-center self-stretch"
            >
                <div class="flex justify-between items-start self-stretch">
                    <span class="text-sm text-gray-950 font-bold">{{ post.display_name }}</span>
                    <span class="text-gray-700 text-xs text-right min-w-28">{{ formatPostDate(post.created_at) }}</span>
                </div>

                <!-- content -->
                <div class="flex flex-col gap-5 items-start self-stretch">
                    <Image
                        v-if="post.post_attachment"
                        :src="post.post_attachment"
                        alt="Image"
                        image-class="w-[250px] h-[160px] object-contain"
                        preview
                        :pt="{
                            toolbar: 'hidden',
                        }"
                        @click="resetImageTransform()"
                    >
                        <!-- Original image template with click event -->
                        <template #original>
                            <img
                                :src="post.post_attachment"
                                alt="Image"
                                :class="[isEnlarged ? 'cursor-zoom-out' : 'cursor-zoom-in']"
                                @click="toggleEnlarged($event)"
                                @mousemove="followMouse"
                                :style="imageStyle"
                                data-pc-section="original"
                            />
                        </template>
                    </Image>
                    <div class="grid grid-cols-1 gap-3 items-start self-stretch text-sm text-gray-950">
                        <span class="font-semibold">{{ post.subject }}</span>
                        <div
                            :id="`content-${post.id}`"
                            v-html="post.message"
                            :class="[
                            'prose prose-p:my-0 prose-ul:my-0 w-full',
                            {
                                 'max-h-[82px] truncate': !expandedPosts[post.id],
                                 'max-h-auto': expandedPosts[post.id],
                            }
                        ]"
                        />
                    </div>  
                    <div
                        v-if="isTruncated[post.id]"
                        class="text-primary font-medium text-xs hover:text-primary-700 select-none cursor-pointer"
                        @click="toggleExpand(post.id)"
                    >
                        {{ expandedPosts[post.id] ? $t('public.see_less') : $t('public.see_more') }}
                    </div>
                </div>

                <div class="flex justify-between items-center self-stretch">
                    <div class="flex items-center">
                        <div class="flex justify-center items-center gap-1">
                            <Button
                                type="button"
                                variant="success-text"
                                size="sm"
                                iconOnly
                                pill
                                class="hover:rotate-[-15deg]"
                                @click="handleUserInteraction(post.id, 'like')"
                            >
                                <IconThumbUpFilled size="16" stroke-width="1.25" />
                            </Button>
                            <span class="min-w-10 text-gray-700 text-sm">{{ post.total_likes_count }}</span> 
                        </div>
                        <div class="flex justify-center items-center gap-1">
                            <Button
                                type="button"
                                variant="error-text"
                                size="sm"
                                iconOnly
                                pill
                                class="hover:rotate-[-15deg]"
                                @click="handleUserInteraction(post.id, 'dislike')"
                            >
                                <IconThumbDownFilled size="16" stroke-width="1.25" />
                            </Button>
                            <span class="min-w-10 text-gray-700 text-sm">{{ post.total_dislikes_count }}</span> 
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
