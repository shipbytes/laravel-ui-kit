// PostHog event bridge: lets Livewire components dispatch analytics events via
// $this->dispatch('posthog-capture', event: 'foo', properties: [...]).

function capture(event, properties = {}) {
    if (typeof window === 'undefined' || !window.posthog || typeof window.posthog.capture !== 'function') {
        return;
    }
    try {
        window.posthog.capture(event, properties);
    } catch (e) {
        // swallow — analytics must never break the app
    }
}

function reset() {
    if (window.posthog && typeof window.posthog.reset === 'function') {
        try { window.posthog.reset(); } catch (e) {}
    }
}

window.posthogCapture = capture;
window.posthogReset = reset;

document.addEventListener('livewire:init', () => {
    if (!window.Livewire) return;
    window.Livewire.on('posthog-capture', (payload) => {
        const event = payload?.event || payload?.[0]?.event;
        const properties = payload?.properties || payload?.[0]?.properties || {};
        if (event) capture(event, properties);
    });
});

document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form && form.action && form.action.includes('/logout')) {
        reset();
    }
}, true);
