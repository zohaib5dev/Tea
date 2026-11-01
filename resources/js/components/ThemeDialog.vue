<script setup>
import { reactive, ref } from 'vue';
import http, { errorText } from '@/lib/http';
import { useAuth } from '@/stores/auth';
import { useUi } from '@/stores/ui';
import { DEFAULT_ACCENT, DEFAULT_BACKGROUND, applyTheme, normalizeAccent, normalizeBackground } from '@/lib/theme';
import AppIcon from '@/components/AppIcon.vue';

const auth = useAuth();
const ui = useUi();
const dialog = ref(null);
const busy = ref(false);
const draft = reactive({ accent: DEFAULT_ACCENT, background: DEFAULT_BACKGROUND });

const accents = [
    ['Gold', '#d4a63a'], ['Rose', '#e0577a'], ['Violet', '#8b5cf6'], ['Sky', '#38bdf8'],
    ['Emerald', '#34d399'], ['Orange', '#fb923c'], ['Lime', '#a3e635'], ['Silver', '#b8c0cc'],
];
const backgrounds = [
    ['Espresso', '#0f0c07'], ['Midnight', '#0b1020'], ['Forest', '#0a1410'], ['Plum', '#130a17'],
    ['Graphite', '#101114'], ['Wine', '#170a0f'], ['Ocean', '#07131a'], ['Black', '#050505'],
];

function open() {
    draft.accent = auth.user.theme?.accent || DEFAULT_ACCENT;
    draft.background = auth.user.theme?.background || DEFAULT_BACKGROUND;
    dialog.value.showModal();
}
defineExpose({ open });

// Every change is shown live in the whole app
function preview() { applyTheme({ accent: draft.accent, background: draft.background }); }

function pick(key, value) { draft[key] = value; preview(); }

function reset() {
    draft.accent = DEFAULT_ACCENT;
    draft.background = DEFAULT_BACKGROUND;
    preview();
}

// Closing without saving puts the saved theme back
function onClose() { applyTheme(auth.user.theme); }

async function save() {
    busy.value = true;
    const isDefault = draft.accent === DEFAULT_ACCENT && draft.background === DEFAULT_BACKGROUND;
    try {
        const { data } = await http.put('/profile/theme', isDefault ? {} : { accent: draft.accent, background: draft.background });
        auth.user.theme = data;
        applyTheme(data);
        dialog.value.close();
        ui.toast('Your theme is saved.');
    } catch (e) { ui.toast(errorText(e)); }
    busy.value = false;
}
</script>

<template>
    <dialog ref="dialog" class="scrollbar-gold  w-[calc(100%-2rem)] max-w-lg rounded-2xl border border-ink-700 bg-ink-900 p-0 backdrop:bg-black/60" @close="onClose" @click="(e) => e.target === dialog && dialog.close()">
        <form @submit.prevent="save">
            <div class="flex items-center justify-between border-b border-ink-700 px-5 py-4">
                <h2 class="flex items-center gap-2 font-display text-2xl font-bold text-gold-300"><AppIcon name="palette" class="h-5 w-5" /> Your theme</h2>
                <button type="button" class="rounded-full p-2 text-ink-300 hover:bg-ink-800 hover:text-gold-300" aria-label="Close" @click="dialog.close()"><AppIcon name="x" class="h-5 w-5" /></button>
            </div>

            <div class="space-y-6 p-5">
                <!-- accent -->
                <fieldset>
                    <legend class="mb-2 text-sm font-semibold text-ink-200">Accent color</legend>
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="relative h-10 w-10 cursor-pointer overflow-hidden rounded-full border-2 border-ink-700 transition hover:border-gold-500" title="Pick any color">
                            <input type="color" :value="draft.accent" class="absolute -inset-2 h-16 w-16 cursor-pointer border-0 bg-transparent p-0" aria-label="Accent color" @input="pick('accent', $event.target.value)">
                        </label>
                        <button v-for="[name, hex] in accents" :key="hex" type="button" :title="name" :aria-label="name"
                                class="h-8 w-8 rounded-full border-2 transition hover:scale-110"
                                :class="draft.accent === hex ? 'border-ink-50' : 'border-transparent'" :style="{ backgroundColor: hex }" @click="pick('accent', hex)"></button>
                    </div>
                </fieldset>

                <!-- background -->
                <fieldset>
                    <legend class="mb-2 text-sm font-semibold text-ink-200">Background</legend>
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="relative h-10 w-10 cursor-pointer overflow-hidden rounded-full border-2 border-ink-700 transition hover:border-gold-500" title="Pick any color">
                            <input type="color" :value="draft.background" class="absolute -inset-2 h-16 w-16 cursor-pointer border-0 bg-transparent p-0" aria-label="Background color" @input="pick('background', $event.target.value)">
                        </label>
                        <button v-for="[name, hex] in backgrounds" :key="hex" type="button" :title="name" :aria-label="name"
                                class="h-8 w-8 rounded-full border-2 transition hover:scale-110"
                                :class="draft.background === hex ? 'border-gold-400' : 'border-ink-700'" :style="{ backgroundColor: hex }" @click="pick('background', hex)"></button>
                    </div>
                    <p class="mt-2 text-xs text-ink-300">Backgrounds always stay dark and accents stay bright, so text is easy to read with any color you pick.</p>
                </fieldset>

                <!-- live sample -->
                <div class="rounded-2xl border border-ink-700 bg-ink-950 p-4">
                    <p class="font-display text-2xl font-bold text-gold-300">Sample</p>
                    <p class="mt-1 text-sm text-ink-200">Accent <span class="font-semibold">{{ normalizeAccent(draft.accent) }}</span>, background <span class="font-semibold">{{ normalizeBackground(draft.background) }}</span></p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="btn-gold">Primary</span>
                        <span class="btn-ghost">Secondary</span>
                        <span class="rounded-full bg-gold-grad px-2 py-0.5 text-xs font-bold text-ink-950">3</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-2 border-t border-ink-700 px-5 py-4">
                <button type="button" class="text-sm font-semibold text-ink-300 transition hover:text-gold-300" @click="reset">Reset to default</button>
                <div class="flex gap-2">
                    <button type="button" class="btn-ghost" @click="dialog.close()">Cancel</button>
                    <button class="btn-gold" :disabled="busy">{{ busy ? 'Saving…' : 'Save theme' }}</button>
                </div>
            </div>
        </form>
    </dialog>
</template>
