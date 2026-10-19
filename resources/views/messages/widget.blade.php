@include('messages.linkify')
<div id="chat-widget" class="fixed bottom-4 right-4 z-50 flex w-80 max-w-[calc(100vw-2rem)] flex-col items-end gap-2" data-me="{{ auth()->id() }}"
     data-feed="{{ route('chat.feed') }}"
     data-pm-poll="{{ url('/messages/__ID__/poll') }}" data-pm-send="{{ url('/messages/__ID__') }}"
     data-gm-poll="{{ url('/tools/group-finder/__ID__/chat') }}" data-gm-send="{{ url('/tools/group-finder/__ID__/chat') }}"
     data-csrf="{{ csrf_token() }}">
    <div id="chat-toasts" class="flex w-full flex-col items-end gap-2"></div>

    <section id="chat-panel" hidden class="flex h-96 w-full flex-col overflow-hidden rounded-xl border border-slate-700 bg-slate-900 shadow-2xl">
        <header class="flex items-center justify-between gap-2 border-b border-slate-700 bg-slate-800 px-3 py-2">
            <p id="chat-title" class="truncate text-sm font-black text-white"></p>
            <div class="flex items-center gap-2">
                <button type="button" id="chat-min" class="text-lg leading-none text-slate-400 hover:text-white" aria-label="Minimizar" title="Minimizar">–</button>
                <button type="button" id="chat-close" class="text-lg leading-none text-slate-400 hover:text-white" aria-label="Fechar" title="Fechar">×</button>
            </div>
        </header>
        <div id="chat-log-mini" class="flex flex-1 flex-col gap-1.5 overflow-y-auto p-3"></div>
        <form id="chat-form-mini" class="flex gap-2 border-t border-slate-700 p-2">
            <input type="text" id="chat-input-mini" maxlength="1000" autocomplete="off" placeholder="Mensagem..." class="tool-input flex-1">
            <button class="tool-button !px-3">Enviar</button>
        </form>
    </section>
</div>

<script>
(() => {
    const root = document.getElementById('chat-widget');
    const panel = document.getElementById('chat-panel');
    const logEl = document.getElementById('chat-log-mini');
    const form = document.getElementById('chat-form-mini');
    const input = document.getElementById('chat-input-mini');
    const toasts = document.getElementById('chat-toasts');
    let current = null;
    let last = 0;

    const url = (tpl, id) => root.dataset[tpl].replace('__ID__', id);
    const headers = { Accept: 'application/json' };
    const store = (k, v) => v === undefined ? sessionStorage.getItem(k) : sessionStorage.setItem(k, v);

    const bubble = (m, group) => {
        const box = document.createElement('div');
        box.dataset.id = m.id;
        box.className = 'max-w-[85%] rounded-lg px-2.5 py-1 text-xs ' + (m.mine ? 'self-end bg-amber-400/15 text-amber-50' : 'self-start bg-slate-800 text-slate-100');
        if (group && !m.mine) {
            const from = document.createElement('p');
            from.className = 'text-[10px] font-bold text-amber-300';
            from.textContent = m.from;
            box.append(from);
        }
        const body = document.createElement('p');
        body.className = 'whitespace-pre-wrap break-words';
        window.linkify(body, m.body);
        const time = document.createElement('p');
        time.className = 'text-[9px] text-slate-500';
        time.textContent = m.time;
        box.append(body, time);
        return box;
    };

    const render = (messages) => {
        const stick = logEl.scrollHeight - logEl.scrollTop - logEl.clientHeight < 40;
        messages.forEach((m) => {
            if (logEl.querySelector(`[data-id="${m.id}"]`)) return;
            logEl.append(bubble(m, current.type === 'group'));
            last = Math.max(last, m.id);
        });
        if (stick || messages.length) logEl.scrollTop = logEl.scrollHeight;
    };

    const poll = async () => {
        if (!current) return;
        const chat = current;
        try {
            const res = await fetch(url(chat.type === 'group' ? 'gmPoll' : 'pmPoll', chat.id) + `?after=${last}`, { headers });
            if (!res.ok || chat !== current) return;
            render((await res.json()).messages);
        } catch (e) {}
    };

    window.openChat = (chat) => {
        current = { type: chat.type, id: chat.id, title: chat.title };
        last = 0;
        delete chips[chipKey(chat.type, chat.id)];
        saveChips();
        renderChips();
        logEl.replaceChildren();
        document.getElementById('chat-title').textContent = (chat.type === 'group' ? '👥 ' : '✉ ') + chat.title;
        panel.hidden = false;
        store('chat-open', JSON.stringify({ ...chat }));
        poll().then(() => input.focus());
    };

    document.getElementById('chat-close').addEventListener('click', () => {
        panel.hidden = true;
        current = null;
        store('chat-open', '');
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const text = input.value.trim();
        if (!text || !current) return;
        input.value = '';
        try {
            const res = await fetch(url(current.type === 'group' ? 'gmSend' : 'pmSend', current.id), {
                method: 'POST',
                headers: { ...headers, 'Content-Type': 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf },
                body: JSON.stringify({ body: text }),
            });
            if (!res.ok) { input.value = text; return; }
            await poll();
        } catch (e) { input.value = text; }
    });

    const chips = (() => { try { return JSON.parse(store('chat-chips') || '{}'); } catch (e) { return {}; } })();
    const saveChips = () => store('chat-chips', JSON.stringify(chips));

    const renderChips = () => {
        toasts.replaceChildren();
        Object.entries(chips).forEach(([key, chip]) => {
            const el = document.createElement('button');
            el.type = 'button';
            el.title = chip.title;
            el.className = 'flex max-w-full items-center gap-2 rounded-full border border-amber-400/60 bg-slate-900 py-1.5 pl-3 pr-2 text-left shadow-xl hover:bg-slate-800';
            const label = document.createElement('span');
            label.className = 'min-w-0';
            const title = document.createElement('span');
            title.className = 'block truncate text-xs font-black text-amber-300';
            title.textContent = (chip.type === 'group' ? '👥 ' : '✉ ') + chip.title;
            const body = document.createElement('span');
            body.className = 'block truncate text-[11px] text-slate-300';
            body.textContent = chip.preview || '';
            label.append(title, body);
            el.append(label);
            if (chip.count > 0) {
                const badge = document.createElement('span');
                badge.className = 'shrink-0 rounded-full bg-amber-400 px-1.5 text-[10px] font-black text-slate-900';
                badge.textContent = chip.count > 99 ? '99+' : chip.count;
                el.append(badge);
            }
            el.addEventListener('click', () => window.openChat({ type: chip.type, id: chip.id, title: chip.title }));
            toasts.append(el);
        });
    };

    const chipKey = (type, id) => `${type}:${id}`;

    const toast = (item) => {
        const key = chipKey(item.type, item.chat_id);
        const chip = chips[key] || { type: item.type, id: item.chat_id, title: item.title, count: 0 };
        chip.count += 1;
        chip.preview = (item.from ? item.from + ': ' : '') + item.body;
        delete chips[key];
        chips[key] = chip;
        saveChips();
        renderChips();
    };

    const minimize = () => {
        if (!current) return;
        const key = chipKey(current.type, current.id);
        chips[key] = { type: current.type, id: current.id, title: current.title, count: 0, preview: 'Toque para abrir' };
        saveChips();
        panel.hidden = true;
        current = null;
        store('chat-open', '');
        renderChips();
    };
    document.getElementById('chat-min').addEventListener('click', minimize);

    const feed = async () => {
        if (document.hidden) return;
        const pm = store('chat-pm'), gm = store('chat-gm');
        const query = pm !== null && gm !== null ? `?pm=${pm}&gm=${gm}` : '';
        try {
            const res = await fetch(root.dataset.feed + query, { headers });
            if (!res.ok) return;
            const data = await res.json();
            data.items.forEach((item) => {
                const viewing = current && current.type === item.type && String(current.id) === String(item.chat_id);
                const onPage = item.type === 'pm' && location.pathname === `/messages/${item.chat_id}`;
                if (!viewing && !onPage) toast(item);
            });
            const maxPm = data.items.filter((i) => i.type === 'pm').reduce((a, i) => Math.max(a, i.id), data.pm_latest);
            const maxGm = data.items.filter((i) => i.type === 'group').reduce((a, i) => Math.max(a, i.id), data.gm_latest);
            store('chat-pm', Math.max(Number(pm ?? 0), maxPm));
            store('chat-gm', Math.max(Number(gm ?? 0), maxGm));
        } catch (e) {}
    };

    try {
        const saved = JSON.parse(store('chat-open') || 'null');
        if (saved) window.openChat(saved);
    } catch (e) {}

    renderChips();
    feed();
    setInterval(() => { feed(); poll(); }, 4000);
})();
</script>
