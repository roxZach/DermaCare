{{-- Intelligent FAQ chatbot widget (Guests and Patients) --}}
<div id="chatbot" class="dc-chat">
    <button id="chat-toggle" class="btn dc-chat-btn shadow" title="Ask our FAQ assistant"><i class="bi bi-chat-dots-fill"></i></button>
    <div id="chat-panel" class="card shadow d-none">
        <div class="card-header d-flex justify-content-between align-items-center dc-chat-head">
            <span><i class="bi bi-robot"></i> DermaCare assistant</span>
            <button class="btn btn-sm text-white" id="chat-close"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="card-body" id="chat-body">
            <div class="chat-msg bot">Hi! I can answer common questions about our clinic, services, fees and skin care.
                I can't diagnose skin problems. For that, please book a dermatologist.</div>
        </div>
        <div class="px-3 pb-2 d-flex flex-wrap gap-1" id="chat-suggest">
            <button class="btn btn-outline-secondary btn-sm chat-chip">What are your opening hours?</button>
            <button class="btn btn-outline-secondary btn-sm chat-chip">How much is a consultation?</button>
            <button class="btn btn-outline-secondary btn-sm chat-chip">How do I book an appointment?</button>
        </div>
        <form id="chat-form" class="card-footer d-flex gap-2">
            <input class="form-control form-control-sm" id="chat-input" maxlength="500" placeholder="Type your question..." autocomplete="off">
            <button class="btn btn-primary btn-sm"><i class="bi bi-send"></i></button>
        </form>
    </div>
</div>
@push('scripts')
<script>
window.DC_CHAT = { ask: "{{ route('chat.ask') }}", rate: "{{ url('/chat') }}" };
</script>
@endpush
