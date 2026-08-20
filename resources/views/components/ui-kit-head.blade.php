{{-- ui-kit::head — drop into your <head> for the kit's head-level wiring. --}}

{{-- Dark-mode no-flash. Reads ui-kit.theme from localStorage and applies the
     `dark` class on <html> before any styles render. --}}
<script>(()=>{const t=localStorage.getItem('ui-kit.theme');if(t==='dark')document.documentElement.classList.add('dark');})();</script>
