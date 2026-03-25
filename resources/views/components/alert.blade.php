{{-- alert class component --}}
<div 
class="alert alert-{{ $type }} alert-dismissible fade show position-relative overflow-hidden custom-alert" 
role="alert" 
data-duration="5000">
    {{ $slot }}
    <!-- Progress Bar (Bootstrap) -->
    <div class="progress position-absolute bottom-0 start-0 w-100" style="height: 4px;">
        <div class="progress-bar bg-{{ $type }}"></div>
    </div>

    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>