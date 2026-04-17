<div class="chat-cont-right">
    
    <div class="chat-header d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
        
        <a id="back_user_list" href="javascript:void(0)" class="back-user-list mr-3">
            <i class="fa fa-chevron-left"></i>
        </a>

        
        <div class="chat-block d-flex align-items-center flex-grow-1">
            <div class="media-img-wrap mr-2">
                <div class="avatar avatar-online">
                    <img src="{{ URL::asset('/assets/img/profiles/avatar-02.jpg') }}" alt="User Image" class="avatar-img rounded-circle" />
                </div>
            </div>
            <div class="media-body">
                <div class="user-name font-weight-medium">{{ $userName ?? 'User Name' }}</div>
                <div class="user-status text-muted small">{{ $userStatus ?? 'online' }}</div>
            </div>
        </div>

        
        <div class="chat-options d-flex align-items-center">
            <a href="javascript:void(0)" class="mx-2" title="Call">
                <span><i class="fe fe-phone"></i></span>
            </a>
            <a href="javascript:void(0)" class="mx-2" title="Video">
                <span><i class="fe fe-video"></i></span>
            </a>
            <a href="javascript:void(0)" class="mx-2" title="More">
                <span><i class="fe fe-more-vertical"></i></span>
            </a>
        </div>
    </div>

    
    <div class="chat-messages p-3" style="height: calc(100vh - 250px); overflow-y: auto;">
        @if(isset($chat))
            <livewire:chat-component :chatId="$chat->id" />
        @else
            <p class="text-center text-muted my-4">Select a chat to start messaging.</p>
        @endif
    </div>

    
    <div class="chat-footer p-3 border-top">
        <form wire:submit.prevent="sendMessage" class="d-flex align-items-center space-x-3">
            
            <div class="input-group-prepend">
                <div class="btn btn-light btn-file position-relative">
                    <i class="fas fa-paperclip"></i>
                    <input type="file" class="position-absolute top-0 right-0 opacity-0" />
                </div>
            </div>
            
            <input 
                type="text" 
                wire:model.defer="newMessageText" 
                placeholder="Type something" 
                class="flex-grow p-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            
            <div class="input-group-append">
                <button 
                    type="submit" 
                    class="btn btn-primary" 
                    @if(empty($newMessageText)) disabled @endif
                >
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </form>
    </div>
</div>