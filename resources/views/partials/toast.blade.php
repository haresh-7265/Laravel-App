<div id="toast-stack" style="position:fixed;top:1.5rem;right:1.5rem;width:320px;display:flex;flex-direction:column;gap:8px;z-index:9999;"></div>

<style>
.toast{display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:10px;border:1px solid #374151;background:#1f2937;box-shadow:0 4px 16px rgba(0,0,0,.3);animation:slideIn .25s ease forwards;position:relative;overflow:hidden;}
.toast.removing{animation:slideOut .25s ease forwards;}
.toast-icon{font-size:18px;flex-shrink:0;margin-top:1px;}
.toast-body{flex:1;min-width:0;}
.toast-title{font-size:13px;font-weight:600;margin:0 0 2px;color:#f9fafb;}
.toast-msg{font-size:12px;color:#9ca3af;margin:0;line-height:1.4;}
.toast-close{background:none;border:none;padding:0;cursor:pointer;color:#6b7280;font-size:16px;line-height:1;}
.toast-bar{position:absolute;bottom:0;left:0;height:3px;animation:shrink linear forwards;}
.toast.success .toast-bar{background:#22c55e;} .toast.success .toast-icon{color:#22c55e;}
.toast.error   .toast-bar{background:#ef4444;} .toast.error   .toast-icon{color:#ef4444;}
.toast.warning .toast-bar{background:#f59e0b;} .toast.warning .toast-icon{color:#f59e0b;}
.toast.info    .toast-bar{background:#3b82f6;} .toast.info    .toast-icon{color:#3b82f6;}
@keyframes slideIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}
@keyframes slideOut{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(20px)}}
@keyframes shrink{from{width:100%}to{width:0%}}
</style>

{{-- Session flash — fires on page load --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    @if(session('success'))  window.notify('success',  @json(session('success'))); @endif
    @if(session('error'))    window.notify('error',    @json(session('error')));   @endif
    @if(session('warning'))  window.notify('warning',  @json(session('warning'))); @endif
    @if(session('info'))     window.notify('info',     @json(session('info')));    @endif
});

document.addEventListener('DOMContentLoaded', () => {
    @auth
        if (window.initNotificationListener) {
            window.initNotificationListener({{ auth()->id() }});
        }
    @endauth
});
</script>