{{-- ui-kit::banners — drop near the top of <body>. Auto-hides when the
     impersonation module isn't installed or no user is currently being
     impersonated. Future kit banners (e.g. demo-mode, maintenance) hook in
     here too without you having to update the layout. --}}

@includeIf('partials.impersonation-banner')
