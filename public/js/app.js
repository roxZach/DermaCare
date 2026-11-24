/* DermaCare AI front-end helpers (jQuery) */
$(function () {

    /* ---------------- FAQ chatbot ---------------- */
    const $panel = $('#chat-panel'), $body = $('#chat-body');
    $('#chat-toggle, #chat-close').on('click', () => { $panel.toggleClass('d-none'); $('#chat-input').trigger('focus'); });
    const esc = s => $('<div>').text(s).html();
    function addMsg(text, who) { $body.append(`<div class="chat-msg ${who}">${esc(text)}</div>`); $body.scrollTop($body[0].scrollHeight); }
    function ask(q) {
        if (!q.trim()) return;
        addMsg(q, 'user');
        $('#chat-suggest').addClass('d-none');
        const $typing = $('<div class="chat-msg bot text-muted">Typing...</div>').appendTo($body);
        $.post(window.DC_CHAT.ask, { question: q }).done(r => {
            $typing.remove();
            addMsg(r.answer, 'bot');
            let meta = `<div class="chat-meta">`;
            if (r.matched_question) meta += `Matched: "${esc(r.matched_question)}" · confidence ${Math.round(r.confidence * 100)}% · `;
            if (r.suggest_booking) meta += `<a href="${r.booking_url}">Book an appointment</a> · `;
            meta += `Helpful? <button class="btn btn-link chat-rate" data-id="${r.id}" data-v="1"><i class="bi bi-hand-thumbs-up"></i></button>`
                + `<button class="btn btn-link chat-rate" data-id="${r.id}" data-v="-1"><i class="bi bi-hand-thumbs-down"></i></button></div>`;
            $body.append(meta); $body.scrollTop($body[0].scrollHeight);
        }).fail(() => { $typing.remove(); addMsg('Sorry, something went wrong. Please try again.', 'bot'); });
    }
    $('#chat-form').on('submit', e => { e.preventDefault(); const q = $('#chat-input').val(); $('#chat-input').val(''); ask(q); });
    $(document).on('click', '.chat-chip', function () { ask($(this).text()); });
    $(document).on('click', '.chat-rate', function () {
        const $b = $(this);
        $.post(`${window.DC_CHAT.rate}/${$b.data('id')}/rate`, { rating: $b.data('v') })
            .done(() => $b.parent().find('.chat-rate').remove().end().append('<span>Thanks for the feedback!</span>'));
    });

    /* ---------------- Body map: click to place a lesion ---------------- */
    function guessRegion(view, x, y) {
        if (y < 14) return view === 'front' ? 'Face / head' : 'Scalp / back of head';
        if (y < 18) return 'Neck';
        if (x < 29 || x > 71) return (x < 50 ? 'Right' : 'Left') + ' arm';
        if (y < 36) return view === 'front' ? 'Chest' : 'Upper back';
        if (y < 52) return view === 'front' ? 'Abdomen' : 'Lower back';
        if (y < 60) return view === 'front' ? 'Groin / hip' : 'Buttocks';
        return (x < 50 ? 'Right' : 'Left') + ' leg';
    }
    $('.bodymap-clickable .bm-svg').on('click', function (e) {
        const svg = this, pt = svg.createSVGPoint();
        pt.x = e.clientX; pt.y = e.clientY;
        const p = pt.matrixTransform(svg.getScreenCTM().inverse());
        const x = Math.max(0, Math.min(100, p.x / 2)), y = Math.max(0, Math.min(100, p.y / 4.4));
        const view = $(svg).data('view');
        $('.bm-new').addClass('d-none');
        $(svg).find('.bm-new').attr({ cx: p.x, cy: p.y }).removeClass('d-none');
        const region = guessRegion(view, x, y);
        $('#bm-x').val(x.toFixed(2)); $('#bm-y').val(y.toFixed(2)); $('#bm-view').val(view);
        if (!$('#bm-region').data('touched')) $('#bm-region').val(region);
        $('#bm-picked').text(`Selected: ${view}, ${region}`);
    });
    $('#bm-region').on('input', function () { $(this).data('touched', true); });

    /* ---------------- Appointment slots ---------------- */
    function loadSlots() {
        const $f = $('[data-slots-url]');
        if (!$f.length) return;
        const doctor = $('#doctor_id').val(), date = $('#date').val(), $t = $('#time');
        if (!doctor || !date) { $t.html('<option value="">Choose a doctor and a date first</option>'); return; }
        const url = $f.data('slots-url').replace('__ID__', doctor);
        $t.html('<option>Loading...</option>');
        $.get(url, { date }).done(r => {
            const keep = $t.data('current');
            $t.empty();
            if (keep && !r.slots.includes(keep)) r.slots.unshift(keep);
            if (!r.slots.length) $t.append('<option value="">No free slots on this day</option>');
            r.slots.forEach(s => $t.append(`<option ${s === keep ? 'selected' : ''}>${s}</option>`));
        });
    }
    $('#doctor_id, #date').on('change', loadSlots);
    loadSlots();

    /* Availability checker on the public doctor page (<<extend>> Check Doctor Availability) */
    $('#avail-date').on('change', function () {
        const $out = $('#avail-out').html('Loading...');
        $.get($(this).data('url'), { date: this.value }).done(r => {
            $out.html(r.slots.length ? r.slots.map(s => `<span class="badge text-bg-light border me-1 mb-1">${s}</span>`).join('')
                : '<span class="text-muted">No free slots on this day.</span>');
        });
    });

    /* ---------------- Repeating form rows (prescription items, invoice items, schedules) ---------------- */
    $(document).on('click', '[data-add-row]', function () {
        const $tpl = $($(this).data('add-row')), $target = $($(this).data('target'));
        const i = Date.now();
        $target.append($tpl.html().replace(/__I__/g, i));
        recalcInvoice();
    });
    $(document).on('click', '.remove-row', function () { $(this).closest('.form-row-item').remove(); recalcInvoice(); });

    /* Invoice: pick a service to fill description and price, then recalculate totals */
    $(document).on('change', '.svc-pick', function () {
        const $o = $(this).find(':selected'), $r = $(this).closest('.form-row-item');
        if ($o.val()) { $r.find('.inv-desc').val($o.data('name')); $r.find('.inv-price').val($o.data('price')); }
        recalcInvoice();
    });
    $(document).on('input', '.inv-qty, .inv-price, #discount', recalcInvoice);
    function recalcInvoice() {
        if (!$('#inv-subtotal').length) return;
        let sub = 0;
        $('.form-row-item').each(function () { sub += (+$(this).find('.inv-qty').val() || 0) * (+$(this).find('.inv-price').val() || 0); });
        const disc = Math.min(+$('#discount').val() || 0, sub);
        $('#inv-subtotal').text(sub.toFixed(2)); $('#inv-total').text((sub - disc).toFixed(2));
    }
    recalcInvoice();

    /* Override form: show the "other diagnosis" field only when needed */
    $('input[name=decision]').on('change', function () { $('#override-fields').toggleClass('d-none', this.value !== 'override'); });
    $('#final_class').on('change', function () { $('#final-other').toggleClass('d-none', this.value !== 'other'); });

    /* Confirm dangerous actions */
    $(document).on('submit', 'form[data-confirm]', function (e) { if (!confirm($(this).data('confirm'))) e.preventDefault(); });
});
