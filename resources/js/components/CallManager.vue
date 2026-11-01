<script setup>
import { ref, watch, nextTick } from 'vue';
import { callState as s, acceptCall, declineCall, hangUp, toggleMic, toggleCam } from '@/composables/useCalls';
import AppIcon from '@/components/AppIcon.vue';
import UserAvatar from '@/components/UserAvatar.vue';

const dialog = ref(null);
const localEl = ref(null);
const remoteEl = ref(null);

// The ringing dialog follows the call state
watch(() => s.incoming, (value) => {
    if (value && !dialog.value.open) dialog.value.showModal();
    if (!value && dialog.value.open) dialog.value.close();
});

// Attach the media streams once the call screen exists
watch(() => [s.active, s.local, s.remote], async () => {
    await nextTick();
    if (localEl.value) localEl.value.srcObject = s.local;
    if (remoteEl.value) remoteEl.value.srcObject = s.remote;
}, { flush: 'post' });
</script>

<template>
    <!-- Incoming call -->
    <dialog ref="dialog" class="w-[calc(100%-2rem)] max-w-sm rounded-3xl border border-gold-500/40 bg-ink-900 p-0 backdrop:bg-black/80 backdrop:backdrop-blur-sm"
            @cancel.prevent="declineCall">
        <div v-if="s.incoming" class="flex flex-col items-center px-6 py-8 text-center">
            <UserAvatar :user="s.incoming.peer" size="xl" class="mb-4" />
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-gold-400">{{ s.incoming.video ? 'Video call' : 'Voice call' }}</p>
            <h2 class="mt-1 font-display text-3xl font-bold">{{ s.incoming.peer.name }}</h2>
            <p class="mt-1 animate-pulse text-sm text-ink-300">is calling you…</p>

            <div class="mt-8 flex items-center gap-8">
                <div class="text-center">
                    <button type="button" class="grid h-16 w-16 place-items-center rounded-full bg-red-500 text-white shadow-lg shadow-red-500/30 transition hover:scale-105" aria-label="Decline call" @click="declineCall">
                        <AppIcon name="phone-off" class="h-7 w-7" />
                    </button>
                    <p class="mt-2 text-xs text-ink-300">Decline</p>
                </div>
                <div class="text-center">
                    <button type="button" class="grid h-16 w-16 animate-bounce place-items-center rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 transition hover:scale-105" aria-label="Accept call" @click="acceptCall">
                        <AppIcon name="phone" class="h-7 w-7" />
                    </button>
                    <p class="mt-2 text-xs text-ink-300">Accept</p>
                </div>
            </div>
        </div>
    </dialog>

    <!-- Call screen -->
    <div v-if="s.active" class="fixed inset-0 z-[70] flex flex-col bg-ink-950" role="dialog" aria-modal="true" aria-label="Call">
        <div class="relative flex-1 overflow-hidden">
            <video ref="remoteEl" autoplay playsinline class="h-full w-full object-cover" :class="s.remoteVideo ? '' : 'hidden'"></video>

            <div v-if="!s.remoteVideo" class="absolute inset-0 grid place-items-center bg-[radial-gradient(600px_300px_at_50%_30%,rgba(212,166,58,.18),transparent)]">
                <UserAvatar :user="s.peer" size="xl" class="!h-32 !w-32 !text-4xl" />
            </div>

            <div class="absolute left-4 top-4 rounded-2xl bg-ink-950/60 px-4 py-2 backdrop-blur">
                <p class="font-bold">{{ s.peer?.name }}</p>
                <p class="text-xs text-gold-300">{{ s.status }}</p>
            </div>

            <video ref="localEl" autoplay playsinline muted
                   class="absolute bottom-4 right-4 h-40 w-28 -scale-x-100 rounded-2xl border border-gold-500/50 bg-ink-800 object-cover shadow-xl sm:h-48 sm:w-36"
                   :class="s.video ? '' : 'hidden'"></video>
        </div>

        <div class="flex items-center justify-center gap-5 border-t border-ink-700 bg-ink-900 px-4 py-5">
            <button type="button" class="grid h-14 w-14 place-items-center rounded-full bg-ink-800 transition hover:bg-ink-700" aria-label="Mute microphone" @click="toggleMic">
                <AppIcon :name="s.micOn ? 'mic' : 'mic-off'" class="h-6 w-6" :class="s.micOn ? '' : 'text-red-400'" />
            </button>
            <button v-if="s.video" type="button" class="grid h-14 w-14 place-items-center rounded-full bg-ink-800 transition hover:bg-ink-700" aria-label="Turn camera off" @click="toggleCam">
                <AppIcon :name="s.camOn ? 'video' : 'video-off'" class="h-6 w-6" :class="s.camOn ? '' : 'text-red-400'" />
            </button>
            <button type="button" class="grid h-16 w-16 place-items-center rounded-full bg-red-500 text-white shadow-lg shadow-red-500/30 transition hover:scale-105" aria-label="End call" @click="hangUp">
                <AppIcon name="phone-off" class="h-7 w-7" />
            </button>
        </div>
    </div>
</template>
