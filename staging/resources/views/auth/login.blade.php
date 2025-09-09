@extends('layouts.auth')

@section('title', 'ログイン')

@section('content')
<div class="login-container">
    <!-- Marble Background -->
    <div class="marble-background" id="marble-bg" style="background-image: url('{{ asset('images/marble-background.png') }}');"></div>
    
    <!-- Logo Container -->
    <div class="logo-container">
        <div class="logo-image">
            <img src="{{ asset('images/shicecal-logo.png') }}" 
                 alt="Shise-Cal Logo" 
                 class="logo-img"
                 onerror="this.style.display='none'; this.parentElement.classList.add('logo-fallback');">
        </div>
    </div>
    
    <!-- Login Form -->
    <form method="POST" action="{{ route('login') }}">
        @csrf
        
        <div class="login-form-container">
            <!-- Username Field -->
            <label for="email" class="form-label username-label">UserName</label>
            <input type="email" 
                   class="form-input username-input" 
                   id="email" 
                   name="email" 
                   value="{{ old('email') }}" 
                   placeholder="*********@cedar-web.com"
                   required 
                   autofocus>
            @error('email')
                <div class="error-message username-error">
                    {{ $message }}
                </div>
            @enderror
            
            <!-- Password Field -->
            <label for="password" class="form-label password-label">Password</label>
            <input type="password" 
                   class="form-input password-input" 
                   id="password" 
                   name="password" 
                   placeholder="PASSWORD"
                   required>
            @error('password')
                <div class="error-message password-error">
                    {{ $message }}
                </div>
            @enderror
        </div>
        
        <!-- Login Button -->
        <button type="submit" class="login-button">
            <span class="login-button-text">LOGIN</span>
        </button>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if marble background image loads successfully
    const marbleBackground = document.getElementById('marble-bg');
    const imageUrl = '{{ asset('images/marble-background.png') }}';
    const testImage = new Image();
    
    console.log('Attempting to load marble background from:', imageUrl);
    
    testImage.onload = function() {
        // Image loaded successfully
        console.log('Marble background image loaded successfully');
        marbleBackground.style.opacity = '1';
    };
    
    testImage.onerror = function() {
        // Image failed to load, show fallback
        console.log('Marble background image failed to load, showing fallback');
        console.log('Image URL that failed:', imageUrl);
        marbleBackground.classList.add('image-error');
    };
    
    // Set initial opacity to 0 and fade in when loaded
    marbleBackground.style.opacity = '0';
    marbleBackground.style.transition = 'opacity 0.5s ease';
    
    testImage.src = imageUrl;
    
    // Fallback timeout - if image doesn't load within 3 seconds, show fallback
    setTimeout(function() {
        if (!testImage.complete || testImage.naturalHeight === 0) {
            console.log('Image load timeout, showing fallback');
            marbleBackground.classList.add('image-error');
            marbleBackground.style.opacity = '1';
        }
    }, 3000);
});
</script>
@endpush
@endsection