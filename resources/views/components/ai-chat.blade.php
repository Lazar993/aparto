<button id="ai-chat-open" type="button" class="aparto-ai-launch" aria-controls="ai-chat-modal" aria-haspopup="dialog">
    <span class="ai-icon">✨</span>
    <span class="ai-text">{{ __('frontpage.ai.ask') }}</span>
</button>

<!-- AI Chat Modal -->
<div id="ai-chat-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/55 transition-opacity duration-300">
    <div id="ai-chat-box" class="ai-chat-component w-[95%] sm:w-[430px] max-h-[82vh] flex flex-col overflow-hidden bg-white/90 backdrop-blur-xl shadow-2xl rounded-2xl border border-blue-200 transform scale-90 transition-all duration-300">
        
        <!-- Header -->
        <div class="flex items-center justify-between p-4 font-semibold bg-gradient-to-r from-blue-500 to-indigo-500 text-white shrink-0">
            <div class="flex items-center gap-2">
                <span class="text-xl">✨</span>
                <span>{{ __('frontpage.ai.assistant') }}</span>
            </div>
            <button id="close-chat" type="button" class="text-white/80 hover:text-white hover:bg-white/10 rounded-full w-8 h-8 flex items-center justify-center transition">✕</button>
        </div>

        <!-- Messages -->
        <div id="messages" class="p-4 flex-1 overflow-y-auto text-sm space-y-3 bg-white"></div>

        <!-- Input -->
        <div class="p-3 border-t border-blue-100 flex gap-2 bg-white shrink-0">
            <input id="chat-input" type="text" class="flex-1 border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400 outline-none transition text-sm" placeholder="{{ __('frontpage.ai.placeholder') }}">
            <button id="send-btn" type="button" class="bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600 text-white px-5 py-2 rounded-xl font-semibold shadow-md transition">{{ __('frontpage.ai.send') }}</button>
        </div>

    </div>
</div>

<script>
$(function(){

    const $openBtn = $('#ai-chat-open');
    const $modal = $('#ai-chat-modal');
    const $box = $('#ai-chat-box');
    const $messages = $('#messages');
    const $input = $('#chat-input');
    const $sendBtn = $('#send-btn');
    const i18n = {
        typing: @json(__('frontpage.ai.js.typing')),
        noResponse: @json(__('frontpage.ai.js.no_response')),
        connectionError: @json(__('frontpage.ai.js.connection_error')),
        apartmentFallbackTitle: @json(__('frontpage.ai.js.apartment_fallback_title')),
    };

    // Keep modal at document root so fixed overlay always covers full viewport.
    if ($modal.parent()[0] !== document.body) {
        $modal.appendTo(document.body);
    }

    // Funkcija za dodavanje poruke
    function addMessage(text, side='user'){
        const $div = $('<div>').addClass(side==='user' ? 'text-right' : 'text-left');
        const bubbleClass = side==='user' ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-900 border border-blue-200';
        $div.html(`<span class="inline-block px-3 py-2 rounded-xl shadow ${bubbleClass}">${text}</span>`);
        $messages.append($div);
        $messages.scrollTop($messages.prop('scrollHeight'));
    }

    // Slanje poruke
    function sendMessage(){
        const text = $input.val();
        if(!text) return;
        addMessage(text, 'user');
        $input.val('');
        addMessage(i18n.typing, 'bot');

        $.ajax({
            url: "{{ route('ai.search') }}",
            method: 'POST',
            contentType: 'application/json',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            data: JSON.stringify({message: text}),
            success: function(data){
                $messages.children().last().remove(); // ukloni placeholder
                addMessage(data.reply || i18n.noResponse, 'bot');
                if(Array.isArray(data.apartments)){
                    data.apartments.forEach(function(a){
                        const title = a.title || i18n.apartmentFallbackTitle;
                        const link = '/' + (a.id ? 'apartments/'+a.id : '');
                        addMessage(`🏠 <a href="${link}" target="_blank" class="underline text-blue-600 hover:text-blue-800">${title} – ${a.price_per_night}€</a>`, 'bot');
                    });
                }
            },
            error: function(){
                $messages.children().last().remove();
                addMessage(i18n.connectionError, 'bot');
            }
        });
    }

    // Otvaranje modala
    $openBtn.on('click', function(){
        $modal.removeClass('hidden').css('opacity',0);
        $box.removeClass('scale-90').addClass('scale-100');
        setTimeout(()=>{$modal.css('opacity',1)},10);
    });

    // Zatvaranje modala
    function closeChat(){
        $modal.css('opacity',0);
        $box.removeClass('scale-100').addClass('scale-90');
        setTimeout(()=>{
            $modal.addClass('hidden');
        },300);
    }

    // Klik na X dugme
    $box.find('#close-chat').on('click', closeChat);

    // Klik van modal box-a zatvara modal
    $modal.on('click', function(e){
        if(e.target === this) closeChat();
    });

    // Escape zatvara modal
    $(document).on('keydown', function(e){
        if(e.key === 'Escape' && !$modal.hasClass('hidden')) {
            closeChat();
        }
    });

    // Slanje poruke
    $sendBtn.on('click', function(e){ e.preventDefault(); sendMessage(); });
    $input.on('keydown', function(e){ if(e.key==='Enter'){ e.preventDefault(); sendMessage(); } });

});
</script>
