/**
 * Per-user themes. The whole interface is painted with the CSS variables --gold-* (accent) and --ink-* (backgrounds and text).
 * From ONE accent color and ONE background color we derive every shade, so any picked color still looks designed.
 * Backgrounds are always kept dark and accents mid-bright, which keeps all text readable.
 */
export const DEFAULT_ACCENT = '#d4a63a';
export const DEFAULT_BACKGROUND = '#0f0c07';

const GOLD_KEYS = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900];
const INK_KEYS = [50, 200, 300, 700, 800, 900, 950];

const clamp = (v, lo, hi) => Math.min(hi, Math.max(lo, v));

function hexToRgb(hex) {
    const n = parseInt(hex.slice(1), 16);
    return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
}

const rgbToHex = (rgb) => '#' + rgb.map((v) => v.toString(16).padStart(2, '0')).join('');

function rgbToHsl([r, g, b]) {
    r /= 255; g /= 255; b /= 255;
    const max = Math.max(r, g, b), min = Math.min(r, g, b), d = max - min;
    const l = (max + min) / 2;
    let h = 0, s = 0;
    if (d) {
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        if (max === r) h = (g - b) / d + (g < b ? 6 : 0);
        else if (max === g) h = (b - r) / d + 2;
        else h = (r - g) / d + 4;
        h /= 6;
    }
    return [h, s, l];
}

function hslToRgb([h, s, l]) {
    if (!s) { const v = Math.round(l * 255); return [v, v, v]; }
    const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    const p = 2 * l - q;
    const channel = (t) => {
        if (t < 0) t += 1;
        if (t > 1) t -= 1;
        if (t < 1 / 6) return p + (q - p) * 6 * t;
        if (t < 1 / 2) return q;
        if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
        return p;
    };
    return [channel(h + 1 / 3), channel(h), channel(h - 1 / 3)].map((v) => Math.round(v * 255));
}

/** The accent actually used: not too dark, not too pale, so buttons stay readable. */
export function normalizeAccent(hex) {
    const [h, s, l] = rgbToHsl(hexToRgb(hex));
    return rgbToHex(hslToRgb([h, Math.max(s, 0.35), clamp(l, 0.45, 0.7)]));
}

/** The background actually used: always dark. */
export function normalizeBackground(hex) {
    const [h, s, l] = rgbToHsl(hexToRgb(hex));
    return rgbToHex(hslToRgb([h, clamp(s, 0, 0.7), clamp(l, 0.02, 0.1)]));
}

export function accentPalette(hex) {
    const [h, s, l] = rgbToHsl(hexToRgb(normalizeAccent(hex)));
    const shade = (light) => hslToRgb([h, s, clamp(light, 0.04, 0.97)]);

    return {
        50: shade(0.95), 100: shade(0.89), 200: shade(0.79),
        300: shade(Math.min(l + 0.16, 0.86)), 400: shade(l + 0.07), 500: shade(l),
        600: shade(l - 0.09), 700: shade(l - 0.18), 800: shade(l - 0.28), 900: shade(l - 0.36),
    };
}

export function inkPalette(hex) {
    const [h, s, l] = rgbToHsl(hexToRgb(hex));
    const L = clamp(l, 0.02, 0.1);
    const S = clamp(s, 0, 0.7);
    const k = clamp(S / 0.36, 0, 1.4);   // 1 = warm brown like the default, 0 = neutral grey: text takes the same tint

    return {
        950: hslToRgb([h, S, L]),
        900: hslToRgb([h, S, L + 0.027]),
        800: hslToRgb([h, S, L + 0.052]),
        700: hslToRgb([h, clamp(S, 0, 0.5), L + 0.108]),
        300: hslToRgb([h, 0.22 * k, 0.57]),
        200: hslToRgb([h, 0.28 * k, 0.73]),
        50: hslToRgb([h, 0.57 * k, 0.93]),
    };
}

/** Paint the app with a theme { accent, background }. Missing values fall back to the defaults in app.css. */
export function applyTheme(theme) {
    const style = document.documentElement.style;
    const set = (name, rgb) => (rgb ? style.setProperty(name, rgb.join(' ')) : style.removeProperty(name));

    const accent = theme?.accent ? accentPalette(theme.accent) : null;
    GOLD_KEYS.forEach((k) => set(`--gold-${k}`, accent && accent[k]));

    const ink = theme?.background ? inkPalette(theme.background) : null;
    INK_KEYS.forEach((k) => set(`--ink-${k}`, ink && ink[k]));
}
