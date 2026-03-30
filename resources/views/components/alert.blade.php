{{-- alert class component --}}
<div 
class="alert alert-{{ $type }} alert-dismissible fade show custom-alert position-fixed top-0 start-50 translate-middle-x mt-3" 
role="alert" 
data-duration="2000"
style="z-index: 9999; min-width: 800px; max-width: 1000px;"
>
    {{ $slot }}

    <!-- Progress Bar -->
    <div class="progress position-absolute bottom-0 start-0 w-100" style="height: 4px;">
        <div class="progress-bar bg-{{ $type }}"></div>
    </div>

    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>