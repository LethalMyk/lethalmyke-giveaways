<script>
window.linkify = window.linkify || ((el, text) => {
    const pattern = /\b((?:https?:\/\/|www\.)[^\s<>"']+)/gi;
    el.replaceChildren();
    let index = 0;
    for (const match of text.matchAll(pattern)) {
        let url = match[0];
        const trail = url.match(/[.,;:!?)\]]+$/);
        if (trail) url = url.slice(0, -trail[0].length);
        el.append(text.slice(index, match.index));
        const a = document.createElement('a');
        a.href = /^www\./i.test(url) ? 'https://' + url : url;
        a.textContent = url;
        a.target = '_blank';
        a.rel = 'noopener noreferrer nofollow';
        a.className = 'break-all text-sky-300 underline hover:text-sky-200';
        a.addEventListener('click', (event) => event.stopPropagation());
        el.append(a);
        index = match.index + url.length;
    }
    el.append(text.slice(index));
});
</script>
