<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PostCard, { type PostData } from '@/components/PostCard.vue';

defineProps<{
    posts: {
        data: PostData[];
        next_page_url: string | null;
    };
}>();
</script>

<template>
    <AppLayout title="Instagram">
        <template #header-left>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
            </svg>
        </template>
        <template #header-right>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 6 21c-1.282 0-2.47-.402-3.445-1.087-.305-.21-.477-.59-.373-.953a4.483 4.483 0 0 0 .15-1.09c0-.602-.189-1.16-.533-1.59A7.986 7.986 0 0 1 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
            </svg>
        </template>

        <!-- Stories bar -->
        <div class="flex gap-4 overflow-x-auto border-b border-neutral-800 px-4 py-3">
            <div class="flex flex-col items-center gap-1">
                <div class="flex size-16 items-center justify-center rounded-full border-2 border-dashed border-neutral-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-neutral-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </div>
                <span class="text-[10px] text-neutral-400">Jouw verhaal</span>
            </div>
            <div v-for="n in 6" :key="n" class="flex flex-shrink-0 flex-col items-center gap-1">
                <div class="rounded-full bg-gradient-to-tr from-yellow-400 via-pink-500 to-purple-600 p-0.5">
                    <img
                        :src="`https://i.pravatar.cc/64?u=story${n}`"
                        class="size-14 rounded-full border-2 border-black object-cover"
                    />
                </div>
                <span class="max-w-16 truncate text-[10px]">gebruiker{{ n }}</span>
            </div>
        </div>

        <!-- Posts -->
        <div>
            <PostCard v-for="post in posts.data" :key="post.id" :post="post" />
        </div>

        <!-- Empty state -->
        <div v-if="posts.data.length === 0" class="flex flex-col items-center justify-center py-20">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="mb-4 size-16 text-neutral-600">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
            </svg>
            <p class="text-neutral-500">Nog geen berichten</p>
        </div>
    </AppLayout>
</template>
