{{-- ui-kit::head — drop into your <head> for analytics + dark-mode wiring. --}}

{{-- Dark-mode no-flash. Reads ui-kit.theme from localStorage and applies the
     `dark` class on <html> before any styles render. --}}
<script>(()=>{const t=localStorage.getItem('ui-kit.theme');if(t==='dark')document.documentElement.classList.add('dark');})();</script>

{{-- GA4 + PostHog loaders, both consent-gated by `cookie_consent=accepted`.
     The partials no-op when their env keys aren't set, so it's safe to leave
     this component in place even if you don't run analytics. --}}
@includeIf('partials.ga4')
@includeIf('partials.posthog')
