{{-- ui-kit::head — drop into your <head> for the kit's head-level wiring. --}}

{{-- Dark-mode no-flash. Puts exactly one of `light` / `dark` on <html> before
     any styles render: the stored ui-kit.theme when there is one, otherwise
     the OS preference. Same logic as the theme store's apply() in ui-kit.js. --}}
<script>(()=>{const s=localStorage.getItem('ui-kit.theme');const d=s?s==='dark':matchMedia('(prefers-color-scheme: dark)').matches;const c=document.documentElement.classList;c.toggle('dark',d);c.toggle('light',!d);})();</script>
