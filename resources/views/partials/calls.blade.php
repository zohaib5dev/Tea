{{-- Voice and video calls (WebRTC). Signaling goes through Reverb, media goes browser to browser.
     Include once in the layout for logged-in users, AFTER partials.echo. --}}

{{-- Incoming call --}}
<dialog id="incoming-call" aria-labelledby="incoming-name"
        class="w-[calc(100%-2rem)] max-w-sm rounded-3xl border border-gold-500/40 bg-ink-900 p-0 text-ink-50 backdrop:bg-black/80 backdrop:backdrop-blur-sm">
    <div class="flex flex-col items-center px-6 py-8 text-center">
        <div id="incoming-avatar" class="mb-4"></div>
        <p id="incoming-kind" class="text-xs font-bold uppercase tracking-[0.2em] text-gold-400">Voice call</p>
        <h2 id="incoming-name" class="mt-1 font-display text-3xl font-bold"></h2>
        <p class="mt-1 animate-pulse text-sm text-ink-300">is calling you…</p>

        <div class="mt-8 flex items-center gap-8">
            <div class="text-center">
                <button type="button" id="incoming-decline" class="grid h-16 w-16 place-items-center rounded-full bg-red-500 text-white shadow-lg shadow-red-500/30 transition hover:scale-105" aria-label="Decline call">
                    <x-icon name="phone-off" class="h-7 w-7"/>
                </button>
                <p class="mt-2 text-xs text-ink-300">Decline</p>
            </div>
            <div class="text-center">
                <button type="button" id="incoming-accept" class="grid h-16 w-16 animate-bounce place-items-center rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 transition hover:scale-105" aria-label="Accept call">
                    <x-icon name="phone" class="h-7 w-7"/>
                </button>
                <p class="mt-2 text-xs text-ink-300">Accept</p>
            </div>
        </div>
    </div>
</dialog>

{{-- Call screen --}}
<div id="call-overlay" class="fixed inset-0 z-[70] hidden flex-col bg-ink-950" role="dialog" aria-modal="true" aria-label="Call">
    <div class="relative flex-1 overflow-hidden">
        <video id="remote-video" autoplay playsinline class="hidden h-full w-full object-cover"></video>

        <div id="call-avatar" class="absolute inset-0 grid place-items-center bg-[radial-gradient(600px_300px_at_50%_30%,rgba(212,166,58,.18),transparent)]">
            <div class="flex flex-col items-center text-center">
                <div id="call-avatar-circle"></div>
            </div>
        </div>

        <div class="absolute left-4 top-4 rounded-2xl bg-ink-950/60 px-4 py-2 backdrop-blur">
            <p id="call-name" class="font-bold"></p>
            <p id="call-status" class="text-xs text-gold-300">Calling…</p>
        </div>

        <video id="local-video" autoplay playsinline muted class="absolute bottom-4 right-4 hidden h-40 w-28 -scale-x-100 rounded-2xl border border-gold-500/50 bg-ink-800 object-cover shadow-xl sm:h-48 sm:w-36"></video>
    </div>

    <div class="flex items-center justify-center gap-5 border-t border-ink-700 bg-ink-900 px-4 py-5">
        <button type="button" id="call-mic" class="grid h-14 w-14 place-items-center rounded-full bg-ink-800 transition hover:bg-ink-700" aria-label="Mute microphone">
            <x-icon name="mic" class="h-6 w-6"/>
            <x-icon name="mic-off" class="hidden h-6 w-6 text-red-400"/>
        </button>
        <button type="button" id="call-cam" class="grid h-14 w-14 place-items-center rounded-full bg-ink-800 transition hover:bg-ink-700" aria-label="Turn camera off">
            <x-icon name="video" class="h-6 w-6"/>
            <x-icon name="video-off" class="hidden h-6 w-6 text-red-400"/>
        </button>
        <button type="button" id="call-end" class="grid h-16 w-16 place-items-center rounded-full bg-red-500 text-white shadow-lg shadow-red-500/30 transition hover:scale-105" aria-label="End call">
            <x-icon name="phone-off" class="h-7 w-7"/>
        </button>
    </div>
</div>

<script>
(() => {
    // Calls need real time (Reverb) and a secure page (HTTPS or localhost) for camera and microphone.
    if (!window.Echo || !navigator.mediaDevices || !window.RTCPeerConnection) {
        window.startCall = () => toast('Calls are not available here. They need Reverb running and HTTPS (or localhost).');
        return;
    }

    const ME = @json(auth()->id());
    const SIGNAL_URL = @json(route('calls.signal', ':id'));
    const STORAGE = @json(asset('storage')) + '/';
    // Add a TURN server in config/services.php (webrtc.ice_servers) so calls also work behind strict networks.
    const ICE_SERVERS = @json(config('services.webrtc.ice_servers', [['urls' => 'stun:stun.l.google.com:19302']]));

    const $ = (id) => document.getElementById(id);
    const overlay = $('call-overlay'), remoteVideo = $('remote-video'), localVideo = $('local-video');
    const avatarBox = $('call-avatar'), avatarCircle = $('call-avatar-circle');
    const nameEl = $('call-name'), statusEl = $('call-status');
    const btnMic = $('call-mic'), btnCam = $('call-cam'), btnEnd = $('call-end');
    const modal = $('incoming-call');

    let call = null;        // the active call
    let incoming = null;    // a call that is ringing
    let timerId = null;
    let ring = null;

    const uid = () => (crypto.randomUUID ? crypto.randomUUID() : Date.now() + '-' + Math.random().toString(16).slice(2));
    const initials = (name) => name.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0].toUpperCase()).join('');

    function fillAvatar(box, peer, size) {
        box.replaceChildren();
        const wrap = document.createElement('span');
        wrap.className = 'mx-auto inline-grid place-items-center overflow-hidden rounded-full bg-gold-grad font-bold text-ink-950 ring-4 ring-gold-500/40 ' + size;
        if (peer.avatar) {
            const img = document.createElement('img');
            img.src = STORAGE + peer.avatar; img.alt = '';
            img.className = 'h-full w-full object-cover';
            wrap.append(img);
        } else {
            wrap.textContent = initials(peer.name);
        }
        box.append(wrap);
    }

    // ---------- Signaling ----------
    async function post(conversationId, callId, type, video, data) {
        const r = await fetch(SIGNAL_URL.replace(':id', conversationId), {
            method: 'POST',
            headers: { ...HEADERS, 'Content-Type': 'application/json' },
            body: JSON.stringify({ type, call_id: callId, video: !!video, data: data ? JSON.stringify(data) : null }),
        });
        if (!r.ok) {
            let msg = 'Server error (' + r.status + ')';
            try { const j = await r.json(); if (j.message) msg = j.message + ' (' + r.status + ')'; } catch {}
            throw new Error(msg);
        }
    }
    const send = (type, data) => post(call.conversationId, call.id, type, call.video, data);

    // ---------- Ringtone (best effort: browsers may keep it silent until the page was used) ----------
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
            ring = { ctx, t: setInterval(beep, 1600) };
        } catch {}
    }
    function stopRing() {
        if (!ring) return;
        clearInterval(ring.t);
        ring.ctx.close().catch(() => {});
        ring = null;
    }

    // ---------- Media ----------
    const getMedia = (video) => navigator.mediaDevices.getUserMedia({ audio: true, video: video ? { facingMode: 'user' } : false });
    function mediaError(e) {
        if (e && e.name === 'NotAllowedError') return 'Allow microphone and camera access to make calls.';
        if (e && e.name === 'NotFoundError') return 'No microphone or camera was found.';
        return 'Could not start the call.';
    }

    // ---------- Call screen ----------
    function setStatus(text) { statusEl.textContent = text; }

    function showOverlay(status) {
        nameEl.textContent = call.peer.name;
        fillAvatar(avatarCircle, call.peer, 'h-32 w-32 text-4xl');
        avatarBox.classList.remove('hidden');
        remoteVideo.classList.add('hidden');
        localVideo.srcObject = call.local;
        localVideo.classList.toggle('hidden', !call.video);
        btnCam.classList.toggle('hidden', !call.video);
        setStatus(status);
        overlay.classList.remove('hidden'); overlay.classList.add('flex');
    }

    function startTimer() {
        const t0 = Date.now();
        clearInterval(timerId);
        timerId = setInterval(() => {
            const s = Math.floor((Date.now() - t0) / 1000);
            setStatus(String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'));
        }, 1000);
    }

    function endCall(reason, notify = true) {
        if (!call) return;
        const c = call;
        call = null;
        clearTimeout(c.timeout);
        clearInterval(timerId);
        if (notify) post(c.conversationId, c.id, 'end', c.video).catch(() => {});
        if (c.pc) c.pc.close();
        if (c.local) c.local.getTracks().forEach((t) => t.stop());
        remoteVideo.srcObject = null; localVideo.srcObject = null;
        overlay.classList.add('hidden'); overlay.classList.remove('flex');
        stopRing();
        if (reason) toast(reason);
    }

    // ---------- WebRTC ----------
    function createPc() {
        const pc = new RTCPeerConnection({ iceServers: ICE_SERVERS });
        call.local.getTracks().forEach((t) => pc.addTrack(t, call.local));

        pc.ontrack = (e) => {
            remoteVideo.srcObject = e.streams[0];
            if (call.video) { remoteVideo.classList.remove('hidden'); avatarBox.classList.add('hidden'); }
        };
        pc.onicecandidate = (e) => { if (e.candidate && call) send('candidate', e.candidate.toJSON()).catch(() => {}); };
        pc.onconnectionstatechange = () => {
            if (!call) return;
            const s = pc.connectionState;
            if (s === 'connected') { clearTimeout(call.timeout); startTimer(); send('connected').catch(() => {}); }
            else if (s === 'disconnected') setStatus('Reconnecting…');
            else if (s === 'failed') endCall('The connection failed. A TURN server may be needed on this network.', true);
        };
        call.pc = pc;
    }

    async function flushCandidates() {
        const list = call.pending; call.pending = [];
        for (const c of list) await call.pc.addIceCandidate(c).catch(() => {});
    }

    // ---------- Starting a call (caller) ----------
    window.startCall = async (conversationId, peer, video) => {
        if (call || incoming) return toast('You are already in a call.');
        let stream;
        try { stream = await getMedia(video); } catch (e) { return toast(mediaError(e)); }

        call = { id: uid(), conversationId, peer, video: !!video, role: 'caller', local: stream, pending: [] };
        showOverlay('Calling…');

        try { await send('invite'); }
        catch (e) { return endCall(e.message, false); }

        call.timeout = setTimeout(() => endCall('No answer.', true), 45000);
    };

    // ---------- Ringing (callee) ----------
    function showIncoming(s) {
        incoming = { id: s.call_id, conversationId: s.conversation_id, peer: s.from, video: !!s.video };
        $('incoming-name').textContent = s.from.name;
        $('incoming-kind').textContent = s.video ? 'Video call' : 'Voice call';
        fillAvatar($('incoming-avatar'), s.from, 'h-24 w-24 text-3xl');
        modal.showModal();
        startRing();
        incoming.timer = setTimeout(() => { const n = incoming && incoming.peer.name; closeIncoming(); if (n) toast('Missed call from ' + n); }, 45000);
    }

    function closeIncoming() {
        if (!incoming) return;
        clearTimeout(incoming.timer);
        incoming = null;
        stopRing();
        if (modal.open) modal.close();
    }

    $('incoming-accept').onclick = async () => {
        const i = incoming;
        if (!i) return;
        let stream;
        try { stream = await getMedia(i.video); }
        catch (e) { toast(mediaError(e)); $('incoming-decline').click(); return; }

        closeIncoming();
        call = { id: i.id, conversationId: i.conversationId, peer: i.peer, video: i.video, role: 'callee', local: stream, pending: [] };
        showOverlay('Connecting…');
        createPc();
        try { await send('accept'); }
        catch (e) { endCall(e.message, false); }
    };

    $('incoming-decline').onclick = () => {
        const i = incoming;
        if (!i) return;
        closeIncoming();
        post(i.conversationId, i.id, 'decline', i.video).catch(() => {});
    };

    // Esc closes the dialog: treat it as "decline"
    modal.addEventListener('cancel', (e) => { e.preventDefault(); $('incoming-decline').click(); });

    // ---------- Controls ----------
    function toggleTrack(kind, button) {
        if (!call) return;
        const tracks = kind === 'audio' ? call.local.getAudioTracks() : call.local.getVideoTracks();
        if (!tracks.length) return;
        const on = !tracks[0].enabled;
        tracks.forEach((t) => t.enabled = on);
        const [iconOn, iconOff] = button.querySelectorAll('svg');
        iconOn.classList.toggle('hidden', !on);
        iconOff.classList.toggle('hidden', on);
    }
    btnMic.onclick = () => toggleTrack('audio', btnMic);
    btnCam.onclick = () => toggleTrack('video', btnCam);
    btnEnd.onclick = () => endCall('Call ended.', true);

    // ---------- Incoming signals ----------
    Echo.private('user.' + ME).listen('.call.signal', async (s) => {
        let data = null;
        try { data = s.data ? JSON.parse(s.data) : null; } catch {}

        try {
            switch (s.type) {
                case 'invite':
                    if (call || incoming) { post(s.conversation_id, s.call_id, 'busy', s.video).catch(() => {}); return; }
                    return showIncoming(s);

                case 'end':
                    if (incoming && incoming.id === s.call_id) { const n = incoming.peer.name; closeIncoming(); toast('Missed call from ' + n); }
                    else if (call && call.id === s.call_id) endCall('The call ended.', false);
                    return;

                case 'decline':
                    if (call && call.id === s.call_id) endCall(call.peer.name + ' declined the call.', false);
                    return;

                case 'busy':
                    if (call && call.id === s.call_id) endCall(call.peer.name + ' is on another call.', false);
                    return;
            }

            if (!call || call.id !== s.call_id) return;   // everything below belongs to the active call only

            if (s.type === 'accept' && call.role === 'caller') {
                clearTimeout(call.timeout);
                setStatus('Connecting…');
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
                if (call.pc && call.pc.remoteDescription) await call.pc.addIceCandidate(data).catch(() => {});
                else call.pending.push(data);   // arrived before the offer or answer: keep it for later
            }
        } catch (e) {
            console.error('[Call]', e);
            endCall('The call failed.', true);
        }
    });

    // Leaving the page ends the call for the other person too.
    window.addEventListener('pagehide', () => { if (call && call.pc) call.pc.close(); });
})();
</script>
