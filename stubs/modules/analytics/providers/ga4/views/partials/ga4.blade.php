@if(config('services.google.analytics_id'))
<script>
    window.loadGoogleAnalytics = function() {
        if (window.gaLoaded) return;
        window.gaLoaded = true;
        var id = @json(config('services.google.analytics_id'));
        var s = document.createElement('script');
        s.src = 'https://www.googletagmanager.com/gtag/js?id=' + id;
        s.async = true;
        document.head.appendChild(s);
        window.dataLayer = window.dataLayer || [];
        function gtag(){ dataLayer.push(arguments); }
        window.gtag = gtag;
        gtag('js', new Date());
        gtag('config', id);
    };
    // Auto-load if cookie consent has been granted elsewhere in the app.
    if (document.cookie.split('; ').some(function(c){ return c === 'cookie_consent=accepted'; })) {
        window.loadGoogleAnalytics();
    }
</script>
@endif
