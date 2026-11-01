export const firstName = (name = '') => name.split(' ')[0];

export const initials = (name = '?') => name.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0].toUpperCase()).join('');

/** "5 minutes ago" */
export function timeAgo(iso) {
    const s = Math.round((Date.now() - new Date(iso)) / 1000);
    const units = [['year', 31536000], ['month', 2592000], ['day', 86400], ['hour', 3600], ['minute', 60]];
    for (const [name, sec] of units) {
        if (s >= sec) {
            const v = Math.floor(s / sec);
            return `${v} ${name}${v > 1 ? 's' : ''} ago`;
        }
    }
    return 'just now';
}

/** "5m", "2h", "3d" for the sidebar */
export function shortAgo(iso) {
    if (!iso) return '';
    const s = Math.round((Date.now() - new Date(iso)) / 1000);
    if (s < 60) return 'now';
    if (s < 3600) return Math.floor(s / 60) + 'm';
    if (s < 86400) return Math.floor(s / 3600) + 'h';
    if (s < 604800) return Math.floor(s / 86400) + 'd';
    return Math.floor(s / 604800) + 'w';
}

export const timeOfDay = (iso) => new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

/** 75 -> "1:15" */
export function clock(s) {
    const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
    return h ? `${h}:${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}` : `${m}:${String(sec).padStart(2, '0')}`;
}

/** 151 -> "2 min 31 sec" */
export function longDuration(s) {
    s = Math.max(0, s || 0);
    if (s < 60) return `${s} sec`;
    const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
    return h ? `${h} hr ${m} min` : `${m} min${sec ? ` ${sec} sec` : ''}`;
}

/** Sidebar text for a call message */
export function callLabel(m) {
    const kind = m.call_kind === 'video' ? 'Video call' : 'Voice call';
    if (m.call_status === 'ended') return m.call_duration ? `${kind}, ${clock(m.call_duration)}` : `${kind} ended`;
    if (m.call_status === 'missed') return `Missed ${kind.toLowerCase()}`;
    if (m.call_status === 'declined') return 'Call declined';
    return kind;
}

export const messagePreview = (m) =>
    m.type === 'call' ? callLabel(m) : (m.body || (m.attachment_type === 'image' ? 'Sent a photo' : 'Sent an attachment'));
