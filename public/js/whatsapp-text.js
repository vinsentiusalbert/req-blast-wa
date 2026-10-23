(() => {
    'use strict';
    const markers = {strong: '*', b: '*', em: '_', i: '_', s: '~', strike: '~', del: '~', code: '```'};
    const blockTags = new Set(['DIV', 'P', 'UL', 'OL', 'BLOCKQUOTE', 'PRE']);
    const escape = text => text.replace(/[&<>"']/g, char => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[char]));
    const word = char => !!char && /[\p{L}\p{N}_]/u.test(char);

    // Only our own allowlisted tags are emitted; message content is always escaped.
    const inline = (text, depth = 0) => {
        if (depth > 8) return escape(text);
        let html = '';
        for (let i = 0; i < text.length;) {
            const marker = ['```', '`', '*', '_', '~'].find(token => text.startsWith(token, i));
            const code = marker === '```' || marker === '`';
            if (marker && !word(text[i - 1]) && text[i + marker.length] && (code || !/\s/u.test(text[i + marker.length]))) {
                let end = text.indexOf(marker, i + marker.length);
                while (end !== -1 && ((!code && /\s/u.test(text[end - 1])) || word(text[end + marker.length]))) {
                    end = text.indexOf(marker, end + marker.length);
                }
                if (end > i + marker.length) {
                    const value = text.slice(i + marker.length, end);
                    const tag = {'*': 'strong', '_': 'em', '~': 's', '`': 'code', '```': 'code'}[marker];
                    html += `<${tag}${marker === '`' ? ' data-inline-code="true"' : ''}>${tag === 'code' ? escape(value) : inline(value, depth + 1)}</${tag}>`;
                    i = end + marker.length;
                    continue;
                }
            }
            html += escape(text[i]);
            i++;
        }
        return html;
    };

    const toHtml = value => {
        const sourceLines = String(value || '').replace(/\r\n?/g, '\n').split('\n');
        const lines = [];
        for (let i = 0; i < sourceLines.length; i++) {
            let line = sourceLines[i];
            if ((line.match(/```/g) || []).length % 2) {
                const closing = sourceLines.findIndex((candidate, index) => index > i && candidate.includes('```'));
                if (closing > i) { line = sourceLines.slice(i, closing + 1).join('\n'); i = closing; }
            }
            lines.push(line);
        }
        let html = '', list = '';
        for (const line of lines) {
            const bullet = line.match(/^[-*] (.*)$/u);
            const number = line.match(/^(\d+)\. (.*)$/u);
            const next = bullet ? 'ul' : number ? 'ol' : '';
            if (list && next !== list) { html += `</${list}>`; list = ''; }
            if (next && !list) {
                html += next === 'ol' ? `<ol start="${Number(number[1])}">` : '<ul>';
                list = next;
            }
            html += next ? `<li>${inline(bullet ? bullet[1] : number[2]) || '<br>'}</li>` : `<div>${inline(line) || '<br>'}</div>`;
        }
        if (list) html += `</${list}>`;
        return html;
    };

    const serializeChildren = node => {
        let text = '';
        const children = [...node.childNodes];
        children.forEach((child, index) => {
            const block = blockTags.has(child.nodeName);
            if (block && index > 0 && !text.endsWith('\n')) text += '\n';
            text += serialize(child);
            if (block && index < children.length - 1) text += '\n';
        });
        return text;
    };
    const serialize = node => {
        if (node.nodeType === Node.TEXT_NODE) return node.data.replace(/\u00a0/g, ' ');
        if (node.nodeType !== Node.ELEMENT_NODE) return '';
        if (['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT'].includes(node.nodeName)) return '';
        if (node.nodeName === 'BR') return '\n';
        if (node.nodeName === 'UL' || node.nodeName === 'OL') {
            let index = Number(node.getAttribute('start')) || 1;
            return [...node.children].map(item => `${node.nodeName === 'OL' ? index++ + '. ' : '- '}${serializeChildren(item).replace(/\n$/, '')}`).join('\n');
        }
        let text = serializeChildren(node);
        // A lone BR is the browser's placeholder for an empty paragraph.
        if (node.childNodes.length === 1 && node.firstChild.nodeName === 'BR') text = '';
        let marker = markers[node.nodeName.toLowerCase()];
        if (node.nodeName === 'CODE' && node.dataset.inlineCode) marker = '`';
        const style = node.style;
        if (!marker && (/monospace/i.test(style.fontFamily) || /monospace/i.test(node.getAttribute('face') || ''))) marker = '```';
        if (!marker && (style.fontWeight === 'bold' || Number(style.fontWeight) >= 600)) marker = '*';
        if (!marker && style.fontStyle === 'italic') marker = '_';
        if (!marker && style.textDecoration.includes('line-through')) marker = '~';
        // WhatsApp markers must sit directly against non-whitespace text.
        if (marker && text.trim()) {
            if (marker.includes('`')) return marker + text + marker;
            return text.replace(/^(\s*)([\s\S]*?)(\s*)$/, (_, before, value, after) => before + marker + value + marker + after);
        }
        return text;
    };
    window.WhatsAppText = {toHtml, fromEditor: serializeChildren, length: value => [...value].length};
})();
