import { reactive, markRaw } from 'vue';
import http, { errorText } from '@/lib/http';
import { getEcho } from '@/lib/echo';
import { useUi } from '@/stores/ui';

/**
 * Voice and video calls (WebRTC). Signaling goes through the API + Reverb,
 * the audio and video travel directly between the two browsers.
 */
export const callState = reactive({
    incoming: null,   // { id, conversationId, peer, video } while the phone is ringing
    active: false,    // the call screen is open
    status: '',       // "Calling…", "Connecting…", "01:23"
    peer: null,
    video: false,
    micOn: true,
    camOn: true,
    local: null,      // MediaStream (kept raw so Vue does not proxy it)
    remote: null,
    remoteVideo: false,
});

const ICE = window.__ICE__ || [{ urls: 'stun:stun.l.google.com:19302' }];
let call = null;        // { id, conversationId, peer, video, role, pc, local, pending, timeout }
let timerId = null;
let ringer = null;
let started = false;

const uid = () => (crypto.randomUUID ? crypto.randomUUID() : Date.now() + '-' + Math.random().toString(16).slice(2));
const toast = (t) => useUi().toast(t, 4000);

async function post(conversationId, callId, type, video, data) {
    await http.post(`/conversations/${conversationId}/call`, {
        type, call_id: callId, video: !!video, data: data ? JSON.stringify(data) : null,
    });
}
const send = (type, data) => post(call.conversationId, call.id, type, call.video, data);

// ---------- ringtone (best effort: browsers may keep it silent until the page was used) ----------
function startRing() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const beep = () => {
            const o = ctx.createOscillator(), g = ctx.createGain();
            o.frequency.value = 480; g.gain.value = 0.08;
            o.connect(g); g.connect(ctx.destination);
            o.start(); o.stop(ctx.currentTime + 0.5);
        };
        beep();
        ringer = { ctx, t: setInterval(beep, 1600) };
    } catch { /* silent ringing is fine */ }
}
function stopRing() {
    if (!ringer) return;
    clearInterval(ringer.t);
    ringer.ctx.close().catch(() => {});
    ringer = null;
}

const getMedia = (video) => navigator.mediaDevices.getUserMedia({ audio: true, video: video ? { facingMode: 'user' } : false });

function mediaError(e) {
    if (e?.name === 'NotAllowedError') return 'Allow microphone and camera access to make calls.';
    if (e?.name === 'NotFoundError') return 'No microphone or camera was found.';
    return 'Could not start the call.';
}

function openScreen(status) {
    callState.peer = call.peer;
    callState.video = call.video;
    callState.micOn = true;
    callState.camOn = true;
    callState.local = markRaw(call.local);
    callState.remote = null;
    callState.remoteVideo = false;
    callState.status = status;
    callState.active = true;
}

function startTimer() {
    const t0 = Date.now();
    clearInterval(timerId);
    timerId = setInterval(() => {
        const s = Math.floor((Date.now() - t0) / 1000);
        callState.status = String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
    }, 1000);
}

export function endCall(reason, notify = true) {
    if (!call) return;
    const c = call;
    call = null;
    clearTimeout(c.timeout);
    clearInterval(timerId);
    if (notify) post(c.conversationId, c.id, 'end', c.video).catch(() => {});
    if (c.pc) c.pc.close();
    if (c.local) c.local.getTracks().forEach((t) => t.stop());
    callState.active = false;
    callState.local = null;
    callState.remote = null;
    stopRing();
    if (reason) toast(reason);
}

function createPc() {
    const pc = new RTCPeerConnection({ iceServers: ICE });
    call.local.getTracks().forEach((t) => pc.addTrack(t, call.local));

    pc.ontrack = (e) => {
        callState.remote = markRaw(e.streams[0]);
        if (call?.video) callState.remoteVideo = true;
    };
    pc.onicecandidate = (e) => { if (e.candidate && call) send('candidate', e.candidate.toJSON()).catch(() => {}); };
    pc.onconnectionstatechange = () => {
        if (!call) return;
        const s = pc.connectionState;
        if (s === 'connected') { clearTimeout(call.timeout); startTimer(); send('connected').catch(() => {}); }
        else if (s === 'disconnected') callState.status = 'Reconnecting…';
        else if (s === 'failed') endCall('The connection failed. A TURN server may be needed on this network.', true);
    };
    call.pc = pc;
}

async function flushCandidates() {
    const list = call.pending;
    call.pending = [];
    for (const c of list) await call.pc.addIceCandidate(c).catch(() => {});
}

// ---------- caller ----------
export async function startCall(conversationId, peer, video) {
    if (call || callState.incoming) return toast('You are already in a call.');
    if (!navigator.mediaDevices || !window.RTCPeerConnection) {
        return toast('Calls need HTTPS (or localhost) and a modern browser.');
    }

    let stream;
    try { stream = await getMedia(video); } catch (e) { return toast(mediaError(e)); }

    call = { id: uid(), conversationId, peer, video: !!video, role: 'caller', local: stream, pending: [] };
    openScreen('Calling…');

    try { await send('invite'); }
    catch (e) { return endCall(errorText(e, 'Could not reach the server.'), false); }

    call.timeout = setTimeout(() => endCall('No answer.', true), 45000);
}

// ---------- callee ----------
function showIncoming(s) {
    const incoming = { id: s.call_id, conversationId: s.conversation_id, peer: s.from, video: !!s.video };
    incoming.timer = setTimeout(() => {
        if (callState.incoming?.id === incoming.id) { closeIncoming(); toast('Missed call from ' + incoming.peer.name); }
    }, 45000);
    callState.incoming = incoming;
    startRing();
}

function closeIncoming() {
    if (!callState.incoming) return;
    clearTimeout(callState.incoming.timer);
    callState.incoming = null;
    stopRing();
}

export async function acceptCall() {
    const i = callState.incoming;
    if (!i) return;

    let stream;
    try { stream = await getMedia(i.video); }
    catch (e) { toast(mediaError(e)); return declineCall(); }

    closeIncoming();
    call = { id: i.id, conversationId: i.conversationId, peer: i.peer, video: i.video, role: 'callee', local: stream, pending: [] };
    openScreen('Connecting…');
    createPc();

    try { await send('accept'); }
    catch (e) { endCall(errorText(e, 'Could not reach the server.'), false); }
}

export function declineCall() {
    const i = callState.incoming;
    if (!i) return;
    closeIncoming();
    post(i.conversationId, i.id, 'decline', i.video).catch(() => {});
}

// ---------- controls ----------
export function toggleMic() {
    const tracks = call?.local.getAudioTracks() || [];
    if (!tracks.length) return;
    const on = !tracks[0].enabled;
    tracks.forEach((t) => (t.enabled = on));
    callState.micOn = on;
}

export function toggleCam() {
    const tracks = call?.local.getVideoTracks() || [];
    if (!tracks.length) return;
    const on = !tracks[0].enabled;
    tracks.forEach((t) => (t.enabled = on));
    callState.camOn = on;
}

export const hangUp = () => endCall('Call ended.', true);

// ---------- incoming signals ----------
async function onSignal(s) {
    let data = null;
    try { data = s.data ? JSON.parse(s.data) : null; } catch { /* ignore */ }

    try {
        switch (s.type) {
            case 'invite':
                if (call || callState.incoming) { post(s.conversation_id, s.call_id, 'busy', s.video).catch(() => {}); return; }
                return showIncoming(s);

            case 'end':
                if (callState.incoming?.id === s.call_id) { const n = callState.incoming.peer.name; closeIncoming(); toast('Missed call from ' + n); }
                else if (call?.id === s.call_id) endCall('The call ended.', false);
                return;

            case 'decline':
                if (call?.id === s.call_id) endCall(call.peer.name + ' declined the call.', false);
                return;

            case 'busy':
                if (call?.id === s.call_id) endCall(call.peer.name + ' is on another call.', false);
                return;
        }

        if (!call || call.id !== s.call_id) return;   // the rest belongs to the active call only

        if (s.type === 'accept' && call.role === 'caller') {
            clearTimeout(call.timeout);
            callState.status = 'Connecting…';
            createPc();
            const offer = await call.pc.createOffer();
            await call.pc.setLocalDescription(offer);
            await send('offer', { type: offer.type, sdp: offer.sdp });
        } else if (s.type === 'offer' && call.role === 'callee') {
            await call.pc.setRemoteDescription(data);
            await flushCandidates();
            const answer = await call.pc.createAnswer();
            await call.pc.setLocalDescription(answer);
            await send('answer', { type: answer.type, sdp: answer.sdp });
        } else if (s.type === 'answer' && call.role === 'caller') {
            await call.pc.setRemoteDescription(data);
            await flushCandidates();
        } else if (s.type === 'candidate' && data) {
            if (call.pc?.remoteDescription) await call.pc.addIceCandidate(data).catch(() => {});
            else call.pending.push(data);   // arrived before the offer or answer: keep it for later
        }
    } catch (e) {
        console.error('[Call]', e);
        endCall('The call failed.', true);
    }
}

/** Listen for calls on your private channel. Safe to call more than once. */
export function initCalls(userId) {
    if (started) return;
    const echo = getEcho();
    if (!echo) return;
    started = true;
    echo.private('user.' + userId).listen('.call.signal', onSignal);
    window.addEventListener('pagehide', () => { if (call?.pc) call.pc.close(); });
}
